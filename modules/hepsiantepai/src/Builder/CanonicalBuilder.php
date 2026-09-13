<?php

namespace Hepsiantep\Ai\Builder;

use Hepsiantep\Ai\Domain\CanonicalProductDTO;
use Hepsiantep\Ai\Domain\CanonicalVariantId;
use Hepsiantep\Ai\Repository\AiMetaRepository;
use Hepsiantep\Ai\Repository\EvidenceRepository;

if (!defined('_PS_VERSION_')) {
    exit;
}

final class CanonicalBuilder
{
    public function __construct(
        private AiMetaRepository $metaRepository,
        private EvidenceRepository $evidenceRepository
    ) {
    }

    public function build(int $idProduct, int $idProductAttribute = 0, ?\Context $context = null): CanonicalProductDTO
    {
        $context ??= \Context::getContext();
        $idShop = (int) $context->shop->id;
        $idLang = (int) $context->language->id;

        $product = new \Product($idProduct, false, $idLang, $idShop);
        if (!\Validate::isLoadedObject($product) || !$product->active) {
            throw new \RuntimeException('Product is not available in the current shop context.');
        }

        $combination = null;
        if ($idProductAttribute > 0) {
            $combination = new \Combination($idProductAttribute);
            if (!\Validate::isLoadedObject($combination) || (int) $combination->id_product !== $idProduct) {
                throw new \InvalidArgumentException('Combination does not belong to product.');
            }
        }

        $meta = $this->metaRepository->find($idShop, $idProduct, $idProductAttribute) ?? [];
        $evidence = $this->evidenceRepository->findActive($idShop, $idProduct, $idProductAttribute, false);

        $currency = strtoupper((string) $context->currency->iso_code);
        $quantity = (float) \StockAvailable::getQuantityAvailableByProduct($idProduct, $idProductAttribute, $idShop);
        $price = (float) \Product::getPriceStatic($idProduct, true, $idProductAttribute ?: null);
        $availability = $this->availability($product, $quantity);
        $now = gmdate('c');

        $name = is_array($product->name)
            ? (string) ($product->name[$idLang] ?? reset($product->name))
            : (string) $product->name;

        $reference = $combination && !empty($combination->reference)
            ? (string) $combination->reference
            : ((string) $product->reference ?: null);
        $gtin = $this->firstNonEmpty([
            $combination?->ean13 ?? null,
            $combination?->upc ?? null,
            $product->ean13 ?? null,
            $product->upc ?? null,
        ]);
        $mpn = $this->firstNonEmpty([
            $combination?->mpn ?? null,
            $product->mpn ?? null,
        ]);

        $manufacturer = null;
        if ((int) $product->id_manufacturer > 0) {
            $manufacturerObject = new \Manufacturer((int) $product->id_manufacturer, $idLang);
            if (\Validate::isLoadedObject($manufacturerObject)) {
                $manufacturer = (string) $manufacturerObject->name;
            }
        }

        $saleUnit = isset($meta['sale_unit']) && is_string($meta['sale_unit']) && $meta['sale_unit'] !== ''
            ? $meta['sale_unit']
            : 'piece';

        $minimumQuantity = $combination && isset($combination->minimal_quantity)
            ? (float) $combination->minimal_quantity
            : (float) ($product->minimal_quantity ?: 1);

        $sourceUpdatedAt = $this->latestDate([
            $product->date_upd ?? null,
            $meta['source_updated_at'] ?? null,
        ]);

        return new CanonicalProductDTO([
            'schema_version' => '1.0',
            'canonical_variant_id' => CanonicalVariantId::fromPrestaShop($idShop, $idProduct, $idProductAttribute),
            'product_group_id' => null,
            'category_type' => (string) ($meta['category_type'] ?? 'general'),
            'source' => [
                'system' => 'prestashop',
                'id_shop' => $idShop,
                'id_product' => $idProduct,
                'id_product_attribute' => $idProductAttribute,
            ],
            'context' => [
                'language' => (string) ($context->language->iso_code ?? 'en'),
                'currency' => $currency,
                'pricing_context' => 'public_tax_included',
            ],
            'timestamps' => [
                'source_updated_at' => $sourceUpdatedAt,
                'canonical_generated_at' => $now,
            ],
            'identity' => [
                'sku' => $reference,
                'gtin' => $gtin,
                'mpn' => $mpn,
                'brand' => $manufacturer,
                'title' => $name,
            ],
            'variant_dimensions' => $this->variantDimensions($product, $idProductAttribute, $idLang),
            'commercial' => [
                'currency' => $currency,
                'price' => $price,
                'sale_unit' => $saleUnit,
                'min_order_quantity' => $minimumQuantity,
                'availability' => $availability,
                'stock_quantity' => $quantity,
                'as_of' => $now,
                'realtime_required' => true,
            ],
            'verified_specs' => [],
            'derived_properties' => is_array($meta['derived_specs'] ?? null) ? $meta['derived_specs'] : [],
            'suitability' => $this->normalizeSuitability($meta['suitability'] ?? []),
            'evidence' => $evidence,
            'links' => [
                'canonical_web' => $context->link->getProductLink($product),
                'realtime_api' => null,
                'direct_cart' => null,
                'image' => $this->coverImage($product, $context->link),
            ],
        ]);
    }

    private function availability(\Product $product, float $quantity): string
    {
        if ($quantity > 0) {
            return 'in_stock';
        }

        return \Product::isAvailableWhenOutOfStock((int) $product->out_of_stock)
            ? 'backorder'
            : 'out_of_stock';
    }

    /** @return array<string,string> */
    private function variantDimensions(\Product $product, int $idProductAttribute, int $idLang): array
    {
        if ($idProductAttribute < 1) {
            return [];
        }

        $dimensions = [];
        foreach ($product->getAttributeCombinationsById($idProductAttribute, $idLang) ?: [] as $row) {
            $group = trim((string) ($row['group_name'] ?? ''));
            $value = trim((string) ($row['attribute_name'] ?? ''));
            if ($group !== '' && $value !== '') {
                $dimensions[$group] = $value;
            }
        }
        return $dimensions;
    }

    /** @param mixed $raw @return array<string,array<int,string>> */
    private function normalizeSuitability($raw): array
    {
        $raw = is_array($raw) ? $raw : [];
        $result = [];
        foreach (['recommended_for', 'conditionally_suitable_for', 'not_recommended_for'] as $key) {
            $values = is_array($raw[$key] ?? null) ? $raw[$key] : [];
            $result[$key] = array_values(array_unique(array_filter(array_map('strval', $values))));
        }
        return $result;
    }

    private function coverImage(\Product $product, \Link $link): ?string
    {
        $cover = \Product::getCover((int) $product->id);
        if (empty($cover['id_image'])) {
            return null;
        }

        $rewrite = is_array($product->link_rewrite) ? reset($product->link_rewrite) : $product->link_rewrite;
        return $link->getImageLink((string) $rewrite, (string) $cover['id_image']);
    }

    /** @param array<int,mixed> $values */
    private function firstNonEmpty(array $values): ?string
    {
        foreach ($values as $value) {
            if ($value !== null && trim((string) $value) !== '') {
                return (string) $value;
            }
        }
        return null;
    }

    /** @param array<int,mixed> $values */
    private function latestDate(array $values): ?string
    {
        $timestamps = [];
        foreach ($values as $value) {
            if (is_string($value) && trim($value) !== '') {
                $timestamp = strtotime($value);
                if ($timestamp !== false) {
                    $timestamps[] = $timestamp;
                }
            }
        }
        return $timestamps === [] ? null : gmdate('c', max($timestamps));
    }
}

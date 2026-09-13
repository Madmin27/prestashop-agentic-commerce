<?php

namespace PrestaShopAgenticCommerce\Builder;

use PrestaShopAgenticCommerce\Domain\CanonicalProductDTO;
use PrestaShopAgenticCommerce\Domain\CanonicalProductId;
use PrestaShopAgenticCommerce\Domain\CanonicalVariantId;
use PrestaShopAgenticCommerce\Repository\AiMetaRepository;
use PrestaShopAgenticCommerce\Repository\EvidenceRepository;

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

    public function build(
        int $idProduct,
        int $idProductAttribute = 0,
        ?\Context $context = null
    ): CanonicalProductDTO {
        $context ??= \Context::getContext();
        $idShop = (int) $context->shop->id;
        $idLang = (int) $context->language->id;
        $idCurrency = (int) $context->currency->id;
        $idCountry = (int) ($context->country->id ?? \Configuration::get('PS_COUNTRY_DEFAULT'));
        $countryIso = strtoupper((string) (
            $context->country->iso_code ?? \Country::getIsoById($idCountry)
        ));

        $shopProductActive = \Db::getInstance()->getValue(
            'SELECT ps.`active` FROM `' . _DB_PREFIX_ . 'product_shop` ps'
            . ' WHERE ps.`id_shop` = ' . $idShop
            . ' AND ps.`id_product` = ' . $idProduct
        );
        if ($shopProductActive === false || (int) $shopProductActive !== 1) {
            throw new \RuntimeException('Product is not available in the current shop context.');
        }

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

            $assignedToShop = (bool) \Db::getInstance()->getValue(
                'SELECT 1 FROM `' . _DB_PREFIX_ . 'product_attribute_shop`'
                . ' WHERE `id_shop` = ' . $idShop
                . ' AND `id_product` = ' . $idProduct
                . ' AND `id_product_attribute` = ' . $idProductAttribute
            );
            if (!$assignedToShop) {
                throw new \InvalidArgumentException('Combination is not available in the current shop context.');
            }
        }

        $meta = $this->metaRepository->find($idShop, $idProduct, $idProductAttribute) ?? [];
        $evidence = $this->evidenceRepository->findActive($idShop, $idProduct, $idProductAttribute, false);
        $currency = strtoupper((string) $context->currency->iso_code);
        $quantity = (float) \StockAvailable::getQuantityAvailableByProduct(
            $idProduct,
            $idProductAttribute,
            $idShop
        );
        $price = (float) \Product::getPriceStatic($idProduct, true, $idProductAttribute ?: null);
        $now = gmdate('c');

        $name = is_array($product->name)
            ? (string) ($product->name[$idLang] ?? reset($product->name))
            : (string) $product->name;
        $shortDescription = is_array($product->description_short)
            ? (string) ($product->description_short[$idLang] ?? '')
            : (string) $product->description_short;
        $description = is_array($product->description)
            ? (string) ($product->description[$idLang] ?? '')
            : (string) $product->description;

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

        $brand = null;
        if ((int) $product->id_manufacturer > 0) {
            $manufacturer = new \Manufacturer((int) $product->id_manufacturer, $idLang);
            if (\Validate::isLoadedObject($manufacturer)) {
                $brand = (string) $manufacturer->name;
            }
        }

        $saleUnit = isset($meta['sale_unit']) && is_string($meta['sale_unit']) && trim($meta['sale_unit']) !== ''
            ? trim($meta['sale_unit'])
            : 'piece';
        $minimumQuantity = $combination && isset($combination->minimal_quantity)
            ? (float) $combination->minimal_quantity
            : (float) ($product->minimal_quantity ?: 1);
        $image = $this->coverImage($product, $context->link, $idLang);

        return new CanonicalProductDTO([
            'schema_version' => '1.0',
            'canonical_variant_id' => CanonicalVariantId::fromPrestaShop(
                $idShop,
                $idProduct,
                $idProductAttribute
            ),
            'product_group_id' => CanonicalProductId::fromPrestaShop($idShop, $idProduct),
            'category_type' => isset($meta['category_type']) && is_string($meta['category_type']) && trim($meta['category_type']) !== ''
                ? trim($meta['category_type'])
                : 'general',
            'source' => [
                'system' => 'prestashop',
                'id_shop' => $idShop,
                'id_product' => $idProduct,
                'id_product_attribute' => $idProductAttribute,
            ],
            'context' => [
                'id_language' => $idLang,
                'language' => (string) ($context->language->iso_code ?? 'en'),
                'locale' => (string) ($context->language->locale ?? $context->language->iso_code ?? 'en'),
                'id_currency' => $idCurrency,
                'currency' => $currency,
                'id_country' => $idCountry,
                'country' => $countryIso,
                'pricing_context' => 'runtime_context_tax_included',
            ],
            'timestamps' => [
                'source_updated_at' => $this->latestDate([
                    $product->date_upd ?? null,
                    $meta['source_updated_at'] ?? null,
                ]),
                'canonical_generated_at' => $now,
            ],
            'identity' => [
                'sku' => $reference,
                'gtin' => $gtin,
                'mpn' => $mpn,
                'brand' => $brand,
                'title' => $name,
            ],
            'content' => [
                'short_description' => $this->plainText($shortDescription),
                'description' => $this->plainText($description),
            ],
            'media' => $image ? [[
                'type' => 'image',
                'role' => 'primary',
                'url' => $image,
                'alt' => $name,
            ]] : [],
            'variant_dimensions' => $this->variantDimensions($product, $idProductAttribute, $idLang),
            'commercial' => [
                'currency' => $currency,
                'price' => $price,
                'sale_unit' => $saleUnit,
                'min_order_quantity' => $minimumQuantity,
                'availability' => $this->availability($product, $quantity),
                'stock_quantity' => $quantity,
                'orderable' => (bool) $product->available_for_order,
                'show_price' => (bool) $product->show_price,
                'visibility' => (string) $product->visibility,
                'as_of' => $now,
                'realtime_required' => true,
            ],
            'verified_specs' => is_array($meta['verified_specs'] ?? null) ? $meta['verified_specs'] : [],
            'declared_specs' => is_array($meta['declared_specs'] ?? null) ? $meta['declared_specs'] : [],
            'derived_properties' => is_array($meta['derived_specs'] ?? null) ? $meta['derived_specs'] : [],
            'suitability' => $this->normalizeSuitability($meta['suitability'] ?? []),
            'evidence' => $evidence,
            'links' => [
                'canonical_web' => $context->link->getProductLink($product),
                'realtime_api' => null,
                'direct_cart' => null,
                'image' => $image,
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

    /** @return array<int,array<string,mixed>> */
    private function variantDimensions(\Product $product, int $idProductAttribute, int $idLang): array
    {
        if ($idProductAttribute < 1) {
            return [];
        }

        $dimensions = [];
        foreach ($product->getAttributeCombinationsById($idProductAttribute, $idLang) ?: [] as $row) {
            $groupId = (int) ($row['id_attribute_group'] ?? 0);
            $attributeId = (int) ($row['id_attribute'] ?? 0);
            $value = trim((string) ($row['attribute_name'] ?? ''));
            if ($groupId < 1 || $attributeId < 1 || $value === '') {
                continue;
            }

            $dimensions[] = [
                'group_id' => $groupId,
                'group_name' => trim((string) ($row['group_name'] ?? '')),
                'attribute_id' => $attributeId,
                'value' => $value,
            ];
        }

        usort($dimensions, static function (array $left, array $right): int {
            return [$left['group_id'], $left['attribute_id']] <=> [$right['group_id'], $right['attribute_id']];
        });

        return $dimensions;
    }

    /** @param mixed $raw @return array<string,array<int,string>> */
    private function normalizeSuitability($raw): array
    {
        $raw = is_array($raw) ? $raw : [];
        $result = [];
        foreach (['recommended_for', 'conditionally_suitable_for', 'not_recommended_for'] as $key) {
            $values = is_array($raw[$key] ?? null) ? $raw[$key] : [];
            $values = array_values(array_unique(array_filter(array_map('strval', $values))));
            sort($values, SORT_STRING);
            $result[$key] = $values;
        }

        return $result;
    }

    private function coverImage(\Product $product, \Link $link, int $idLang): ?string
    {
        $cover = \Product::getCover((int) $product->id);
        if (empty($cover['id_image'])) {
            return null;
        }

        $rewrite = is_array($product->link_rewrite)
            ? ($product->link_rewrite[$idLang] ?? reset($product->link_rewrite))
            : $product->link_rewrite;

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

    private function plainText(string $value): string
    {
        $value = preg_replace(
            '/<\s*\/?\s*(p|div|br|li|ul|ol|h[1-6]|table|tr|td|th)\b[^>]*>/iu',
            ' ',
            $value
        ) ?? $value;
        $value = html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(preg_replace('/\s+/u', ' ', $value) ?? '');
    }
}

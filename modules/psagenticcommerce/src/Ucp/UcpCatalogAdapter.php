<?php

namespace PrestaShopAgenticCommerce\Ucp;

use PrestaShopAgenticCommerce\Domain\CanonicalProductDTO;
use PrestaShopAgenticCommerce\Publication\CanonicalPublicationPolicy;

if (!defined('_PS_VERSION_')) {
    exit;
}

final class UcpCatalogAdapter
{
    public const VERSION = '2026-08-25';

    private CanonicalPublicationPolicy $publicationPolicy;

    public function __construct(?CanonicalPublicationPolicy $publicationPolicy = null)
    {
        $this->publicationPolicy = $publicationPolicy ?: new CanonicalPublicationPolicy();
    }

    /**
     * @param array<int,CanonicalProductDTO> $variants
     * @return array<string,mixed>|null
     */
    public function product(array $variants): ?array
    {
        $published = [];
        foreach ($variants as $dto) {
            if (!$dto instanceof CanonicalProductDTO) {
                throw new \InvalidArgumentException('UCP variants must be CanonicalProductDTO instances.');
            }
            $data = $this->publicationPolicy->prepare($dto);
            if (($data['commercial']['visibility'] ?? 'none') === 'none') {
                continue;
            }
            if (!is_numeric($data['commercial']['price'] ?? null)) {
                continue;
            }
            $published[] = $data;
        }

        if ($published === []) {
            return null;
        }

        usort($published, static fn(array $a, array $b): int =>
            strcmp((string) $a['canonical_variant_id'], (string) $b['canonical_variant_id'])
        );

        $first = $published[0];
        $currency = (string) $first['commercial']['currency'];
        $ucpVariants = [];
        $amounts = [];

        foreach ($published as $data) {
            if ((string) $data['product_group_id'] !== (string) $first['product_group_id']) {
                throw new \DomainException('Cannot combine variants from different canonical product groups.');
            }
            if ((string) $data['commercial']['currency'] !== $currency) {
                throw new \DomainException('Cannot combine UCP variants with different currencies.');
            }

            $amount = $this->minor((float) $data['commercial']['price']);
            $amounts[] = $amount;
            $availability = (string) $data['commercial']['availability'];
            $available = (bool) $data['commercial']['orderable']
                && in_array($availability, ['in_stock', 'backorder', 'preorder'], true);
            $description = $this->description($data);

            $variant = [
                'id' => (string) $data['canonical_variant_id'],
                'title' => (string) $data['identity']['title'],
                'description' => ['plain' => $description],
                'url' => (string) $data['links']['canonical_web'],
                'price' => ['amount' => $amount, 'currency' => $currency],
                'availability' => [
                    'available' => $available,
                    'status' => $availability,
                ],
            ];
            if (!empty($data['identity']['sku'])) {
                $variant['sku'] = (string) $data['identity']['sku'];
            }
            if (!empty($data['identity']['gtin'])) {
                $variant['barcodes'] = [[
                    'type' => 'GTIN',
                    'value' => (string) $data['identity']['gtin'],
                ]];
            }

            $quantityUnit = $this->quantityUnit((string) ($data['commercial']['sale_unit'] ?? 'piece'));
            if ($quantityUnit !== null) {
                $variant['quantity_unit'] = $quantityUnit;
            }

            $options = [];
            foreach ($data['variant_dimensions'] as $dimension) {
                if (!is_array($dimension)) {
                    continue;
                }
                $name = trim((string) ($dimension['group_name'] ?? ''));
                $label = trim((string) ($dimension['value'] ?? ''));
                if ($name !== '' && $label !== '') {
                    $options[] = ['name' => $name, 'label' => $label];
                }
            }
            if ($options !== []) {
                $variant['options'] = $options;
            }
            $ucpVariants[] = $variant;
        }

        $product = [
            'id' => (string) $first['product_group_id'],
            'title' => (string) $first['identity']['title'],
            'description' => ['plain' => $this->description($first)],
            'url' => (string) $first['links']['canonical_web'],
            'categories' => [[
                'value' => (string) $first['category_type'],
                'taxonomy' => 'merchant',
            ]],
            'price_range' => [
                'min' => ['amount' => min($amounts), 'currency' => $currency],
                'max' => ['amount' => max($amounts), 'currency' => $currency],
            ],
            'variants' => $ucpVariants,
            'media' => $this->media($first['media'] ?? []),
            'metadata' => [
                'category_type' => (string) $first['category_type'],
                'verified_specs' => $first['verified_specs'],
                'declared_specs' => $first['declared_specs'],
                'derived_properties' => $first['derived_properties'],
                'suitability' => $first['suitability'],
            ],
        ];

        $handle = trim((string) parse_url((string) $first['links']['canonical_web'], PHP_URL_PATH), '/');
        if ($handle !== '') {
            $segments = explode('/', $handle);
            $product['handle'] = (string) end($segments);
        }

        return $product;
    }

    /** @param array<string,mixed> $data */
    private function description(array $data): string
    {
        $description = trim((string) ($data['content']['description'] ?? ''));
        return $description !== ''
            ? $description
            : trim((string) ($data['content']['short_description'] ?? ''));
    }

    private function minor(float $amount): int
    {
        return (int) round($amount * 100);
    }

    /** @return array<string,mixed>|null */
    private function quantityUnit(string $saleUnit): ?array
    {
        $normalized = strtolower(trim($saleUnit));
        return match ($normalized) {
            'piece', 'each', 'unit' => null,
            'meter', 'metre', 'm' => ['unit' => 'MTR', 'scale' => 0, 'display_text' => 'm'],
            'kilogram', 'kg' => ['unit' => 'KGM', 'scale' => 0, 'display_text' => 'kg'],
            'gram', 'g' => ['unit' => 'GRM', 'scale' => 0, 'display_text' => 'g'],
            default => $normalized === ''
                ? null
                : ['unit' => $normalized, 'scale' => 0, 'display_text' => $saleUnit],
        };
    }

    /** @param mixed $media @return array<int,array<string,mixed>> */
    private function media($media): array
    {
        if (!is_array($media)) {
            return [];
        }
        $result = [];
        foreach ($media as $item) {
            if (!is_array($item) || empty($item['url'])) {
                continue;
            }
            $entry = [
                'type' => (string) ($item['type'] ?? 'image'),
                'url' => (string) $item['url'],
            ];
            if (isset($item['alt']) && $item['alt'] !== null && trim((string) $item['alt']) !== '') {
                $entry['alt_text'] = (string) $item['alt'];
            }
            $result[] = $entry;
        }
        return $result;
    }
}

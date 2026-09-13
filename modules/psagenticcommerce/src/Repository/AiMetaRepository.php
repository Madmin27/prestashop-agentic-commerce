<?php

namespace PrestaShopAgenticCommerce\Repository;

if (!defined('_PS_VERSION_')) {
    exit;
}

final class AiMetaRepository
{
    /**
     * Resolve product-level metadata (attribute 0) with optional variant
     * overrides. Scalar nulls inherit from the product row. Specification maps
     * are merged by property key. A variant suitability document, when present,
     * replaces the product-level suitability document so an explicit empty list
     * can clear inherited recommendations.
     *
     * @return array<string,mixed>|null
     */
    public function find(int $idShop, int $idProduct, int $idProductAttribute): ?array
    {
        $base = $this->findExact($idShop, $idProduct, 0);
        if ($idProductAttribute === 0) {
            return $base;
        }

        $variant = $this->findExact($idShop, $idProduct, $idProductAttribute);
        if ($base === null && $variant === null) {
            return null;
        }
        if ($base === null) {
            return $variant;
        }
        if ($variant === null) {
            return $base;
        }

        return [
            'category_type' => $this->firstNonEmpty($variant['category_type'] ?? null, $base['category_type'] ?? null),
            'sale_unit' => $this->firstNonEmpty($variant['sale_unit'] ?? null, $base['sale_unit'] ?? null),
            'verified_specs' => array_replace($base['verified_specs'], $variant['verified_specs']),
            'declared_specs' => array_replace($base['declared_specs'], $variant['declared_specs']),
            'derived_specs' => array_replace($base['derived_specs'], $variant['derived_specs']),
            'suitability' => $variant['_has_suitability'] ? $variant['suitability'] : $base['suitability'],
            'source_updated_at' => $this->latestDate(
                $base['source_updated_at'] ?? null,
                $variant['source_updated_at'] ?? null
            ),
            'updated_at' => $this->latestDate($base['updated_at'] ?? null, $variant['updated_at'] ?? null),
        ];
    }

    /** @return array<string,mixed>|null */
    private function findExact(int $idShop, int $idProduct, int $idProductAttribute): ?array
    {
        $query = new \DbQuery();
        $query->select('*')
            ->from('agenticcommerce_product_meta')
            ->where('id_shop = ' . (int) $idShop)
            ->where('id_product = ' . (int) $idProduct)
            ->where('id_product_attribute = ' . (int) $idProductAttribute);

        $row = \Db::getInstance()->getRow($query);
        if (!$row) {
            return null;
        }

        $row['verified_specs'] = $this->decode($row['verified_specs_json'] ?? null);
        $row['declared_specs'] = $this->decode($row['declared_specs_json'] ?? null);
        $row['derived_specs'] = $this->decode($row['derived_specs_json'] ?? null);
        $row['suitability'] = $this->decode($row['suitability_json'] ?? null);
        $row['_has_suitability'] = $this->hasJson($row['suitability_json'] ?? null);

        return $row;
    }

    /** @return array<string,mixed> */
    private function decode($value): array
    {
        if (!$this->hasJson($value)) {
            return [];
        }

        $decoded = json_decode((string) $value, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function hasJson($value): bool
    {
        return is_string($value) && trim($value) !== '';
    }

    private function firstNonEmpty($preferred, $fallback): ?string
    {
        if (is_string($preferred) && trim($preferred) !== '') {
            return $preferred;
        }
        if (is_string($fallback) && trim($fallback) !== '') {
            return $fallback;
        }
        return null;
    }

    private function latestDate($left, $right): ?string
    {
        $values = array_filter([$left, $right], static fn ($value): bool => is_string($value) && trim($value) !== '');
        if ($values === []) {
            return null;
        }

        usort($values, static function (string $a, string $b): int {
            return (strtotime($a) ?: 0) <=> (strtotime($b) ?: 0);
        });

        return end($values) ?: null;
    }
}

<?php

namespace PrestaShopAgenticCommerce\Repository;

if (!defined('_PS_VERSION_')) {
    exit;
}

final class EvidenceRepository
{
    /**
     * Product-level evidence (attribute 0) is inherited by variants. Variant
     * evidence is added alongside it. Publication later binds an evidence row
     * to the exact property value through value_hash, preventing a base-product
     * measurement from validating a different variant value.
     *
     * @return array<string,array<int,array<string,mixed>>>
     */
    public function findActive(
        int $idShop,
        int $idProduct,
        int $idProductAttribute,
        bool $publicOnly = false
    ): array {
        $query = new \DbQuery();
        $query->select('*')
            ->from('agenticcommerce_evidence')
            ->where('id_shop = ' . (int) $idShop)
            ->where('id_product = ' . (int) $idProduct)
            ->where(
                $idProductAttribute > 0
                    ? 'id_product_attribute IN (0,' . (int) $idProductAttribute . ')'
                    : 'id_product_attribute = 0'
            )
            ->where("status = 'active'")
            ->orderBy('property_key ASC, evidence_class ASC, source_type ASC, id_evidence ASC');

        if ($publicOnly) {
            $query->where('is_public = 1');
        }

        $rows = \Db::getInstance()->executeS($query) ?: [];
        $result = [];

        foreach ($rows as $row) {
            $key = trim((string) ($row['property_key'] ?? ''));
            if ($key === '') {
                continue;
            }

            $result[$key][] = [
                'evidence_class' => (string) ($row['evidence_class'] ?? ''),
                'source_type' => (string) ($row['source_type'] ?? ''),
                'source_id' => $this->nullableString($row['source_id'] ?? null),
                'source_url' => $this->nullableString($row['source_url'] ?? null),
                'value_hash' => $this->nullableString($row['value_hash'] ?? null),
                'confidence' => (float) ($row['confidence'] ?? 0),
                'evidence_date' => $this->nullableString($row['evidence_date'] ?? null),
                'is_public' => (bool) ((int) ($row['is_public'] ?? 0)),
                'status' => (string) ($row['status'] ?? ''),
                'notes' => $this->nullableString($row['notes'] ?? null),
            ];
        }

        ksort($result, SORT_STRING);
        return $result;
    }

    private function nullableString($value): ?string
    {
        if ($value === null) {
            return null;
        }
        $value = trim((string) $value);
        return $value === '' ? null : $value;
    }
}

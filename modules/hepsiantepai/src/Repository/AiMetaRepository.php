<?php

namespace Hepsiantep\Ai\Repository;

if (!defined('_PS_VERSION_')) {
    exit;
}

final class AiMetaRepository
{
    /** @return array<string,mixed>|null */
    public function find(int $idShop, int $idProduct, int $idProductAttribute): ?array
    {
        $sql = new \DbQuery();
        $sql->select('*')
            ->from('hepsiantep_ai_product_meta')
            ->where('id_shop = ' . (int) $idShop)
            ->where('id_product = ' . (int) $idProduct)
            ->where('id_product_attribute = ' . (int) $idProductAttribute);

        $row = \Db::getInstance()->getRow($sql);
        if (!$row) {
            return null;
        }

        $row['suitability'] = $this->decodeJson($row['suitability_json'] ?? null);
        $row['derived_specs'] = $this->decodeJson($row['derived_specs_json'] ?? null);

        return $row;
    }

    /** @return array<string,mixed> */
    private function decodeJson($value): array
    {
        if (!is_string($value) || trim($value) === '') {
            return [];
        }

        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : [];
    }
}

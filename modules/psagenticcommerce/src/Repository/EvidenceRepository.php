<?php
namespace PrestaShopAgenticCommerce\Repository;
if (!defined('_PS_VERSION_')) { exit; }
final class EvidenceRepository
{
    public function findActive(int $idShop, int $idProduct, int $idProductAttribute, bool $publicOnly = false): array
    {
        $query = new \DbQuery();
        $query->select('*');
        $query->from('agentic_evidence');
        $query->where('id_shop = '.(int)$idShop);
        $query->where('id_product = '.(int)$idProduct);
        $query->where('id_product_attribute = '.(int)$idProductAttribute);
        $query->where("status = 'active'");
        if ($publicOnly) { $query->where('is_public = 1'); }
        $rows = \Db::getInstance()->executeS($query) ?: [];
        $result = [];
        foreach ($rows as $row) {
            $key = (string)($row['property_key'] ?? '');
            if ($key === '') { continue; }
            $row['confidence'] = (float)($row['confidence'] ?? 0);
            $row['is_public'] = (bool)($row['is_public'] ?? false);
            $result[$key][] = $row;
        }
        return $result;
    }
}

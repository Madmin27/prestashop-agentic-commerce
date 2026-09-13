<?php
namespace Hepsiantep\Ai\Repository;
if (!defined('_PS_VERSION_')) { exit; }
final class EvidenceRepository
{
    public function findActive(int $idShop, int $idProduct, int $idProductAttribute, bool $publicOnly = false): array
    {
        $sql = new \DbQuery();
        $sql->select('*')->from('hepsiantep_ai_evidence')
            ->where('id_shop = ' . (int) $idShop)
            ->where('id_product = ' . (int) $idProduct)
            ->where('id_product_attribute = ' . (int) $idProductAttribute)
            ->where("status = 'active'");
        if ($publicOnly) { $sql->where('is_public = 1'); }
        $rows = \Db::getInstance()->executeS($sql) ?: [];
        $grouped = [];
        foreach ($rows as $row) {
            $key = (string) $row['property_key'];
            $grouped[$key][] = [
                'evidence_class' => (string) $row['evidence_class'],
                'source_type' => (string) $row['source_type'],
                'source_id' => $row['source_id'] !== null ? (string) $row['source_id'] : null,
                'source_url' => $row['source_url'] !== null ? (string) $row['source_url'] : null,
                'confidence' => (float) $row['confidence'],
                'evidence_date' => $row['evidence_date'] ?: null,
                'is_public' => (bool) $row['is_public'],
                'status' => (string) $row['status'],
                'notes' => $row['notes'] !== null ? (string) $row['notes'] : null,
            ];
        }
        return $grouped;
    }
}

<?php

namespace PrestaShopAgenticCommerce\Repository;

use PrestaShopAgenticCommerce\Contract\ProductVariantSourceInterface;

if (!defined('_PS_VERSION_')) {
    exit;
}

final class ProductVariantRepository implements ProductVariantSourceInterface
{
    /**
     * Enumerate every active, AI-discoverable product representation in a shop.
     * Products with combinations yield one row per shop-associated combination;
     * simple products yield attribute id 0.
     *
     * @return array<int,array{id_product:int,id_product_attribute:int}>
     */
    public function listForShop(int $idShop): array
    {
        if ($idShop < 1) {
            throw new \InvalidArgumentException('Shop id must be positive.');
        }

        $query = new \DbQuery();
        $query->select('DISTINCT ps.`id_product`, COALESCE(pas.`id_product_attribute`, 0) AS `id_product_attribute`')
            ->from('product_shop', 'ps')
            ->leftJoin(
                'product_attribute_shop',
                'pas',
                'pas.`id_product` = ps.`id_product` AND pas.`id_shop` = ps.`id_shop`'
            )
            ->where('ps.`id_shop` = ' . $idShop)
            ->where('ps.`active` = 1')
            ->where("ps.`visibility` <> 'none'")
            ->orderBy('ps.`id_product` ASC, `id_product_attribute` ASC');

        $rows = \Db::getInstance()->executeS($query) ?: [];
        $result = [];
        foreach ($rows as $row) {
            $idProduct = (int) ($row['id_product'] ?? 0);
            $idProductAttribute = (int) ($row['id_product_attribute'] ?? 0);
            if ($idProduct < 1 || $idProductAttribute < 0) {
                continue;
            }
            $result[] = [
                'id_product' => $idProduct,
                'id_product_attribute' => $idProductAttribute,
            ];
        }

        return $result;
    }
}

<?php

namespace PrestaShopAgenticCommerce\Ucp;

if (!defined('_PS_VERSION_')) {
    exit;
}

final class UcpCatalogSource
{
    /**
     * @return array{ids:array<int,int>,total:int,has_next:bool,next_offset:int}
     */
    public function searchProductIds(\Context $context, string $query, int $limit, int $offset): array
    {
        $idLang = (int) $context->language->id;
        $idShop = (int) $context->shop->id;
        $query = trim($query);
        $limit = min(max($limit, 1), 50);
        $offset = max($offset, 0);

        if ($query !== '') {
            $rows = \Product::searchByName($idLang, $query) ?: [];
            $ids = [];
            foreach ($rows as $row) {
                $idProduct = (int) ($row['id_product'] ?? 0);
                if ($idProduct < 1 || isset($ids[$idProduct])) {
                    continue;
                }
                $product = new \Product($idProduct, false, $idLang, $idShop);
                if (\Validate::isLoadedObject($product) && $product->active && $product->visibility !== 'none') {
                    $ids[$idProduct] = $idProduct;
                }
            }
            $all = array_values($ids);
            $total = count($all);
            $page = array_slice($all, $offset, $limit);
            return [
                'ids' => $page,
                'total' => $total,
                'has_next' => ($offset + count($page)) < $total,
                'next_offset' => $offset + count($page),
            ];
        }

        $rows = \Product::getProducts($idLang, $offset, $limit, 'id_product', 'ASC', false, true, $context) ?: [];
        $ids = [];
        foreach ($rows as $row) {
            $idProduct = (int) ($row['id_product'] ?? 0);
            if ($idProduct > 0) {
                $ids[] = $idProduct;
            }
        }

        $total = (int) \Db::getInstance()->getValue(
            'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'product_shop` WHERE `id_shop` = ' . $idShop
            . " AND `active` = 1 AND `visibility` <> 'none'"
        );

        return [
            'ids' => $ids,
            'total' => $total,
            'has_next' => ($offset + count($ids)) < $total,
            'next_offset' => $offset + count($ids),
        ];
    }

    /** @return array<int,int> */
    public function variantIds(\Context $context, int $idProduct): array
    {
        $idLang = (int) $context->language->id;
        $idShop = (int) $context->shop->id;
        $product = new \Product($idProduct, false, $idLang, $idShop);
        if (!\Validate::isLoadedObject($product) || !$product->active || $product->visibility === 'none') {
            return [];
        }

        $ids = [];
        foreach ($product->getAttributeCombinations($idLang) ?: [] as $row) {
            $id = (int) ($row['id_product_attribute'] ?? 0);
            if ($id > 0) {
                $ids[$id] = $id;
            }
        }

        if ($ids === []) {
            return [0];
        }
        ksort($ids, SORT_NUMERIC);
        return array_values($ids);
    }

    /**
     * @return array<int,array{id_product:int,id_product_attribute:int|null}>
     */
    public function parseLookupIds(array $ids, int $idShop): array
    {
        $result = [];
        foreach ($ids as $raw) {
            $id = trim((string) $raw);
            if ($id === '') {
                continue;
            }

            if (preg_match('/^ps-(\d+)-(\d+)-(\d+)$/', $id, $m) === 1) {
                if ((int) $m[1] !== $idShop) {
                    continue;
                }
                $result[] = [
                    'id_product' => (int) $m[2],
                    'id_product_attribute' => (int) $m[3],
                ];
                continue;
            }

            if (preg_match('/^ps-(\d+)-(\d+)$/', $id, $m) === 1) {
                if ((int) $m[1] !== $idShop) {
                    continue;
                }
                $result[] = [
                    'id_product' => (int) $m[2],
                    'id_product_attribute' => null,
                ];
                continue;
            }

            if (ctype_digit($id) && (int) $id > 0) {
                $result[] = ['id_product' => (int) $id, 'id_product_attribute' => null];
            }
        }

        return $result;
    }
}

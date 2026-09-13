<?php

namespace FD\PrismUcp\Catalog;

use FD\PrismUcp\Http\Response;
use FD\PrismUcp\Ucp\Formatter;
use FD\PrismUcp\Ucp\UcpError;

if (!defined('_PS_VERSION_')) {
    exit;
}

final class CatalogService
{
    public function __construct(private \Context $context)
    {
    }

    public function search(array $body): Response
    {
        $query = trim((string) ($body['query'] ?? ''));
        $limit = min(max((int) ($body['limit'] ?? 10), 1), 50);
        $offset = max((int) ($body['offset'] ?? 0), 0);

        $provider = CatalogProviderRegistry::collect()->getProvider();
        if ($provider !== null) {
            $result = $provider->search(['query' => $query, 'limit' => $limit, 'offset' => $offset]);
            return $this->searchResponse($result->products, $result->totalCount, $result->hasNextPage);
        }

        return $this->defaultSearch($query, $limit, $offset);
    }

    public function lookup(array $body): Response
    {
        $ids = $body['ids'] ?? null;
        if (!is_array($ids) || $ids === []) {
            return UcpError::response('missing_ids', 'ids array is required', 400);
        }
        $ids = array_slice($ids, 0, 50);

        $provider = CatalogProviderRegistry::collect()->getProvider();
        if ($provider !== null) {
            $result = $provider->lookup($ids);
            return $this->lookupResponse($result->products);
        }

        return $this->defaultLookup($ids);
    }

    private function defaultSearch(string $query, int $limit, int $offset): Response
    {
        $idLang = (int) $this->context->language->id;
        $currencyIso = $this->context->currency->iso_code;
        $link = $this->context->link;
        $products = [];
        $total = 0;

        if ($query !== '') {
            $results = \Product::searchByName($idLang, $query) ?: [];
            $total = count($results);
            foreach (array_slice($results, $offset, $limit) as $row) {
                $product = new \Product((int) $row['id_product'], false, $idLang);
                if (\Validate::isLoadedObject($product) && $product->active) {
                    $products[] = Formatter::product($product, $idLang, $currencyIso, $link);
                }
            }
        } else {
            $rows = \Product::getProducts($idLang, $offset, $limit, 'date_add', 'DESC', false, true) ?: [];
            $total = (int) \Db::getInstance()->getValue(
                'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'product_shop` WHERE `id_shop` = ' .
                (int) $this->context->shop->id . ' AND `active` = 1'
            );
            foreach ($rows as $row) {
                $product = new \Product((int) $row['id_product'], false, $idLang);
                if (\Validate::isLoadedObject($product)) {
                    $products[] = Formatter::product($product, $idLang, $currencyIso, $link);
                }
            }
        }

        return $this->searchResponse($products, $total, ($offset + $limit) < $total);
    }

    private function defaultLookup(array $ids): Response
    {
        $idLang = (int) $this->context->language->id;
        $currencyIso = $this->context->currency->iso_code;
        $link = $this->context->link;
        $products = [];

        foreach ($ids as $id) {
            $product = new \Product((int) $id, false, $idLang);
            if (\Validate::isLoadedObject($product) && $product->active) {
                $products[] = Formatter::product($product, $idLang, $currencyIso, $link);
            }
        }

        return $this->lookupResponse($products);
    }

    private function searchResponse(array $products, int $total, bool $hasNextPage): Response
    {
        return Response::json(200, [
            'ucp' => [
                'version' => CatalogProtocol::VERSION,
                'status' => 'success',
                'capabilities' => [
                    'dev.ucp.shopping.catalog.search' => [['version' => CatalogProtocol::VERSION]],
                ],
            ],
            'products' => $products,
            'pagination' => [
                'total_count' => max(0, $total),
                'has_next_page' => $hasNextPage,
            ],
            'messages' => [],
        ]);
    }

    private function lookupResponse(array $products): Response
    {
        return Response::json(200, [
            'ucp' => [
                'version' => CatalogProtocol::VERSION,
                'status' => 'success',
                'capabilities' => [
                    'dev.ucp.shopping.catalog.lookup' => [['version' => CatalogProtocol::VERSION]],
                ],
            ],
            'products' => $products,
            'messages' => [],
        ]);
    }
}

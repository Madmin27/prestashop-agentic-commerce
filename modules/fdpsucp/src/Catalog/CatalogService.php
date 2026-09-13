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
        $filters = is_array($body['filters'] ?? null) ? $body['filters'] : [];
        $pagination = is_array($body['pagination'] ?? null) ? $body['pagination'] : [];
        $limit = min(max((int) ($pagination['limit'] ?? $body['limit'] ?? 10), 1), 50);

        try {
            $offset = isset($pagination['cursor'])
                ? $this->decodeCursor((string) $pagination['cursor'])
                : max((int) ($body['offset'] ?? 0), 0);
        } catch (\InvalidArgumentException $e) {
            return UcpError::response('invalid_cursor', 'Invalid catalog pagination cursor', 400);
        }

        $provider = CatalogProviderRegistry::collect()->getProvider();
        if ($provider !== null) {
            if ($query === '' && $filters === []) {
                return UcpError::response('invalid_search', 'catalog search requires query or filters', 400);
            }
            $result = $provider->search([
                'query' => $query,
                'filters' => $filters,
                'context' => is_array($body['context'] ?? null) ? $body['context'] : [],
                'limit' => $limit,
                'offset' => $offset,
            ]);
            return $this->searchResponse(
                $result->products,
                $result->totalCount,
                $result->hasNextPage,
                $result->cursor,
                CatalogProtocol::VERSION,
                $result->messages
            );
        }

        return $this->defaultSearch($query, $limit, $offset);
    }

    public function lookup(array $body): Response
    {
        $ids = $body['ids'] ?? null;
        if (!is_array($ids) || $ids === []) {
            return UcpError::response('missing_ids', 'ids array is required', 400);
        }
        $ids = array_slice(array_values(array_map('strval', $ids)), 0, 50);

        $provider = CatalogProviderRegistry::collect()->getProvider();
        if ($provider !== null) {
            $result = $provider->lookup($ids, [
                'filters' => is_array($body['filters'] ?? null) ? $body['filters'] : [],
                'context' => is_array($body['context'] ?? null) ? $body['context'] : [],
            ]);
            return $this->lookupResponse(
                $result->products,
                CatalogProtocol::VERSION,
                $result->messages
            );
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
                'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'product_shop` WHERE `id_shop` = '
                . (int) $this->context->shop->id . ' AND `active` = 1'
            );
            foreach ($rows as $row) {
                $product = new \Product((int) $row['id_product'], false, $idLang);
                if (\Validate::isLoadedObject($product)) {
                    $products[] = Formatter::product($product, $idLang, $currencyIso, $link);
                }
            }
        }

        $hasNextPage = ($offset + count($products)) < $total;
        return $this->searchResponse(
            $products,
            $total,
            $hasNextPage,
            $hasNextPage ? $this->encodeCursor($offset + count($products)) : null,
            Formatter::UCP_VERSION
        );
    }

    private function defaultLookup(array $ids): Response
    {
        $idLang = (int) $this->context->language->id;
        $currencyIso = $this->context->currency->iso_code;
        $link = $this->context->link;
        $products = [];

        foreach ($ids as $id) {
            if (!ctype_digit((string) $id)) {
                continue;
            }
            $product = new \Product((int) $id, false, $idLang);
            if (\Validate::isLoadedObject($product) && $product->active) {
                $products[] = Formatter::product($product, $idLang, $currencyIso, $link);
            }
        }

        return $this->lookupResponse($products, Formatter::UCP_VERSION);
    }

    private function searchResponse(
        array $products,
        ?int $total,
        bool $hasNextPage,
        ?string $cursor,
        string $version,
        array $messages = []
    ): Response {
        $pagination = ['has_next_page' => $hasNextPage];
        if ($total !== null) {
            $pagination['total_count'] = max(0, $total);
        }
        if ($hasNextPage && $cursor !== null && $cursor !== '') {
            $pagination['cursor'] = $cursor;
        }

        return Response::json(200, [
            'ucp' => [
                'version' => $version,
                'status' => 'success',
                'capabilities' => [
                    'dev.ucp.shopping.catalog.search' => [['version' => $version]],
                ],
            ],
            'products' => $products,
            'pagination' => $pagination,
            'messages' => array_values($messages),
        ]);
    }

    private function lookupResponse(array $products, string $version, array $messages = []): Response
    {
        return Response::json(200, [
            'ucp' => [
                'version' => $version,
                'status' => 'success',
                'capabilities' => [
                    'dev.ucp.shopping.catalog.lookup' => [['version' => $version]],
                ],
            ],
            'products' => $products,
            'messages' => array_values($messages),
        ]);
    }

    private function encodeCursor(int $offset): string
    {
        return rtrim(strtr(base64_encode('o:' . max(0, $offset)), '+/', '-_'), '=');
    }

    private function decodeCursor(string $cursor): int
    {
        $cursor = trim($cursor);
        if ($cursor === '') {
            return 0;
        }
        $padding = strlen($cursor) % 4;
        if ($padding !== 0) {
            $cursor .= str_repeat('=', 4 - $padding);
        }
        $decoded = base64_decode(strtr($cursor, '-_', '+/'), true);
        if ($decoded === false || preg_match('/^o:(\d+)$/', $decoded, $m) !== 1) {
            throw new \InvalidArgumentException('Invalid catalog pagination cursor.');
        }
        return (int) $m[1];
    }
}

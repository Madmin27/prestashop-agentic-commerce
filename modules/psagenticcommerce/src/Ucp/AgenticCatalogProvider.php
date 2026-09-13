<?php

namespace PrestaShopAgenticCommerce\Ucp;

use FD\PrismUcp\Catalog\CatalogLookupResult;
use FD\PrismUcp\Catalog\CatalogProviderInterface;
use FD\PrismUcp\Catalog\CatalogSearchResult;
use PrestaShopAgenticCommerce\Contract\PublicCanonicalProductProviderInterface;

if (!defined('_PS_VERSION_')) {
    exit;
}

final class AgenticCatalogProvider implements CatalogProviderInterface
{
    private \Context $context;
    private PublicCanonicalProductProviderInterface $productProvider;
    private UcpCatalogAdapter $adapter;
    private UcpCatalogSource $source;

    public function __construct(
        \Context $context,
        PublicCanonicalProductProviderInterface $productProvider,
        UcpCatalogAdapter $adapter,
        UcpCatalogSource $source
    ) {
        $this->context = $context;
        $this->productProvider = $productProvider;
        $this->adapter = $adapter;
        $this->source = $source;
    }

    public function search(array $params): CatalogSearchResult
    {
        $query = trim((string) ($params['query'] ?? ''));
        $filters = is_array($params['filters'] ?? null) ? $params['filters'] : [];
        $limit = min(max((int) ($params['limit'] ?? 10), 1), 50);
        $scanOffset = max((int) ($params['offset'] ?? 0), 0);

        $products = [];
        $totalUnderlying = 0;
        $hasNext = false;
        $nextOffset = null;

        while (true) {
            $page = $this->source->searchProductIds($this->context, $query, 50, $scanOffset);
            $totalUnderlying = (int) $page['total'];
            if ($page['ids'] === []) {
                break;
            }

            foreach ($page['ids'] as $idProduct) {
                $candidateOffset = $scanOffset;
                ++$scanOffset;

                $product = $this->buildProduct((int) $idProduct, null);
                if ($product === null || !$this->matchesFilters($product, $filters)) {
                    continue;
                }

                if (count($products) < $limit) {
                    $products[] = $product;
                    continue;
                }

                $hasNext = true;
                $nextOffset = $candidateOffset;
                break 2;
            }

            if (!(bool) $page['has_next']) {
                break;
            }
        }

        $cursor = $hasNext && $nextOffset !== null
            ? $this->cursor($nextOffset)
            : null;

        return new CatalogSearchResult(
            $products,
            $filters === [] ? $totalUnderlying : null,
            $hasNext,
            $cursor
        );
    }

    public function lookup(array $ids): CatalogLookupResult
    {
        $requests = $this->source->parseLookupIds($ids, (int) $this->context->shop->id);
        $products = [];
        $seen = [];

        foreach ($requests as $request) {
            $key = $request['id_product'] . ':' . ($request['id_product_attribute'] ?? '*');
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;

            $product = $this->buildProduct(
                (int) $request['id_product'],
                $request['id_product_attribute'] === null
                    ? null
                    : (int) $request['id_product_attribute']
            );
            if ($product !== null) {
                $products[] = $product;
            }
        }

        return new CatalogLookupResult($products);
    }

    /** @return array<string,mixed>|null */
    private function buildProduct(int $idProduct, ?int $onlyAttribute): ?array
    {
        $variantIds = $onlyAttribute === null
            ? $this->source->variantIds($this->context, $idProduct)
            : [$onlyAttribute];

        if ($variantIds === []) {
            return null;
        }

        $dtos = [];
        foreach ($variantIds as $idProductAttribute) {
            try {
                $dtos[] = $this->productProvider->build(
                    $idProduct,
                    (int) $idProductAttribute,
                    $this->context
                );
            } catch (\Throwable $e) {
                \PrestaShopLogger::addLog(
                    '[Agentic Commerce] UCP canonical build skipped product ' . $idProduct
                    . '/' . (int) $idProductAttribute . ': ' . $e->getMessage(),
                    2
                );
            }
        }

        return $dtos === [] ? null : $this->adapter->product($dtos);
    }

    /** @param array<string,mixed> $product @param array<string,mixed> $filters */
    private function matchesFilters(array $product, array $filters): bool
    {
        $categories = is_array($filters['categories'] ?? null) ? $filters['categories'] : [];
        if ($categories !== []) {
            $wanted = array_values(array_unique(array_map('strval', $categories)));
            $actual = [];
            foreach ($product['categories'] ?? [] as $category) {
                if (is_array($category) && isset($category['value'])) {
                    $actual[] = (string) $category['value'];
                }
            }
            if (array_intersect($wanted, $actual) === []) {
                return false;
            }
        }

        $price = is_array($filters['price'] ?? null) ? $filters['price'] : [];
        if ($price !== []) {
            $min = isset($price['min']) && is_numeric($price['min']) ? (int) $price['min'] : null;
            $max = isset($price['max']) && is_numeric($price['max']) ? (int) $price['max'] : null;
            $matches = false;
            foreach ($product['variants'] ?? [] as $variant) {
                $amount = is_array($variant) && isset($variant['price']['amount'])
                    ? (int) $variant['price']['amount']
                    : null;
                if ($amount === null) {
                    continue;
                }
                if (($min === null || $amount >= $min) && ($max === null || $amount <= $max)) {
                    $matches = true;
                    break;
                }
            }
            if (!$matches) {
                return false;
            }
        }

        return true;
    }

    private function cursor(int $offset): string
    {
        return rtrim(strtr(base64_encode('o:' . max(0, $offset)), '+/', '-_'), '=');
    }
}

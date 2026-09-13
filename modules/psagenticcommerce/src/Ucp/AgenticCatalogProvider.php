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
    public function __construct(
        private \Context $context,
        private PublicCanonicalProductProviderInterface $productProvider,
        private UcpCatalogAdapter $adapter,
        private UcpCatalogSource $source
    ) {
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
                $candidateOffset = $scanOffset++;
                $product = $this->filterProduct(
                    $this->buildProduct((int) $idProduct, null),
                    $filters
                );
                if ($product === null) {
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

        return new CatalogSearchResult(
            $products,
            $filters === [] ? $totalUnderlying : null,
            $hasNext,
            $hasNext && $nextOffset !== null ? $this->cursor($nextOffset) : null
        );
    }

    public function lookup(array $ids, array $params = []): CatalogLookupResult
    {
        $filters = is_array($params['filters'] ?? null) ? $params['filters'] : [];
        $requests = $this->source->parseLookupIds($ids, (int) $this->context->shop->id);
        $merged = [];

        foreach ($requests as $request) {
            $product = $this->filterProduct(
                $this->buildProduct(
                    (int) $request['id_product'],
                    $request['id_product_attribute'] === null
                        ? null
                        : (int) $request['id_product_attribute']
                ),
                $filters
            );
            if ($product === null) {
                continue;
            }

            $input = [
                'id' => (string) $request['input_id'],
                'match' => (string) $request['match'],
            ];
            foreach ($product['variants'] as &$variant) {
                $variant['inputs'] = [$input];
            }
            unset($variant);
            $this->mergeLookupProduct($merged, $product);
        }

        ksort($merged, SORT_STRING);
        return new CatalogLookupResult(array_values($merged));
    }

    /** @return array<string,mixed>|null */
    private function buildProduct(int $idProduct, ?int $onlyAttribute): ?array
    {
        $shopVariantIds = $this->source->variantIds($this->context, $idProduct);
        if ($shopVariantIds === []) {
            return null;
        }
        if ($onlyAttribute !== null) {
            if (!in_array($onlyAttribute, $shopVariantIds, true)) {
                return null;
            }
            $shopVariantIds = [$onlyAttribute];
        }

        $dtos = [];
        foreach ($shopVariantIds as $idProductAttribute) {
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

    /** @param array<string,mixed>|null $product @param array<string,mixed> $filters */
    private function filterProduct(?array $product, array $filters): ?array
    {
        if ($product === null) {
            return null;
        }

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
                return null;
            }
        }

        $price = is_array($filters['price'] ?? null) ? $filters['price'] : [];
        if ($price !== []) {
            $min = isset($price['min']) && is_numeric($price['min']) ? (int) $price['min'] : null;
            $max = isset($price['max']) && is_numeric($price['max']) ? (int) $price['max'] : null;
            $variants = [];
            foreach ($product['variants'] ?? [] as $variant) {
                $amount = is_array($variant) && isset($variant['price']['amount'])
                    ? (int) $variant['price']['amount']
                    : null;
                if ($amount !== null
                    && ($min === null || $amount >= $min)
                    && ($max === null || $amount <= $max)
                ) {
                    $variants[] = $variant;
                }
            }
            if ($variants === []) {
                return null;
            }
            $product['variants'] = $variants;
            $amounts = array_map(
                static fn(array $variant): int => (int) $variant['price']['amount'],
                $variants
            );
            $currency = (string) $variants[0]['price']['currency'];
            $product['price_range'] = [
                'min' => ['amount' => min($amounts), 'currency' => $currency],
                'max' => ['amount' => max($amounts), 'currency' => $currency],
            ];
        }

        return $product;
    }

    /** @param array<string,array<string,mixed>> $merged @param array<string,mixed> $product */
    private function mergeLookupProduct(array &$merged, array $product): void
    {
        $productId = (string) $product['id'];
        if (!isset($merged[$productId])) {
            $merged[$productId] = $product;
            return;
        }

        $variantMap = [];
        foreach ($merged[$productId]['variants'] as $variant) {
            $variantMap[(string) $variant['id']] = $variant;
        }
        foreach ($product['variants'] as $variant) {
            $variantId = (string) $variant['id'];
            if (!isset($variantMap[$variantId])) {
                $variantMap[$variantId] = $variant;
                continue;
            }
            $inputs = array_merge(
                $variantMap[$variantId]['inputs'] ?? [],
                $variant['inputs'] ?? []
            );
            $unique = [];
            foreach ($inputs as $input) {
                $unique[(string) $input['id'] . '|' . (string) ($input['match'] ?? '')] = $input;
            }
            $variantMap[$variantId]['inputs'] = array_values($unique);
        }
        ksort($variantMap, SORT_STRING);
        $merged[$productId]['variants'] = array_values($variantMap);
    }

    private function cursor(int $offset): string
    {
        return rtrim(strtr(base64_encode('o:' . max(0, $offset)), '+/', '-_'), '=');
    }
}

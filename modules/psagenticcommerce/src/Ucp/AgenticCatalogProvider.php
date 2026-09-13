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
        $limit = min(max((int) ($params['limit'] ?? 10), 1), 50);
        $offset = max((int) ($params['offset'] ?? 0), 0);

        $page = $this->source->searchProductIds($this->context, $query, $limit, $offset);
        $products = [];
        foreach ($page['ids'] as $idProduct) {
            $product = $this->buildProduct((int) $idProduct, null);
            if ($product !== null) {
                $products[] = $product;
            }
        }

        $cursor = $page['has_next']
            ? $this->cursor((int) $page['next_offset'])
            : null;

        return new CatalogSearchResult(
            $products,
            (int) $page['total'],
            (bool) $page['has_next'],
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

    private function cursor(int $offset): string
    {
        return rtrim(strtr(base64_encode((string) max(0, $offset)), '+/', '-_'), '=');
    }
}

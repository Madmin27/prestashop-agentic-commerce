<?php

namespace FD\PrismUcp\Catalog;

if (!defined('_PS_VERSION_')) {
    exit;
}

interface CatalogProviderInterface
{
    public function search(array $params): CatalogSearchResult;

    /**
     * @param array<int,string> $ids
     * @param array<string,mixed> $params Optional lookup filters/context.
     */
    public function lookup(array $ids, array $params = []): CatalogLookupResult;
}

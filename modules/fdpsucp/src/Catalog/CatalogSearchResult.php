<?php

namespace FD\PrismUcp\Catalog;

if (!defined('_PS_VERSION_')) {
    exit;
}

final class CatalogSearchResult
{
    /** @param array<int,array<string,mixed>> $products */
    public function __construct(
        public readonly array $products,
        public readonly int $totalCount,
        public readonly bool $hasNextPage
    ) {
    }
}

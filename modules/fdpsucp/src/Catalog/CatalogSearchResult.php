<?php

namespace FD\PrismUcp\Catalog;

if (!defined('_PS_VERSION_')) {
    exit;
}

final class CatalogSearchResult
{
    /** @var array<int,array<string,mixed>> */
    public array $products;
    public int $totalCount;
    public bool $hasNextPage;

    /** @param array<int,array<string,mixed>> $products */
    public function __construct(array $products, int $totalCount, bool $hasNextPage)
    {
        $this->products = $products;
        $this->totalCount = $totalCount;
        $this->hasNextPage = $hasNextPage;
    }
}

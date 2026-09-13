<?php

namespace FD\PrismUcp\Catalog;

if (!defined('_PS_VERSION_')) {
    exit;
}

final class CatalogLookupResult
{
    /** @var array<int,array<string,mixed>> */
    public array $products;

    /** @param array<int,array<string,mixed>> $products */
    public function __construct(array $products)
    {
        $this->products = $products;
    }
}

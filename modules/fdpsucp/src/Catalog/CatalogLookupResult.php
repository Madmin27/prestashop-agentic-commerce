<?php

namespace FD\PrismUcp\Catalog;

if (!defined('_PS_VERSION_')) {
    exit;
}

final class CatalogLookupResult
{
    /** @param array<int,array<string,mixed>> $products */
    public function __construct(public readonly array $products)
    {
    }
}

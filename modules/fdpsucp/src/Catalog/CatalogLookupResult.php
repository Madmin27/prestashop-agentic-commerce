<?php

namespace FD\PrismUcp\Catalog;

if (!defined('_PS_VERSION_')) {
    exit;
}

final class CatalogLookupResult
{
    /** @var array<int,array<string,mixed>> */
    public array $products;
    /** @var array<int,array<string,mixed>> */
    public array $messages;

    /**
     * @param array<int,array<string,mixed>> $products
     * @param array<int,array<string,mixed>> $messages
     */
    public function __construct(array $products, array $messages = [])
    {
        $this->products = $products;
        $this->messages = array_values($messages);
    }
}

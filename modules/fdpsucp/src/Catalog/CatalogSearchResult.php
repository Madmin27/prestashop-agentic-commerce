<?php

namespace FD\PrismUcp\Catalog;

if (!defined('_PS_VERSION_')) {
    exit;
}

final class CatalogSearchResult
{
    /** @var array<int,array<string,mixed>> */
    public array $products;
    public ?int $totalCount;
    public bool $hasNextPage;
    public ?string $cursor;
    /** @var array<int,array<string,mixed>> */
    public array $messages;

    /**
     * @param array<int,array<string,mixed>> $products
     * @param array<int,array<string,mixed>> $messages
     */
    public function __construct(
        array $products,
        ?int $totalCount,
        bool $hasNextPage,
        ?string $cursor = null,
        array $messages = []
    ) {
        $this->products = $products;
        $this->totalCount = $totalCount === null ? null : max(0, $totalCount);
        $this->hasNextPage = $hasNextPage;
        $this->cursor = $hasNextPage ? $cursor : null;
        $this->messages = array_values($messages);

        if ($this->hasNextPage && ($this->cursor === null || $this->cursor === '')) {
            throw new \InvalidArgumentException('A next-page cursor is required when hasNextPage is true.');
        }
    }
}

<?php

namespace PrestaShopAgenticCommerce\Export\AiJson;

final class AiJsonExportBundle implements \JsonSerializable
{
    /** @var array<string,mixed> */
    private array $manifest;
    /** @var array<string,mixed> */
    private array $catalog;
    /** @var array<string,array<string,mixed>> */
    private array $products;

    /**
     * @param array<string,mixed> $manifest
     * @param array<string,mixed> $catalog
     * @param array<string,array<string,mixed>> $products
     */
    public function __construct(array $manifest, array $catalog, array $products)
    {
        ksort($products, SORT_STRING);
        $this->manifest = $manifest;
        $this->catalog = $catalog;
        $this->products = $products;
    }

    /** @return array<string,mixed> */
    public function manifest(): array { return $this->manifest; }
    /** @return array<string,mixed> */
    public function catalog(): array { return $this->catalog; }
    /** @return array<string,array<string,mixed>> */
    public function products(): array { return $this->products; }

    /** @return array<string,mixed> */
    public function jsonSerialize(): array
    {
        return [
            'manifest' => $this->manifest,
            'catalog' => $this->catalog,
            'products' => $this->products,
        ];
    }
}

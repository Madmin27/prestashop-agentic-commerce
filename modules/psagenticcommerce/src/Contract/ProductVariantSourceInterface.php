<?php

namespace PrestaShopAgenticCommerce\Contract;

interface ProductVariantSourceInterface
{
    /**
     * @return array<int,array{id_product:int,id_product_attribute:int}>
     */
    public function listForShop(int $idShop): array;
}

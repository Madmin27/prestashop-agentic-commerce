<?php

namespace PrestaShopAgenticCommerce\Contract;

use PrestaShopAgenticCommerce\Domain\CanonicalProductDTO;

interface PublicCanonicalProductProviderInterface
{
    public function build(
        int $idProduct,
        int $idProductAttribute = 0,
        ?\Context $context = null
    ): CanonicalProductDTO;
}

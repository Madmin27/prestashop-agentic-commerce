<?php

namespace PrestaShopAgenticCommerce\Export\AiJson;

if (!defined('_PS_VERSION_')) {
    exit;
}

final class AiJsonCacheInvalidator
{
    private AiJsonCacheStore $cache;

    public function __construct(AiJsonCacheStore $cache)
    {
        $this->cache = $cache;
    }

    public function invalidateAllActiveShops(): void
    {
        $shopIds = \Shop::getShops(true, null, true);
        foreach ($shopIds as $idShop) {
            $this->cache->invalidateShop((int) $idShop);
        }
    }
}

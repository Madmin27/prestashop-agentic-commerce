<?php

namespace PrestaShopAgenticCommerce\Support;

use PrestaShopAgenticCommerce\Export\AiJson\AiJsonCacheInvalidator;

if (!defined('_PS_VERSION_')) {
    exit;
}

trait AiJsonInvalidationHooks
{
    public function hookActionObjectProductAddAfter(array $params): void { $this->invalidateAiJsonCache(); }
    public function hookActionObjectProductUpdateAfter(array $params): void { $this->invalidateAiJsonCache(); }
    public function hookActionObjectProductDeleteAfter(array $params): void { $this->invalidateAiJsonCache(); }
    public function hookActionObjectCombinationAddAfter(array $params): void { $this->invalidateAiJsonCache(); }
    public function hookActionObjectCombinationUpdateAfter(array $params): void { $this->invalidateAiJsonCache(); }
    public function hookActionObjectCombinationDeleteAfter(array $params): void { $this->invalidateAiJsonCache(); }
    public function hookActionUpdateQuantity(array $params): void { $this->invalidateAiJsonCache(); }
    public function hookActionObjectSpecificPriceAddAfter(array $params): void { $this->invalidateAiJsonCache(); }
    public function hookActionObjectSpecificPriceUpdateAfter(array $params): void { $this->invalidateAiJsonCache(); }
    public function hookActionObjectSpecificPriceDeleteAfter(array $params): void { $this->invalidateAiJsonCache(); }

    private function invalidateAiJsonCache(): void
    {
        (new AiJsonCacheInvalidator($this->createAiJsonCacheStore()))->invalidateAllActiveShops();
    }
}

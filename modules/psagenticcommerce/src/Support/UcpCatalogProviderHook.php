<?php

namespace PrestaShopAgenticCommerce\Support;

use FD\PrismUcp\Catalog\CatalogProviderRegistry;
use PrestaShopAgenticCommerce\Ucp\AgenticCatalogProvider;
use PrestaShopAgenticCommerce\Ucp\UcpCatalogAdapter;
use PrestaShopAgenticCommerce\Ucp\UcpCatalogSource;

if (!defined('_PS_VERSION_')) {
    exit;
}

trait UcpCatalogProviderHook
{
    public function hookActionUcpCollectCatalogProviders(array $params): void
    {
        $registry = $params['registry'] ?? null;
        if (!$registry instanceof CatalogProviderRegistry) {
            return;
        }

        $registry->register(new AgenticCatalogProvider(
            \Context::getContext(),
            $this->createPublicCanonicalBuilder(),
            new UcpCatalogAdapter(),
            new UcpCatalogSource()
        ));
    }
}

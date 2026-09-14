<?php

namespace PrestaShopAgenticCommerce\Install;

if (!defined('_PS_VERSION_')) {
    exit;
}

final class AiJsonHookInstaller
{
    /** @var array<int,string> */
    private const HOOKS = [
        'actionObjectProductAddAfter',
        'actionObjectProductUpdateAfter',
        'actionObjectProductDeleteAfter',
        'actionObjectCombinationAddAfter',
        'actionObjectCombinationUpdateAfter',
        'actionObjectCombinationDeleteAfter',
        'actionUpdateQuantity',
        'actionObjectSpecificPriceAddAfter',
        'actionObjectSpecificPriceUpdateAfter',
        'actionObjectSpecificPriceDeleteAfter',
        'actionUcpCollectCatalogProviders',
    ];

    public static function install(\Module $module): bool
    {
        foreach (self::HOOKS as $hook) {
            if (!$module->registerHook($hook)) {
                return false;
            }
        }
        return true;
    }
}

<?php

namespace PrestaShopAgenticCommerce\Install;

if (!defined('_PS_VERSION_')) {
    exit;
}

final class RuntimeUpgrade
{
    public static function run(\Module $module): bool
    {
        if (!$module->registerHook('moduleRoutes')) {
            return false;
        }
        if (!AiJsonHookInstaller::install($module)) {
            return false;
        }
        return (new WebExposureInstaller())->install();
    }
}

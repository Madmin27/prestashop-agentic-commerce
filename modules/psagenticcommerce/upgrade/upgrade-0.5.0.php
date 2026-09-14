<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_0_5_0($module): bool
{
    return \PrestaShopAgenticCommerce\Install\RuntimeUpgrade::run($module)
        && (new \PrestaShopAgenticCommerce\Install\OpenAiConfigInstaller())->install();
}

<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_0_4_0($module): bool
{
    return \PrestaShopAgenticCommerce\Install\RuntimeUpgrade::run($module);
}

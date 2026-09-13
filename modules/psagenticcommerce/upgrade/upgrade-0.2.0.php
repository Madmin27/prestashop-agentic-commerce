<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_0_2_0($module)
{
    require_once dirname(__DIR__) . '/src/autoload.php';

    return (new \PrestaShopAgenticCommerce\Install\DatabaseInstaller())->upgrade();
}

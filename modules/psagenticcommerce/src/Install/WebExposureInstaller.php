<?php

namespace PrestaShopAgenticCommerce\Install;

use PrestaShopAgenticCommerce\Support\HtaccessRules;

if (!defined('_PS_VERSION_')) {
    exit;
}

final class WebExposureInstaller
{
    public function install(): bool
    {
        $path = rtrim(_PS_ROOT_DIR_, '/\\') . DIRECTORY_SEPARATOR . '.htaccess';
        if (!is_file($path) && method_exists('Tools', 'generateHtaccess')) {
            @\Tools::generateHtaccess();
        }

        $current = is_file($path) ? (string) @file_get_contents($path) : '';
        $updated = HtaccessRules::apply($current);
        if ($updated !== $current && @file_put_contents($path, $updated) === false) {
            \PrestaShopLogger::addLog('[Agentic Commerce] Could not write AI discovery rewrite.', 2);
        }

        return true;
    }

    public function uninstall(): bool
    {
        $path = rtrim(_PS_ROOT_DIR_, '/\\') . DIRECTORY_SEPARATOR . '.htaccess';
        if (!is_file($path)) {
            return true;
        }

        $current = (string) @file_get_contents($path);
        $updated = HtaccessRules::remove($current);
        if ($updated !== $current) {
            @file_put_contents($path, $updated);
        }

        return true;
    }
}

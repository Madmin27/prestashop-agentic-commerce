<?php
/**
 * UCP discovery endpoint — the merchant's UCP profile.
 *
 * Reachable at:
 *   /index.php?fc=module&module=fdpsucp&controller=discovery
 *   /module/fdpsucp/discovery
 *   /.well-known/ucp
 *
 * Catalog capability versioning is advertised independently from the older
 * transactional service version until cart/checkout are upgraded separately.
 */

use FD\PrismUcp\Catalog\CatalogProtocol;
use FD\PrismUcp\Payment\PaymentRegistry;
use FD\PrismUcp\Ucp\Formatter;

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_ . 'fdpsucp/src/autoload.php';

class FdPsUcpDiscoveryModuleFrontController extends ModuleFrontController
{
    public $auth = false;
    public $ssl = true;

    public function initContent()
    {
        $endpoint = rtrim($this->context->link->getBaseLink(), '/') . '/module/fdpsucp/api';
        $storeName = Configuration::get('PS_SHOP_NAME') ?: 'PrestaShop';
        $registry = PaymentRegistry::collect();

        $profile = Formatter::profile($endpoint, $storeName, $registry);
        $base = CatalogProtocol::specBase();
        $profile['ucp']['capabilities']['dev.ucp.shopping.catalog.search'] = [[
            'version' => CatalogProtocol::VERSION,
            'spec' => $base . '/specification/shopping/catalog/search',
            'schema' => $base . '/schemas/shopping/catalog_search.json',
        ]];
        $profile['ucp']['capabilities']['dev.ucp.shopping.catalog.lookup'] = [[
            'version' => CatalogProtocol::VERSION,
            'spec' => $base . '/specification/shopping/catalog/lookup',
            'schema' => $base . '/schemas/shopping/catalog_lookup.json',
        ]];

        header('Content-Type: application/json');
        header('Cache-Control: public, max-age=300');
        header('Access-Control-Allow-Origin: *');
        echo json_encode($profile, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        exit;
    }
}

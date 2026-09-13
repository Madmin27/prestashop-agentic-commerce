<?php
if (!defined('_PS_VERSION_')) { exit; }
require_once __DIR__ . '/src/autoload.php';
use PrestaShopAgenticCommerce\Install\DatabaseInstaller;
final class PsAgenticCommerce extends Module
{
    public function __construct()
    {
        $this->name = 'psagenticcommerce';
        $this->tab = 'others';
        $this->version = '0.1.0';
        $this->author = 'PrestaShop Agentic Commerce Contributors';
        $this->need_instance = 0;
        $this->bootstrap = true;
        $this->ps_versions_compliancy = ['min' => '1.7.8.0', 'max' => _PS_VERSION_];
        parent::__construct();
        $this->displayName = $this->trans('PrestaShop Agentic Commerce', [], 'Modules.Psagenticcommerce.Admin');
        $this->description = $this->trans('Canonical product data, evidence and AI commerce integration for PrestaShop.', [], 'Modules.Psagenticcommerce.Admin');
    }
    public function install(): bool { return parent::install() && (new DatabaseInstaller())->install(); }
    public function uninstall(): bool { return (new DatabaseInstaller())->uninstall() && parent::uninstall(); }
}

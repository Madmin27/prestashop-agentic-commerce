<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/src/autoload.php';

use PrestaShopAgenticCommerce\Builder\CanonicalBuilder;
use PrestaShopAgenticCommerce\Builder\PublicCanonicalBuilder;
use PrestaShopAgenticCommerce\Export\AiJson\AiJsonExporter;
use PrestaShopAgenticCommerce\Install\DatabaseInstaller;
use PrestaShopAgenticCommerce\Pricing\PublicPricingResolver;
use PrestaShopAgenticCommerce\Publication\CanonicalPublicationPolicy;
use PrestaShopAgenticCommerce\Repository\AiMetaRepository;
use PrestaShopAgenticCommerce\Repository\EvidenceRepository;

final class PsAgenticCommerce extends Module
{
    public function __construct()
    {
        $this->name = 'psagenticcommerce';
        $this->tab = 'others';
        $this->version = '0.2.0';
        $this->author = 'PrestaShop Agentic Commerce Contributors';
        $this->need_instance = 0;
        $this->bootstrap = true;
        $this->ps_versions_compliancy = ['min' => '8.0.0.0', 'max' => _PS_VERSION_];

        parent::__construct();

        $this->displayName = $this->trans(
            'PrestaShop Agentic Commerce',
            [],
            'Modules.Psagenticcommerce.Admin'
        );
        $this->description = $this->trans(
            'Canonical product data, evidence and AI commerce integration for PrestaShop.',
            [],
            'Modules.Psagenticcommerce.Admin'
        );
        $this->confirmUninstall = $this->trans(
            'Uninstall the module? Canonical metadata and evidence will be preserved.',
            [],
            'Modules.Psagenticcommerce.Admin'
        );
    }

    public function install(): bool
    {
        if (PHP_VERSION_ID < 80000) {
            return false;
        }

        return parent::install() && (new DatabaseInstaller())->install();
    }

    public function uninstall(): bool
    {
        return (new DatabaseInstaller())->uninstall() && parent::uninstall();
    }

    public function createCanonicalBuilder(): CanonicalBuilder
    {
        return new CanonicalBuilder(
            new AiMetaRepository(),
            new EvidenceRepository()
        );
    }

    public function createPublicCanonicalBuilder(): PublicCanonicalBuilder
    {
        return new PublicCanonicalBuilder(
            $this->createCanonicalBuilder(),
            new PublicPricingResolver()
        );
    }

    public function createAiJsonExporter(): AiJsonExporter
    {
        return new AiJsonExporter(new CanonicalPublicationPolicy());
    }
}

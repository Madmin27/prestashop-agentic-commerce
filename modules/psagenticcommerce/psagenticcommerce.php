<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/src/autoload.php';

use PrestaShopAgenticCommerce\Builder\CanonicalBuilder;
use PrestaShopAgenticCommerce\Builder\PublicCanonicalBuilder;
use PrestaShopAgenticCommerce\Export\AiJson\AiJsonCacheStore;
use PrestaShopAgenticCommerce\Export\AiJson\AiJsonExportCoordinator;
use PrestaShopAgenticCommerce\Export\AiJson\AiJsonExporter;
use PrestaShopAgenticCommerce\Export\OpenAi\OpenAiFeedConfigResolver;
use PrestaShopAgenticCommerce\Export\OpenAi\OpenAiSnapshotJob;
use PrestaShopAgenticCommerce\Export\OpenAi\OpenAiSnapshotWriter;
use PrestaShopAgenticCommerce\Install\AiJsonHookInstaller;
use PrestaShopAgenticCommerce\Install\DatabaseInstaller;
use PrestaShopAgenticCommerce\Install\OpenAiConfigInstaller;
use PrestaShopAgenticCommerce\Install\WebExposureInstaller;
use PrestaShopAgenticCommerce\Pricing\PublicPricingResolver;
use PrestaShopAgenticCommerce\Publication\CanonicalPublicationPolicy;
use PrestaShopAgenticCommerce\Repository\AiMetaRepository;
use PrestaShopAgenticCommerce\Repository\EvidenceRepository;
use PrestaShopAgenticCommerce\Repository\ProductVariantRepository;
use PrestaShopAgenticCommerce\Support\AiJsonInvalidationHooks;
use PrestaShopAgenticCommerce\Support\UcpCatalogProviderHook;

final class PsAgenticCommerce extends Module
{
    use AiJsonInvalidationHooks;
    use UcpCatalogProviderHook;

    public function __construct()
    {
        $this->name = 'psagenticcommerce';
        $this->tab = 'others';
        $this->version = '0.5.0';
        $this->author = 'PrestaShop Agentic Commerce Contributors';
        $this->need_instance = 0;
        $this->bootstrap = true;
        $this->ps_versions_compliancy = ['min' => '8.0.0.0', 'max' => _PS_VERSION_];
        parent::__construct();
        $this->displayName = $this->trans('PrestaShop Agentic Commerce', [], 'Modules.Psagenticcommerce.Admin');
        $this->description = $this->trans('Canonical product data, evidence and AI commerce integration for PrestaShop.', [], 'Modules.Psagenticcommerce.Admin');
        $this->confirmUninstall = $this->trans('Uninstall the module? Canonical metadata and evidence will be preserved.', [], 'Modules.Psagenticcommerce.Admin');
    }

    public function install(): bool
    {
        if (PHP_VERSION_ID < 80000) {
            return false;
        }

        return parent::install()
            && (new DatabaseInstaller())->install()
            && (new OpenAiConfigInstaller())->install()
            && $this->registerHook('moduleRoutes')
            && AiJsonHookInstaller::install($this)
            && (new WebExposureInstaller())->install();
    }

    public function uninstall(): bool
    {
        (new WebExposureInstaller())->uninstall();
        return (new DatabaseInstaller())->uninstall() && parent::uninstall();
    }

    /** @return array<string,array<string,mixed>> */
    public function hookModuleRoutes(array $params): array
    {
        return [
            'module-psagenticcommerce-ai-catalog' => [
                'controller' => 'ai',
                'rule' => 'ai/v1/s{id_shop}/{locale}/{currency}/{country}/catalog.json',
                'keywords' => [
                    'id_shop' => ['regexp' => '[1-9][0-9]*', 'param' => 'id_shop'],
                    'locale' => ['regexp' => '[a-zA-Z0-9_-]+', 'param' => 'locale'],
                    'currency' => ['regexp' => '[A-Za-z]{3}', 'param' => 'currency'],
                    'country' => ['regexp' => '[A-Za-z]{2}', 'param' => 'country'],
                ],
                'params' => ['fc' => 'module', 'module' => 'psagenticcommerce', 'controller' => 'ai', 'ai_resource' => 'catalog'],
            ],
            'module-psagenticcommerce-ai-product' => [
                'controller' => 'ai',
                'rule' => 'ai/v1/s{id_shop}/{locale}/{currency}/{country}/products/{canonical_variant_id}.json',
                'keywords' => [
                    'id_shop' => ['regexp' => '[1-9][0-9]*', 'param' => 'id_shop'],
                    'locale' => ['regexp' => '[a-zA-Z0-9_-]+', 'param' => 'locale'],
                    'currency' => ['regexp' => '[A-Za-z]{3}', 'param' => 'currency'],
                    'country' => ['regexp' => '[A-Za-z]{2}', 'param' => 'country'],
                    'canonical_variant_id' => ['regexp' => 'ps-[0-9]+-[0-9]+-[0-9]+', 'param' => 'canonical_variant_id'],
                ],
                'params' => ['fc' => 'module', 'module' => 'psagenticcommerce', 'controller' => 'ai', 'ai_resource' => 'product'],
            ],
        ];
    }

    public function createCanonicalBuilder(): CanonicalBuilder
    {
        return new CanonicalBuilder(new AiMetaRepository(), new EvidenceRepository());
    }

    public function createPublicCanonicalBuilder(): PublicCanonicalBuilder
    {
        return new PublicCanonicalBuilder($this->createCanonicalBuilder(), new PublicPricingResolver());
    }

    public function createAiJsonExporter(): AiJsonExporter
    {
        return new AiJsonExporter(new CanonicalPublicationPolicy());
    }

    public function createAiJsonExportCoordinator(): AiJsonExportCoordinator
    {
        return new AiJsonExportCoordinator(new ProductVariantRepository(), $this->createPublicCanonicalBuilder(), $this->createAiJsonExporter());
    }

    public function createAiJsonCacheStore(): AiJsonCacheStore
    {
        return new AiJsonCacheStore();
    }

    public function createOpenAiSnapshotJob(): OpenAiSnapshotJob
    {
        return new OpenAiSnapshotJob(
            new ProductVariantRepository(),
            $this->createPublicCanonicalBuilder(),
            new OpenAiFeedConfigResolver(),
            new OpenAiSnapshotWriter()
        );
    }
}

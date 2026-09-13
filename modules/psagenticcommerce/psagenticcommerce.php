<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/src/autoload.php';

use PrestaShopAgenticCommerce\Builder\CanonicalBuilder;
use PrestaShopAgenticCommerce\Builder\PublicCanonicalBuilder;
use PrestaShopAgenticCommerce\Config\OpenAiBackOfficeForm;
use PrestaShopAgenticCommerce\Config\OpenAiSettings;
use PrestaShopAgenticCommerce\Export\AiJson\AiJsonCacheStore;
use PrestaShopAgenticCommerce\Export\AiJson\AiJsonExportCoordinator;
use PrestaShopAgenticCommerce\Export\AiJson\AiJsonExporter;
use PrestaShopAgenticCommerce\Export\OpenAi\CurlSftpClient;
use PrestaShopAgenticCommerce\Export\OpenAi\OpenAiDeliveryAudit;
use PrestaShopAgenticCommerce\Export\OpenAi\OpenAiFeedConfigResolver;
use PrestaShopAgenticCommerce\Export\OpenAi\OpenAiSftpConfigResolver;
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
use PrestaShopAgenticCommerce\Security\SecretCipher;
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
        $this->version = '0.6.0';
        $this->author = 'PrestaShop Agentic Commerce Contributors';
        $this->need_instance = 0;
        $this->bootstrap = true;
        $this->ps_versions_compliancy = ['min' => '8.2.0.0', 'max' => _PS_VERSION_];
        parent::__construct();
        $this->displayName = $this->trans('PrestaShop Agentic Commerce', [], 'Modules.Psagenticcommerce.Admin');
        $this->description = $this->trans('Canonical product data, AI JSON, UCP catalog and OpenAI product feed integration.', [], 'Modules.Psagenticcommerce.Admin');
        $this->confirmUninstall = $this->trans('Uninstall the module? Canonical product metadata will be preserved; OpenAI connection credentials will be removed.', [], 'Modules.Psagenticcommerce.Admin');
    }

    public function isUsingNewTranslationSystem(): bool
    {
        return true;
    }

    public function install(): bool
    {
        if (version_compare(_PS_VERSION_, '8.2.0.0', '<') || PHP_VERSION_ID < 80000) {
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
        (new OpenAiConfigInstaller())->uninstall();
        return (new DatabaseInstaller())->uninstall() && parent::uninstall();
    }

    public function getContent(): string
    {
        $idShop = (int) ($this->context->shop->id ?? 0);
        if ($idShop < 1) {
            return $this->displayError($this->trans('Select a concrete shop before configuring this module.', [], 'Modules.Psagenticcommerce.Admin'));
        }

        $html = '';
        if (Tools::isSubmit('submitPsAgenticOpenAi')) {
            try {
                (new OpenAiSettings())->save($idShop, $_POST);
                $this->saveProtectedOpenAiValues($idShop);
                $html .= $this->displayConfirmation($this->trans('OpenAI settings saved for this shop.', [], 'Modules.Psagenticcommerce.Admin'));
            } catch (Throwable $e) {
                $html .= $this->displayError($e->getMessage());
            }
        }

        if (Tools::isSubmit('submitPsAgenticOpenAiGenerate') || Tools::isSubmit('submitPsAgenticOpenAiSend')) {
            try {
                $send = Tools::isSubmit('submitPsAgenticOpenAiSend');
                $result = $this->runOpenAiSnapshot($idShop, $send);
                $message = sprintf(
                    $this->trans('Snapshot complete: %d exported, %d skipped.', [], 'Modules.Psagenticcommerce.Admin'),
                    (int) $result['exported'],
                    (int) $result['skipped']
                );
                if ($send) {
                    $message .= ' ' . $this->trans('SFTP upload completed.', [], 'Modules.Psagenticcommerce.Admin');
                }
                $html .= $this->displayConfirmation($message);
            } catch (Throwable $e) {
                (new OpenAiDeliveryAudit())->record($idShop, 'failed', ['message' => $e->getMessage()]);
                $html .= $this->displayError($e->getMessage());
            }
        }

        $values = (new OpenAiSettings())->values($idShop);
        $values['PSAGENTIC_OPENAI_SFTP_SECRET_INPUT'] = '';
        $values['PSAGENTIC_OPENAI_SFTP_KEY_SECRET_INPUT'] = '';
        $values['PSAGENTIC_OPENAI_SFTP_CLEAR_SECRET'] = '0';
        $values['PSAGENTIC_OPENAI_SFTP_CLEAR_KEY_SECRET'] = '0';
        $html .= (new OpenAiBackOfficeForm())->render($this, $values);
        $html .= $this->renderOpenAiOperations($idShop);
        return $html;
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
        return new OpenAiSnapshotJob(new ProductVariantRepository(), $this->createPublicCanonicalBuilder(), new OpenAiFeedConfigResolver(), new OpenAiSnapshotWriter());
    }

    private function saveProtectedOpenAiValues(int $idShop): void
    {
        if (!empty($_POST['PSAGENTIC_OPENAI_SFTP_CLEAR_SECRET'])) {
            Configuration::updateValue(OpenAiSftpConfigResolver::AUTH_SECRET, '', false, null, $idShop);
        } else {
            $value = (string) Tools::getValue('PSAGENTIC_OPENAI_SFTP_SECRET_INPUT', '');
            if ($value !== '') {
                Configuration::updateValue(OpenAiSftpConfigResolver::AUTH_SECRET, (new SecretCipher())->encrypt($value), false, null, $idShop);
            }
        }

        if (!empty($_POST['PSAGENTIC_OPENAI_SFTP_CLEAR_KEY_SECRET'])) {
            Configuration::updateValue(OpenAiSftpConfigResolver::KEY_PASSPHRASE, '', false, null, $idShop);
        } else {
            $value = (string) Tools::getValue('PSAGENTIC_OPENAI_SFTP_KEY_SECRET_INPUT', '');
            if ($value !== '') {
                Configuration::updateValue(OpenAiSftpConfigResolver::KEY_PASSPHRASE, (new SecretCipher())->encrypt($value), false, null, $idShop);
            }
        }
    }

    /** @return array<string,mixed> */
    private function runOpenAiSnapshot(int $idShop, bool $send): array
    {
        $targetDirectory = rtrim(_PS_CACHE_DIR_, '/\\') . DIRECTORY_SEPARATOR . 'psagenticcommerce' . DIRECTORY_SEPARATOR . 'openai' . DIRECTORY_SEPARATOR . 's' . $idShop;
        $result = $this->createOpenAiSnapshotJob()->run($idShop, $this->context, $targetDirectory);
        if (!$send) {
            return $result;
        }

        $config = (new OpenAiSftpConfigResolver())->resolve($idShop);
        $upload = (new CurlSftpClient())->upload((string) $result['path'], $config);
        (new OpenAiDeliveryAudit())->record($idShop, 'success', [
            'bytes' => (int) $upload['bytes'],
            'message' => $this->trans('Uploaded snapshot to configured OpenAI SFTP destination.', [], 'Modules.Psagenticcommerce.Admin'),
        ]);
        return $result + ['uploaded_bytes' => (int) $upload['bytes']];
    }

    private function renderOpenAiOperations(int $idShop): string
    {
        $status = (new OpenAiDeliveryAudit())->status($idShop);
        $action = AdminController::$currentIndex . '&configure=' . $this->name . '&token=' . Tools::getAdminTokenLite('AdminModules');
        $cronUrl = $this->context->link->getModuleLink($this->name, 'openaisnapshot', [], true);
        $lastStatus = htmlspecialchars((string) ($status[OpenAiDeliveryAudit::LAST_STATUS] ?? ''), ENT_QUOTES, 'UTF-8');
        $lastAt = htmlspecialchars((string) ($status[OpenAiDeliveryAudit::LAST_AT] ?? ''), ENT_QUOTES, 'UTF-8');
        $lastMessage = htmlspecialchars((string) ($status[OpenAiDeliveryAudit::LAST_MESSAGE] ?? ''), ENT_QUOTES, 'UTF-8');
        $title = htmlspecialchars($this->trans('OpenAI Feed Operations', [], 'Modules.Psagenticcommerce.Admin'), ENT_QUOTES, 'UTF-8');
        $lastDelivery = htmlspecialchars($this->trans('Last delivery:', [], 'Modules.Psagenticcommerce.Admin'), ENT_QUOTES, 'UTF-8');
        $never = htmlspecialchars($this->trans('never', [], 'Modules.Psagenticcommerce.Admin'), ENT_QUOTES, 'UTF-8');
        $generate = htmlspecialchars($this->trans('Generate snapshot', [], 'Modules.Psagenticcommerce.Admin'), ENT_QUOTES, 'UTF-8');
        $generateAndSend = htmlspecialchars($this->trans('Generate & send by SFTP', [], 'Modules.Psagenticcommerce.Admin'), ENT_QUOTES, 'UTF-8');
        $cronEndpoint = htmlspecialchars($this->trans('Cron endpoint:', [], 'Modules.Psagenticcommerce.Admin'), ENT_QUOTES, 'UTF-8');
        $cronHelp = htmlspecialchars($this->trans('Use POST with the X-Agentic-Cron-Token header. The token is intentionally not displayed here.', [], 'Modules.Psagenticcommerce.Admin'), ENT_QUOTES, 'UTF-8');

        return '<div class="panel"><h3><i class="icon-refresh"></i> ' . $title . '</h3>'
            . '<p><strong>' . $lastDelivery . '</strong> ' . ($lastStatus !== '' ? $lastStatus : $never) . ' ' . $lastAt . '</p>'
            . ($lastMessage !== '' ? '<p>' . $lastMessage . '</p>' : '')
            . '<form method="post" action="' . htmlspecialchars($action, ENT_QUOTES, 'UTF-8') . '">'
            . '<button class="btn btn-default" type="submit" name="submitPsAgenticOpenAiGenerate"><i class="icon-file"></i> ' . $generate . '</button> '
            . '<button class="btn btn-primary" type="submit" name="submitPsAgenticOpenAiSend"><i class="icon-cloud-upload"></i> ' . $generateAndSend . '</button>'
            . '</form><hr>'
            . '<p><strong>' . $cronEndpoint . '</strong> <code>' . htmlspecialchars($cronUrl, ENT_QUOTES, 'UTF-8') . '</code></p>'
            . '<p>' . $cronHelp . '</p>'
            . '</div>';
    }
}

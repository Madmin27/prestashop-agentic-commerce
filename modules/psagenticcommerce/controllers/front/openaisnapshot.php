<?php

use PrestaShopAgenticCommerce\Install\OpenAiConfigInstaller;

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_ . 'psagenticcommerce/src/autoload.php';

final class PsAgenticCommerceOpenAiSnapshotModuleFrontController extends ModuleFrontController
{
    public $auth = false;
    public $ssl = true;

    public function initContent()
    {
        header('Content-Type: application/json; charset=utf-8');

        if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
            $this->emit(405, ['error' => 'method_not_allowed']);
        }

        $expected = (string) Configuration::get(OpenAiConfigInstaller::CRON_TOKEN);
        $provided = (string) ($_SERVER['HTTP_X_AGENTIC_CRON_TOKEN'] ?? Tools::getValue('token', ''));
        if ($expected === '' || $provided === '' || !hash_equals($expected, $provided)) {
            $this->emit(401, ['error' => 'unauthorized']);
        }

        $idShop = (int) ($this->context->shop->id ?? 0);
        if ($idShop < 1) {
            $this->emit(400, ['error' => 'invalid_shop']);
        }

        try {
            /** @var PsAgenticCommerce $module */
            $module = $this->module;
            $targetDirectory = rtrim(_PS_CACHE_DIR_, '/\\')
                . DIRECTORY_SEPARATOR . 'psagenticcommerce'
                . DIRECTORY_SEPARATOR . 'openai'
                . DIRECTORY_SEPARATOR . 's' . $idShop;
            $result = $module->createOpenAiSnapshotJob()->run(
                $idShop,
                $this->context,
                $targetDirectory
            );

            // Do not expose the server filesystem path to remote callers.
            unset($result['path']);
            $this->emit(200, ['status' => 'ok'] + $result);
        } catch (\Throwable $e) {
            PrestaShopLogger::addLog(
                '[Agentic Commerce] OpenAI snapshot failed: ' . $e->getMessage(),
                3
            );
            $this->emit(500, ['error' => 'snapshot_failed']);
        }
    }

    /** @param array<string,mixed> $body */
    private function emit(int $status, array $body): void
    {
        http_response_code($status);
        echo json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }
}

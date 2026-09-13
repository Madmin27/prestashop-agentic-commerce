<?php

use PrestaShopAgenticCommerce\Export\AiJson\AiJsonDeliveryService;
use PrestaShopAgenticCommerce\Export\AiJson\RepresentationResolver;
use PrestaShopAgenticCommerce\Http\JsonResponse;

if (!defined('_PS_VERSION_')) {
    exit;
}

final class PsAgenticCommerceAiModuleFrontController extends ModuleFrontController
{
    public $ssl = true;

    public function initContent(): void
    {
        parent::initContent();
        $this->ajax = true;
        $context = Context::getContext();
        $state = null;

        try {
            $resolver = new RepresentationResolver();
            $state = $resolver->apply(
                $context,
                (int) Tools::getValue('id_shop'),
                (string) Tools::getValue('locale'),
                (string) Tools::getValue('currency'),
                (string) Tools::getValue('country')
            );
            $representation = $state['representation'];
            $exporter = $this->module->createAiJsonExporter();
            $delivery = new AiJsonDeliveryService(
                $this->module->createAiJsonExportCoordinator(),
                $exporter,
                $this->module->createAiJsonCacheStore(),
                300
            );
            $baseUrl = $this->baseUrl();
            $ucp = Module::isEnabled('fdpsucp') ? $baseUrl . '/.well-known/ucp' : null;
            $resource = (string) Tools::getValue('ai_resource');

            if ($resource === 'catalog') {
                JsonResponse::send(
                    $delivery->catalog($representation, $context, $baseUrl, $ucp),
                    300
                );
            } elseif ($resource === 'product') {
                $variantId = (string) Tools::getValue('canonical_variant_id');
                $body = $delivery->product($representation, $variantId, $context, $baseUrl, $ucp);
                if ($body === null) {
                    $this->sendNotFound();
                } else {
                    JsonResponse::send($body, 300);
                }
            } else {
                $this->sendNotFound();
            }
        } catch (InvalidArgumentException | DomainException | RuntimeException $e) {
            PrestaShopLogger::addLog('[Agentic Commerce] AI JSON request rejected: ' . $e->getMessage(), 2);
            $this->sendNotFound();
        } catch (Throwable $e) {
            PrestaShopLogger::addLog('[Agentic Commerce] AI JSON error: ' . $e->getMessage(), 3);
            http_response_code(500);
        } finally {
            if ($state !== null) {
                (new RepresentationResolver())->restore($context, $state);
            }
        }

        exit;
    }

    private function sendNotFound(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: public, max-age=60');
        http_response_code(404);
        echo "{\"error\":\"not_found\"}\n";
    }

    private function baseUrl(): string
    {
        if (method_exists($this->context->shop, 'getBaseURL')) {
            return rtrim((string) $this->context->shop->getBaseURL(true), '/');
        }

        return rtrim('https://' . Tools::getShopDomainSsl(), '/') . rtrim(__PS_BASE_URI__, '/');
    }
}

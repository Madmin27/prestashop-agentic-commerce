<?php

use PrestaShopAgenticCommerce\Export\AiJson\RepresentationKey;
use PrestaShopAgenticCommerce\Http\JsonResponse;

if (!defined('_PS_VERSION_')) {
    exit;
}

final class PsAgenticCommerceDiscoveryModuleFrontController extends ModuleFrontController
{
    public $ssl = true;

    public function initContent(): void
    {
        parent::initContent();
        $this->ajax = true;

        try {
            $context = Context::getContext();
            $idShop = (int) $context->shop->id;
            $idLang = (int) Configuration::get('PS_LANG_DEFAULT', null, null, $idShop);
            $idCurrency = (int) Configuration::get('PS_CURRENCY_DEFAULT', null, null, $idShop);
            $idCountry = (int) Configuration::get('PS_COUNTRY_DEFAULT', null, null, $idShop);

            $language = new Language($idLang);
            $currency = new Currency($idCurrency);
            $country = new Country($idCountry, $idLang);
            if (!Validate::isLoadedObject($language)
                || !Validate::isLoadedObject($currency)
                || !Validate::isLoadedObject($country)
            ) {
                throw new RuntimeException('Default public representation is not available.');
            }

            $representation = new RepresentationKey(
                $idShop,
                $idLang,
                (string) $language->iso_code,
                (string) ($language->locale ?: $language->iso_code),
                $idCurrency,
                (string) $currency->iso_code,
                $idCountry,
                (string) $country->iso_code
            );

            $module = $this->module;
            $exporter = $module->createAiJsonExporter();
            $cache = $module->createAiJsonCacheStore();
            $cacheKey = $representation->catalogCacheKey() . ':manifest';
            $body = $cache->getForShop($cacheKey, 300, $idShop);

            if ($body === null) {
                $ucp = Module::isEnabled('fdpsucp')
                    ? rtrim($this->baseUrl(), '/') . '/.well-known/ucp'
                    : null;
                $document = $exporter->discoveryDocument(
                    $representation,
                    $this->baseUrl(),
                    null,
                    $ucp
                );
                $body = $exporter->encode($document);
                $cache->put($cacheKey, $body);
            }

            JsonResponse::send($body, 300);
        } catch (Throwable $e) {
            PrestaShopLogger::addLog('[Agentic Commerce] Discovery error: ' . $e->getMessage(), 3);
            http_response_code(500);
        }

        exit;
    }

    private function baseUrl(): string
    {
        if (method_exists($this->context->shop, 'getBaseURL')) {
            return rtrim((string) $this->context->shop->getBaseURL(true), '/');
        }

        return rtrim('https://' . Tools::getShopDomainSsl(), '/') . __PS_BASE_URI__;
    }
}

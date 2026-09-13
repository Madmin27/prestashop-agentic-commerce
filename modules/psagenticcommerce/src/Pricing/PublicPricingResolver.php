<?php

namespace PrestaShopAgenticCommerce\Pricing;

if (!defined('_PS_VERSION_')) {
    exit;
}

final class PublicPricingResolver
{
    /** @return array{id_country:int,country:string,country_object:\Country} */
    public function publicCountry(\Context $context): array
    {
        $idShop = (int) ($context->shop->id ?? 0);
        $idCountry = (int) \Configuration::get('PS_COUNTRY_DEFAULT', null, null, $idShop > 0 ? $idShop : null);
        $publicCountry = new \Country($idCountry, (int) ($context->language->id ?? 0));
        if (!\Validate::isLoadedObject($publicCountry)) {
            throw new \RuntimeException('Default shop country is not available for public pricing.');
        }

        $countryIso = strtoupper((string) $publicCountry->iso_code);
        if (!preg_match('/^[A-Z]{2}$/', $countryIso)) {
            throw new \RuntimeException('Default shop country has an invalid ISO code.');
        }

        return [
            'id_country' => $idCountry,
            'country' => $countryIso,
            'country_object' => $publicCountry,
        ];
    }

    public function resolve(
        int $idProduct,
        int $idProductAttribute,
        float $quantity,
        \Context $context
    ): PublicPriceResult {
        if ($idProduct < 1 || $idProductAttribute < 0 || $quantity <= 0) {
            throw new \InvalidArgumentException('Invalid public pricing request.');
        }

        $public = $this->publicCountry($context);
        $originalCustomer = $context->customer;
        $originalCart = $context->cart;
        $originalCountry = $context->country ?? null;
        $originalTaxCalculationMethod = \Product::$_taxCalculationMethod;
        $originalCustomerId = \Validate::isLoadedObject($originalCustomer)
            ? (int) $originalCustomer->id
            : null;

        try {
            $context->customer = new \Customer();
            $context->cart = new \Cart();
            $context->country = $public['country_object'];
            \Product::$_taxCalculationMethod = null;
            \Product::initPricesComputation(null);

            $price = (float) \Product::getPriceStatic(
                $idProduct,
                true,
                $idProductAttribute > 0 ? $idProductAttribute : null,
                6,
                null,
                false,
                true,
                $quantity,
                false,
                0,
                0
            );

            return new PublicPriceResult(
                $price,
                strtoupper((string) $context->currency->iso_code),
                $public['id_country'],
                $public['country'],
                gmdate('c')
            );
        } finally {
            $context->customer = $originalCustomer;
            $context->cart = $originalCart;
            $context->country = $originalCountry;
            \Product::$_taxCalculationMethod = $originalTaxCalculationMethod;
            \Product::initPricesComputation($originalCustomerId);
        }
    }
}

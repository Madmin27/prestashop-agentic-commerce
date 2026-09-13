<?php

namespace PrestaShopAgenticCommerce\Pricing;

if (!defined('_PS_VERSION_')) {
    exit;
}

final class PublicPricingResolver
{
    public function resolve(
        int $idProduct,
        int $idProductAttribute,
        float $quantity,
        \Context $context
    ): PublicPriceResult {
        if ($idProduct < 1 || $idProductAttribute < 0 || $quantity <= 0) {
            throw new \InvalidArgumentException('Invalid public pricing request.');
        }

        $originalCustomer = $context->customer;
        $originalCart = $context->cart;
        $originalTaxCalculationMethod = \Product::$_taxCalculationMethod;

        try {
            // Force anonymous catalog semantics. Group::getCurrent() resolves to
            // PS_UNIDENTIFIED_GROUP when the current customer is not loaded.
            $context->customer = new \Customer();
            $context->cart = new \Cart();
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
                gmdate('c')
            );
        } finally {
            $context->customer = $originalCustomer;
            $context->cart = $originalCart;
            \Product::$_taxCalculationMethod = $originalTaxCalculationMethod;
        }
    }
}

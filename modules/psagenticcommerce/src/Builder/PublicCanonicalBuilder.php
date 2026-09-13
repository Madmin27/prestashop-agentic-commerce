<?php

namespace PrestaShopAgenticCommerce\Builder;

use PrestaShopAgenticCommerce\Domain\CanonicalProductDTO;
use PrestaShopAgenticCommerce\Pricing\PublicPricingResolver;

if (!defined('_PS_VERSION_')) {
    exit;
}

final class PublicCanonicalBuilder
{
    private CanonicalBuilder $canonicalBuilder;
    private PublicPricingResolver $pricingResolver;

    public function __construct(
        CanonicalBuilder $canonicalBuilder,
        PublicPricingResolver $pricingResolver
    ) {
        $this->canonicalBuilder = $canonicalBuilder;
        $this->pricingResolver = $pricingResolver;
    }

    public function build(
        int $idProduct,
        int $idProductAttribute = 0,
        ?\Context $context = null
    ): CanonicalProductDTO {
        $context = $context ?: \Context::getContext();
        $runtime = $this->canonicalBuilder->build($idProduct, $idProductAttribute, $context);
        $data = $runtime->toArray();

        $data['context']['pricing_context'] = 'public_catalog_tax_included';
        $data['commercial']['realtime_required'] = true;

        if (($data['commercial']['show_price'] ?? false) !== true) {
            $data['commercial']['price'] = null;
            return new CanonicalProductDTO($data);
        }

        $quantity = (float) ($data['commercial']['min_order_quantity'] ?? 1);
        if ($quantity <= 0) {
            $quantity = 1.0;
        }

        $price = $this->pricingResolver->resolve(
            $idProduct,
            $idProductAttribute,
            $quantity,
            $context
        );

        $data['context']['id_country'] = $price->idCountry();
        $data['context']['country'] = $price->country();
        $data['commercial']['currency'] = $price->currency();
        $data['commercial']['price'] = $price->price();
        $data['commercial']['as_of'] = $price->asOf();

        return new CanonicalProductDTO($data);
    }
}

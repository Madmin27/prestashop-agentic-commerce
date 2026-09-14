<?php

namespace PrestaShopAgenticCommerce\Export\AiJson;

use PrestaShopAgenticCommerce\Contract\ProductVariantSourceInterface;
use PrestaShopAgenticCommerce\Contract\PublicCanonicalProductProviderInterface;

final class AiJsonExportCoordinator
{
    private ProductVariantSourceInterface $variantSource;
    private PublicCanonicalProductProviderInterface $productProvider;
    private AiJsonExporter $exporter;

    public function __construct(
        ProductVariantSourceInterface $variantSource,
        PublicCanonicalProductProviderInterface $productProvider,
        AiJsonExporter $exporter
    ) {
        $this->variantSource = $variantSource;
        $this->productProvider = $productProvider;
        $this->exporter = $exporter;
    }

    public function build(
        RepresentationKey $representation,
        \Context $context,
        string $baseUrl,
        ?string $generatedAt = null,
        ?string $ucpDiscoveryUrl = null
    ): AiJsonExportBundle {
        $this->assertContextMatchesRepresentation($representation, $context);
        $generatedAt = $generatedAt ?: gmdate('c');

        $dtos = [];
        $products = [];
        foreach ($this->variantSource->listForShop($representation->idShop()) as $row) {
            $dto = $this->productProvider->build(
                (int) $row['id_product'],
                (int) $row['id_product_attribute'],
                $context
            );

            if (!$representation->equals(RepresentationKey::fromCanonical($dto))) {
                throw new \DomainException('Canonical product was built for a different representation.');
            }

            try {
                $document = $this->exporter->productDocument($dto);
            } catch (\DomainException $e) {
                // Visibility can change between enumeration and build. A product
                // that is no longer public is omitted rather than aborting the
                // whole export. Other build/validation failures are not swallowed.
                if (($dto->toArray()['commercial']['visibility'] ?? 'none') === 'none') {
                    continue;
                }
                throw $e;
            }

            $variantId = (string) $document['canonical_variant_id'];
            $dtos[] = $dto;
            $products[$variantId] = $document;
        }

        $catalog = $this->exporter->catalogDocument(
            $representation,
            $dtos,
            $baseUrl,
            $generatedAt
        );
        $manifest = $this->exporter->discoveryDocument(
            $representation,
            $baseUrl,
            $generatedAt,
            $ucpDiscoveryUrl
        );

        return new AiJsonExportBundle($manifest, $catalog, $products);
    }

    private function assertContextMatchesRepresentation(
        RepresentationKey $representation,
        \Context $context
    ): void {
        if ((int) ($context->shop->id ?? 0) !== $representation->idShop()
            || (int) ($context->language->id ?? 0) !== $representation->idLanguage()
            || (int) ($context->currency->id ?? 0) !== $representation->idCurrency()
            || strtoupper((string) ($context->currency->iso_code ?? '')) !== $representation->currency()
        ) {
            throw new \InvalidArgumentException('PrestaShop context does not match the requested AI representation.');
        }
    }
}

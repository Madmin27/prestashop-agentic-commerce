<?php

namespace PrestaShopAgenticCommerce\Export\OpenAi;

use PrestaShopAgenticCommerce\Contract\ProductVariantSourceInterface;
use PrestaShopAgenticCommerce\Contract\PublicCanonicalProductProviderInterface;

if (!defined('_PS_VERSION_')) {
    exit;
}

final class OpenAiSnapshotCoordinator
{
    public function __construct(
        private ProductVariantSourceInterface $variantSource,
        private PublicCanonicalProductProviderInterface $productProvider,
        private OpenAiProductFeedExporter $exporter
    ) {
    }

    public function build(int $idShop, \Context $context): OpenAiSnapshotResult
    {
        if ($idShop < 1 || (int) ($context->shop->id ?? 0) !== $idShop) {
            throw new \InvalidArgumentException('OpenAI snapshot shop does not match PrestaShop context.');
        }

        $lines = [];
        $skipped = [];
        foreach ($this->variantSource->listForShop($idShop) as $source) {
            $idProduct = (int) ($source['id_product'] ?? 0);
            $idProductAttribute = (int) ($source['id_product_attribute'] ?? 0);

            try {
                $dto = $this->productProvider->build($idProduct, $idProductAttribute, $context);
                $lines[] = json_encode(
                    $this->exporter->row($dto),
                    JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
                );
            } catch (\Exception $e) {
                $skipped[] = [
                    'id_product' => $idProduct,
                    'id_product_attribute' => $idProductAttribute,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return new OpenAiSnapshotResult(
            $lines === [] ? '' : implode("\n", $lines) . "\n",
            count($lines),
            $skipped
        );
    }
}

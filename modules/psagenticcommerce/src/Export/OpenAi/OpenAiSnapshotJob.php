<?php

namespace PrestaShopAgenticCommerce\Export\OpenAi;

use PrestaShopAgenticCommerce\Contract\ProductVariantSourceInterface;
use PrestaShopAgenticCommerce\Contract\PublicCanonicalProductProviderInterface;

if (!defined('_PS_VERSION_')) {
    exit;
}

final class OpenAiSnapshotJob
{
    public function __construct(
        private ProductVariantSourceInterface $variantSource,
        private PublicCanonicalProductProviderInterface $productProvider,
        private OpenAiFeedConfigResolver $configResolver,
        private OpenAiSnapshotWriter $writer
    ) {
    }

    /** @return array{exported:int,skipped:int,path:string,errors:array<int,array{id_product:int,id_product_attribute:int,error:string}>} */
    public function run(int $idShop, \Context $context, string $targetDirectory): array
    {
        if ($idShop < 1 || (int) ($context->shop->id ?? 0) !== $idShop) {
            throw new \InvalidArgumentException('OpenAI snapshot shop does not match PrestaShop context.');
        }

        $config = $this->configResolver->resolve($idShop);
        $exporter = new OpenAiProductFeedExporter($config);
        $coordinator = new OpenAiSnapshotCoordinator(
            $this->variantSource,
            $this->productProvider,
            $exporter
        );
        $snapshot = $coordinator->build($idShop, $context);

        if (!is_dir($targetDirectory) && !@mkdir($targetDirectory, 0750, true) && !is_dir($targetDirectory)) {
            throw new \RuntimeException('Unable to create OpenAI snapshot directory.');
        }

        $path = rtrim($targetDirectory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'products.jsonl.gz';
        $this->writer->writeGzip($snapshot, $path);

        return [
            'exported' => $snapshot->exportedCount(),
            'skipped' => count($snapshot->skipped()),
            'path' => $path,
            'errors' => $snapshot->skipped(),
        ];
    }
}

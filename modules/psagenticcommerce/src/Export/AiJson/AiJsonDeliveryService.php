<?php

namespace PrestaShopAgenticCommerce\Export\AiJson;

if (!defined('_PS_VERSION_')) {
    exit;
}

final class AiJsonDeliveryService
{
    private AiJsonExportCoordinator $coordinator;
    private AiJsonExporter $exporter;
    private AiJsonCacheStore $cache;
    private int $ttl;

    public function __construct(
        AiJsonExportCoordinator $coordinator,
        AiJsonExporter $exporter,
        AiJsonCacheStore $cache,
        int $ttl = 300
    ) {
        if ($ttl < 1) {
            throw new \InvalidArgumentException('AI JSON delivery TTL must be positive.');
        }
        $this->coordinator = $coordinator;
        $this->exporter = $exporter;
        $this->cache = $cache;
        $this->ttl = $ttl;
    }

    public function manifest(
        RepresentationKey $representation,
        \Context $context,
        string $baseUrl,
        ?string $ucpDiscoveryUrl = null
    ): string {
        $key = $representation->catalogCacheKey() . ':manifest';
        $body = $this->cache->getForShop($key, $this->ttl, $representation->idShop());
        if ($body !== null) {
            return $body;
        }

        $document = $this->exporter->discoveryDocument(
            $representation,
            $baseUrl,
            null,
            $ucpDiscoveryUrl
        );
        $body = $this->exporter->encode($document);
        $this->cache->putForShop($key, $body, $representation->idShop());
        return $body;
    }

    public function catalog(
        RepresentationKey $representation,
        \Context $context,
        string $baseUrl,
        ?string $ucpDiscoveryUrl = null
    ): string {
        $key = $representation->catalogCacheKey();
        $body = $this->cache->getForShop($key, $this->ttl, $representation->idShop());
        if ($body !== null) {
            return $body;
        }

        $this->warm($representation, $context, $baseUrl, $ucpDiscoveryUrl);
        $body = $this->cache->getForShop($key, $this->ttl, $representation->idShop());
        if ($body === null) {
            throw new \RuntimeException('AI catalog cache was not generated.');
        }
        return $body;
    }

    public function product(
        RepresentationKey $representation,
        string $canonicalVariantId,
        \Context $context,
        string $baseUrl,
        ?string $ucpDiscoveryUrl = null
    ): ?string {
        $key = $representation->productCacheKey($canonicalVariantId);
        $body = $this->cache->getForShop($key, $this->ttl, $representation->idShop());
        if ($body !== null) {
            return $body;
        }

        $this->warm($representation, $context, $baseUrl, $ucpDiscoveryUrl);
        return $this->cache->getForShop($key, $this->ttl, $representation->idShop());
    }

    public function invalidateShop(int $idShop): void
    {
        $this->cache->invalidateShop($idShop);
    }

    private function warm(
        RepresentationKey $representation,
        \Context $context,
        string $baseUrl,
        ?string $ucpDiscoveryUrl
    ): void {
        $bundle = $this->coordinator->build(
            $representation,
            $context,
            $baseUrl,
            null,
            $ucpDiscoveryUrl
        );
        $idShop = $representation->idShop();

        foreach ($bundle->products() as $variantId => $document) {
            $this->cache->putForShop(
                $representation->productCacheKey((string) $variantId),
                $this->exporter->encode($document),
                $idShop
            );
        }
        $this->cache->putForShop(
            $representation->catalogCacheKey(),
            $this->exporter->encode($bundle->catalog()),
            $idShop
        );
        $this->cache->putForShop(
            $representation->catalogCacheKey() . ':manifest',
            $this->exporter->encode($bundle->manifest()),
            $idShop
        );
    }
}

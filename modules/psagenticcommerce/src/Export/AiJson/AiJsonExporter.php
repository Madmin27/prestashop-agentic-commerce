<?php

namespace PrestaShopAgenticCommerce\Export\AiJson;

use PrestaShopAgenticCommerce\Domain\CanonicalProductDTO;
use PrestaShopAgenticCommerce\Publication\CanonicalPublicationPolicy;

final class AiJsonExporter
{
    private CanonicalPublicationPolicy $publicationPolicy;

    public function __construct(?CanonicalPublicationPolicy $publicationPolicy = null)
    {
        $this->publicationPolicy = $publicationPolicy ?: new CanonicalPublicationPolicy();
    }

    /** @return array<string,mixed> */
    public function productDocument(CanonicalProductDTO $dto): array
    {
        $data = $this->publicationPolicy->prepare($dto);
        if (!$this->isPublishable($data)) {
            throw new \DomainException('Product is not publicly visible for AI export.');
        }

        return $data;
    }

    /**
     * @param array<int,CanonicalProductDTO> $products
     * @return array<string,mixed>
     */
    public function catalogDocument(
        RepresentationKey $representation,
        array $products,
        string $baseUrl,
        ?string $generatedAt = null
    ): array {
        $baseUrl = $this->normalizeBaseUrl($baseUrl);
        $generatedAt = $this->normalizeTimestamp($generatedAt);
        $items = [];

        foreach ($products as $dto) {
            if (!$dto instanceof CanonicalProductDTO) {
                throw new \InvalidArgumentException('Catalog products must be CanonicalProductDTO instances.');
            }

            $productRepresentation = RepresentationKey::fromCanonical($dto);
            if (!$representation->equals($productRepresentation)) {
                throw new \DomainException('Mixed representation contexts are not allowed in one AI catalog.');
            }

            $data = $this->publicationPolicy->prepare($dto);
            if (!$this->isPublishable($data)) {
                continue;
            }

            $variantId = (string) $data['canonical_variant_id'];
            $items[] = [
                'canonical_variant_id' => $variantId,
                'product_group_id' => (string) $data['product_group_id'],
                'title' => (string) $data['identity']['title'],
                'category_type' => (string) $data['category_type'],
                'sku' => $data['identity']['sku'] ?? null,
                'gtin' => $data['identity']['gtin'] ?? null,
                'availability' => (string) $data['commercial']['availability'],
                'orderable' => (bool) $data['commercial']['orderable'],
                'price' => $data['commercial']['price'],
                'currency' => (string) $data['commercial']['currency'],
                'sale_unit' => (string) $data['commercial']['sale_unit'],
                'updated_at' => $data['timestamps']['source_updated_at']
                    ?? $data['timestamps']['canonical_generated_at'],
                'canonical_web' => (string) $data['links']['canonical_web'],
                'image' => $data['links']['image'] ?? null,
                'url' => $this->url($baseUrl, $representation->productPath($variantId)),
            ];
        }

        usort($items, static function (array $left, array $right): int {
            return $left['canonical_variant_id'] <=> $right['canonical_variant_id'];
        });

        return [
            'schema_version' => '1.0',
            'document_type' => 'ai_catalog',
            'generated_at' => $generatedAt,
            'representation' => $representation->toArray(),
            'product_count' => count($items),
            'products' => $items,
        ];
    }

    /** @return array<string,mixed> */
    public function discoveryDocument(
        RepresentationKey $representation,
        string $baseUrl,
        ?string $generatedAt = null,
        ?string $ucpDiscoveryUrl = null
    ): array {
        $baseUrl = $this->normalizeBaseUrl($baseUrl);
        $generatedAt = $this->normalizeTimestamp($generatedAt);

        $endpoints = [
            'catalog' => $this->url($baseUrl, $representation->catalogPath()),
            'product_pattern' => $this->url($baseUrl, $representation->productPatternPath()),
        ];
        if ($ucpDiscoveryUrl !== null) {
            if (filter_var($ucpDiscoveryUrl, FILTER_VALIDATE_URL) === false) {
                throw new \InvalidArgumentException('Invalid UCP discovery URL.');
            }
            $endpoints['ucp_discovery'] = $ucpDiscoveryUrl;
        }

        return [
            'schema_version' => '1.0',
            'document_type' => 'ai_catalog_manifest',
            'generated_at' => $generatedAt,
            'representation' => $representation->toArray(),
            'cache_key' => $representation->catalogCacheKey(),
            'endpoints' => $endpoints,
        ];
    }

    /** @param array<string,mixed> $document */
    public function encode(array $document): string
    {
        return json_encode(
            $document,
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
        ) . "\n";
    }

    /** @param array<string,mixed> $data */
    private function isPublishable(array $data): bool
    {
        return ($data['commercial']['visibility'] ?? 'none') !== 'none';
    }

    private function normalizeBaseUrl(string $baseUrl): string
    {
        $baseUrl = rtrim(trim($baseUrl), '/');
        if (filter_var($baseUrl, FILTER_VALIDATE_URL) === false) {
            throw new \InvalidArgumentException('A valid absolute base URL is required.');
        }

        return $baseUrl;
    }

    private function normalizeTimestamp(?string $timestamp): string
    {
        $timestamp = $timestamp ?: gmdate('c');
        if (strtotime($timestamp) === false) {
            throw new \InvalidArgumentException('Invalid export timestamp.');
        }

        return $timestamp;
    }

    private function url(string $baseUrl, string $path): string
    {
        return $baseUrl . '/' . ltrim($path, '/');
    }
}

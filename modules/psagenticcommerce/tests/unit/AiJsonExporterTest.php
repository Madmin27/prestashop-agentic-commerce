<?php

use PHPUnit\Framework\TestCase;
use PrestaShopAgenticCommerce\Domain\CanonicalProductDTO;
use PrestaShopAgenticCommerce\Export\AiJson\AiJsonExporter;
use PrestaShopAgenticCommerce\Export\AiJson\RepresentationKey;

final class AiJsonExporterTest extends TestCase
{
    public function testCatalogIsSortedAndSkipsHiddenProducts(): void
    {
        $exporter = new AiJsonExporter();
        $representation = new RepresentationKey(1, 1, 'tr', 'tr-TR', 1, 'TRY', 224, 'TR');

        $visibleB = new CanonicalProductDTO($this->payload('ps-1-2-0', 'B', 'both'));
        $hidden = new CanonicalProductDTO($this->payload('ps-1-3-0', 'Hidden', 'none'));
        $visibleA = new CanonicalProductDTO($this->payload('ps-1-1-0', 'A', 'catalog'));

        $catalog = $exporter->catalogDocument(
            $representation,
            [$visibleB, $hidden, $visibleA],
            'https://shop.example',
            '2026-09-13T12:00:00Z'
        );

        self::assertSame(2, $catalog['product_count']);
        self::assertSame('ps-1-1-0', $catalog['products'][0]['canonical_variant_id']);
        self::assertSame('ps-1-2-0', $catalog['products'][1]['canonical_variant_id']);
        self::assertSame(
            'https://shop.example/ai/v1/s1/tr-TR/TRY/TR/products/ps-1-1-0.json',
            $catalog['products'][0]['url']
        );
    }

    public function testMixedRepresentationCatalogIsRejected(): void
    {
        $exporter = new AiJsonExporter();
        $representation = new RepresentationKey(1, 1, 'tr', 'tr-TR', 1, 'TRY', 224, 'TR');
        $other = $this->payload('ps-1-2-0', 'USD product', 'both');
        $other['context']['id_currency'] = 2;
        $other['context']['currency'] = 'USD';
        $other['commercial']['currency'] = 'USD';

        $this->expectException(\DomainException::class);
        $exporter->catalogDocument(
            $representation,
            [new CanonicalProductDTO($other)],
            'https://shop.example',
            '2026-09-13T12:00:00Z'
        );
    }

    public function testProductDocumentUsesPublicationPolicy(): void
    {
        $exporter = new AiJsonExporter();
        $payload = $this->payload('ps-1-1-0', 'A', 'both');
        $payload['commercial']['stock_quantity'] = 99;

        $document = $exporter->productDocument(new CanonicalProductDTO($payload));

        self::assertNull($document['commercial']['stock_quantity']);
        self::assertSame(10.0, $document['commercial']['price']);
    }

    public function testDiscoveryDocumentUsesRepresentationPaths(): void
    {
        $exporter = new AiJsonExporter();
        $representation = new RepresentationKey(1, 1, 'tr', 'tr-TR', 1, 'TRY', 224, 'TR');

        $manifest = $exporter->discoveryDocument(
            $representation,
            'https://shop.example/',
            '2026-09-13T12:00:00Z',
            'https://shop.example/.well-known/ucp'
        );

        self::assertSame(
            'https://shop.example/ai/v1/s1/tr-TR/TRY/TR/catalog.json',
            $manifest['endpoints']['catalog']
        );
        self::assertSame(
            'https://shop.example/ai/v1/s1/tr-TR/TRY/TR/products/{canonical_variant_id}.json',
            $manifest['endpoints']['product_pattern']
        );
        self::assertSame('https://shop.example/.well-known/ucp', $manifest['endpoints']['ucp_discovery']);
    }

    private function payload(string $variantId, string $title, string $visibility): array
    {
        return [
            'schema_version' => '1.0',
            'canonical_variant_id' => $variantId,
            'product_group_id' => 'ps-1-1',
            'category_type' => 'general',
            'source' => [
                'system' => 'prestashop',
                'id_shop' => 1,
                'id_product' => 1,
                'id_product_attribute' => 0,
            ],
            'context' => [
                'id_language' => 1,
                'language' => 'tr',
                'locale' => 'tr-TR',
                'id_currency' => 1,
                'currency' => 'TRY',
                'id_country' => 224,
                'country' => 'TR',
                'pricing_context' => 'public_catalog_tax_included',
            ],
            'timestamps' => [
                'source_updated_at' => '2026-09-13T10:00:00Z',
                'canonical_generated_at' => '2026-09-13T10:01:00Z',
            ],
            'identity' => [
                'sku' => null,
                'gtin' => null,
                'mpn' => null,
                'brand' => null,
                'title' => $title,
            ],
            'content' => ['short_description' => '', 'description' => ''],
            'media' => [],
            'variant_dimensions' => [],
            'commercial' => [
                'currency' => 'TRY',
                'price' => 10.0,
                'sale_unit' => 'piece',
                'min_order_quantity' => 1,
                'availability' => 'in_stock',
                'stock_quantity' => 5,
                'orderable' => true,
                'show_price' => true,
                'visibility' => $visibility,
                'as_of' => '2026-09-13T10:01:00Z',
                'realtime_required' => true,
            ],
            'verified_specs' => [],
            'declared_specs' => [],
            'derived_properties' => [],
            'suitability' => [
                'recommended_for' => [],
                'conditionally_suitable_for' => [],
                'not_recommended_for' => [],
            ],
            'evidence' => [],
            'links' => [
                'canonical_web' => 'https://shop.example/p/' . rawurlencode($variantId),
                'realtime_api' => null,
                'direct_cart' => null,
                'image' => null,
            ],
        ];
    }
}

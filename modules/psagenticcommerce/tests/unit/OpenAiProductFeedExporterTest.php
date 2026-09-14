<?php

use PHPUnit\Framework\TestCase;
use PrestaShopAgenticCommerce\Domain\CanonicalProductDTO;
use PrestaShopAgenticCommerce\Export\OpenAi\OpenAiFeedConfig;
use PrestaShopAgenticCommerce\Export\OpenAi\OpenAiProductFeedExporter;

final class OpenAiProductFeedExporterTest extends TestCase
{
    public function testMapsCanonicalVariantToStableFeedRow(): void
    {
        $exporter = new OpenAiProductFeedExporter($this->config());
        $row = $exporter->row(new CanonicalProductDTO($this->payload()));

        self::assertTrue($row['is_eligible_search']);
        self::assertFalse($row['is_eligible_checkout']);
        self::assertSame('ps-1-10-2', $row['item_id']);
        self::assertSame('Test Product', $row['title']);
        self::assertSame('Public description', $row['description']);
        self::assertSame('12.34 TRY', $row['price']);
        self::assertSame('in_stock', $row['availability']);
        self::assertSame('1234567890123', $row['gtin']);
        self::assertSame('MTR-8MM', $row['mpn']);
        self::assertSame('natural_jute', $row['material']);
        self::assertSame('ps-1-10', $row['group_id']);
        self::assertTrue($row['listing_has_variations']);
        self::assertSame(['Color' => 'Natural', 'Diameter' => '8 mm'], $row['variant_dict']);
        self::assertSame(['TR'], $row['target_countries']);
    }

    public function testUsesConfiguredBrandFallback(): void
    {
        $payload = $this->payload();
        $payload['identity']['brand'] = null;

        $row = (new OpenAiProductFeedExporter($this->config()))
            ->row(new CanonicalProductDTO($payload));

        self::assertSame('Default Brand', $row['brand']);
    }

    public function testFailsClosedWithoutImage(): void
    {
        $payload = $this->payload();
        $payload['media'] = [];
        $payload['links']['image'] = null;

        $this->expectException(InvalidArgumentException::class);
        (new OpenAiProductFeedExporter($this->config()))
            ->row(new CanonicalProductDTO($payload));
    }

    public function testFailsClosedWithoutPublicPrice(): void
    {
        $payload = $this->payload();
        $payload['commercial']['price'] = null;

        $this->expectException(InvalidArgumentException::class);
        (new OpenAiProductFeedExporter($this->config()))
            ->row(new CanonicalProductDTO($payload));
    }

    public function testJsonlEmitsOneObjectPerLine(): void
    {
        $dto = new CanonicalProductDTO($this->payload());
        $jsonl = (new OpenAiProductFeedExporter($this->config()))->jsonl([$dto, $dto]);
        $lines = array_values(array_filter(explode("\n", $jsonl), 'strlen'));

        self::assertCount(2, $lines);
        self::assertSame('ps-1-10-2', json_decode($lines[0], true, 512, JSON_THROW_ON_ERROR)['item_id']);
    }

    public function testCheckoutConfigRequiresPolicyUrls(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new OpenAiFeedConfig(
            'Seller',
            'https://example.test',
            'https://example.test/returns',
            ['TR'],
            'TR',
            'Brand',
            true,
            true
        );
    }

    private function config(): OpenAiFeedConfig
    {
        return new OpenAiFeedConfig(
            'Example Seller',
            'https://example.test',
            'https://example.test/returns',
            ['TR'],
            'TR',
            'Default Brand'
        );
    }

    /** @return array<string,mixed> */
    private function payload(): array
    {
        return [
            'schema_version' => '1.0',
            'canonical_variant_id' => 'ps-1-10-2',
            'product_group_id' => 'ps-1-10',
            'category_type' => 'technical_cordage',
            'source' => [
                'system' => 'prestashop',
                'id_shop' => 1,
                'id_product' => 10,
                'id_product_attribute' => 2,
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
                'sku' => 'JUTE-8MM',
                'gtin' => '123-4567 890123',
                'mpn' => 'MTR-8MM',
                'brand' => 'Test Brand',
                'title' => 'Test Product',
            ],
            'content' => [
                'short_description' => 'Short',
                'description' => 'Public description',
            ],
            'media' => [[
                'type' => 'image',
                'role' => 'primary',
                'url' => 'https://example.test/image.jpg',
                'alt' => 'Test Product',
            ]],
            'variant_dimensions' => [
                ['group_id' => 2, 'group_name' => 'Diameter', 'attribute_id' => 20, 'value' => '8 mm'],
                ['group_id' => 1, 'group_name' => 'Color', 'attribute_id' => 10, 'value' => 'Natural'],
            ],
            'commercial' => [
                'currency' => 'TRY',
                'price' => 12.34,
                'sale_unit' => 'meter',
                'min_order_quantity' => 1,
                'availability' => 'in_stock',
                'stock_quantity' => 10,
                'orderable' => true,
                'show_price' => true,
                'visibility' => 'both',
                'as_of' => '2026-09-13T10:01:00Z',
                'realtime_required' => true,
            ],
            'verified_specs' => ['material' => 'natural_jute'],
            'declared_specs' => [],
            'derived_properties' => [],
            'suitability' => [
                'recommended_for' => [],
                'conditionally_suitable_for' => [],
                'not_recommended_for' => [],
            ],
            'evidence' => [],
            'links' => [
                'canonical_web' => 'https://example.test/p/test-product',
                'realtime_api' => null,
                'direct_cart' => null,
                'image' => 'https://example.test/image.jpg',
            ],
        ];
    }
}

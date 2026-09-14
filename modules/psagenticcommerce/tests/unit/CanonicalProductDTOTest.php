<?php

use PHPUnit\Framework\TestCase;
use PrestaShopAgenticCommerce\Domain\CanonicalProductDTO;

final class CanonicalProductDTOTest extends TestCase
{
    public function testAcceptsCountryAwareRepresentationContext(): void
    {
        $dto = new CanonicalProductDTO($this->payload());
        self::assertSame('TR', $dto->toArray()['context']['country']);
    }

    public function testRejectsLowercaseCurrency(): void
    {
        $payload = $this->payload();
        $payload['context']['currency'] = 'try';

        $this->expectException(\InvalidArgumentException::class);
        new CanonicalProductDTO($payload);
    }

    public function testRejectsMissingTaxCountry(): void
    {
        $payload = $this->payload();
        unset($payload['context']['id_country']);

        $this->expectException(\InvalidArgumentException::class);
        new CanonicalProductDTO($payload);
    }

    private function payload(): array
    {
        return [
            'schema_version' => '1.0',
            'canonical_variant_id' => 'ps-1-123-0',
            'product_group_id' => 'ps-1-123',
            'category_type' => 'general',
            'source' => [
                'system' => 'prestashop',
                'id_shop' => 1,
                'id_product' => 123,
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
                'source_updated_at' => null,
                'canonical_generated_at' => '2026-09-13T10:00:00Z',
            ],
            'identity' => ['title' => 'Test'],
            'content' => ['short_description' => '', 'description' => ''],
            'media' => [],
            'variant_dimensions' => [],
            'commercial' => [
                'currency' => 'TRY',
                'price' => 10.0,
                'sale_unit' => 'piece',
                'min_order_quantity' => 1,
                'availability' => 'in_stock',
                'stock_quantity' => 1,
                'orderable' => true,
                'show_price' => true,
                'visibility' => 'both',
                'as_of' => '2026-09-13T10:00:00Z',
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
            'links' => ['canonical_web' => 'https://example.test/p'],
        ];
    }
}

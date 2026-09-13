<?php

use PHPUnit\Framework\TestCase;
use PrestaShopAgenticCommerce\Domain\CanonicalProductDTO;
use PrestaShopAgenticCommerce\Domain\CanonicalValueHash;
use PrestaShopAgenticCommerce\Publication\CanonicalPublicationPolicy;

final class CanonicalPublicationPolicyTest extends TestCase
{
    public function testFiltersMismatchedEvidenceAndRedactsCommerce(): void
    {
        $payload = $this->payload();
        $payload['verified_specs']['diameter_mm'] = 8.0;
        $payload['declared_specs']['material'] = 'jute';
        $payload['evidence'] = [
            'diameter_mm' => [[
                'evidence_class' => 'verified',
                'source_type' => 'measurement',
                'source_id' => null,
                'source_url' => null,
                'value_hash' => CanonicalValueHash::fromValue(8.0),
                'confidence' => 1.0,
                'evidence_date' => null,
                'is_public' => true,
                'status' => 'active',
                'notes' => 'private',
            ]],
            'material' => [[
                'evidence_class' => 'declared',
                'source_type' => 'supplier',
                'source_id' => null,
                'source_url' => null,
                'value_hash' => CanonicalValueHash::fromValue('polyester'),
                'confidence' => 0.9,
                'evidence_date' => null,
                'is_public' => true,
                'status' => 'active',
                'notes' => null,
            ]],
        ];

        $out = (new CanonicalPublicationPolicy())->prepare(new CanonicalProductDTO($payload));

        self::assertSame(['diameter_mm' => 8.0], $out['verified_specs']);
        self::assertSame([], $out['declared_specs']);
        self::assertNull($out['commercial']['price']);
        self::assertNull($out['commercial']['stock_quantity']);
        self::assertArrayNotHasKey('notes', $out['evidence']['diameter_mm'][0]);
    }

    public function testStrictModeRejectsWrongValueHash(): void
    {
        $payload = $this->payload();
        $payload['verified_specs']['diameter_mm'] = 10.0;
        $payload['evidence']['diameter_mm'] = [[
            'evidence_class' => 'verified',
            'source_type' => 'measurement',
            'source_id' => null,
            'source_url' => null,
            'value_hash' => CanonicalValueHash::fromValue(8.0),
            'confidence' => 1.0,
            'evidence_date' => null,
            'is_public' => true,
            'status' => 'active',
            'notes' => null,
        ]];

        $this->expectException(\DomainException::class);
        (new CanonicalPublicationPolicy())->prepareStrict(new CanonicalProductDTO($payload));
    }

    public function testKeepsVisiblePublicPrice(): void
    {
        $payload = $this->payload();
        $payload['context']['pricing_context'] = 'public_catalog_tax_included';

        $out = (new CanonicalPublicationPolicy())->prepare(new CanonicalProductDTO($payload));

        self::assertSame(10.0, $out['commercial']['price']);
        self::assertNull($out['commercial']['stock_quantity']);
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
                'language' => 'en',
                'locale' => 'en-US',
                'id_currency' => 1,
                'currency' => 'USD',
                'id_country' => 1,
                'country' => 'US',
                'pricing_context' => 'runtime_context_tax_included',
            ],
            'timestamps' => [
                'source_updated_at' => null,
                'canonical_generated_at' => '2026-09-13T10:00:00Z',
            ],
            'identity' => ['title' => 'Test product'],
            'content' => ['short_description' => '', 'description' => ''],
            'media' => [],
            'variant_dimensions' => [],
            'commercial' => [
                'currency' => 'USD',
                'price' => 10.0,
                'sale_unit' => 'piece',
                'min_order_quantity' => 1,
                'availability' => 'in_stock',
                'stock_quantity' => 25,
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
            'links' => ['canonical_web' => 'https://example.test/product/123'],
        ];
    }
}

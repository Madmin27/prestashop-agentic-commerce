<?php

use PHPUnit\Framework\TestCase;
use PrestaShopAgenticCommerce\Domain\CanonicalProductDTO;
use PrestaShopAgenticCommerce\Domain\CanonicalValueHash;
use PrestaShopAgenticCommerce\Publication\CanonicalPublicationPolicy;

final class SuitabilityProvenanceTest extends TestCase
{
    public function testPositiveClaimsNeedEvidenceButSafetyExclusionsRemain(): void
    {
        $payload = $this->payload();
        $payload['suitability'] = [
            'recommended_for' => ['crafts', 'lifting'],
            'conditionally_suitable_for' => ['outdoor_short_term'],
            'not_recommended_for' => ['climbing'],
        ];
        $payload['evidence'] = [
            'suitability.recommended_for' => [$this->evidence('declared', 'crafts')],
            'suitability.conditionally_suitable_for' => [$this->evidence('derived', 'outdoor_short_term')],
        ];

        $out = (new CanonicalPublicationPolicy())->prepare(new CanonicalProductDTO($payload));

        self::assertSame(['crafts'], $out['suitability']['recommended_for']);
        self::assertSame(['outdoor_short_term'], $out['suitability']['conditionally_suitable_for']);
        self::assertSame(['climbing'], $out['suitability']['not_recommended_for']);
    }

    public function testStrictModeRejectsUnprovenRecommendation(): void
    {
        $payload = $this->payload();
        $payload['suitability']['recommended_for'] = ['lifting'];

        $this->expectException(\DomainException::class);
        (new CanonicalPublicationPolicy())->prepareStrict(new CanonicalProductDTO($payload));
    }

    private function evidence(string $class, string $value): array
    {
        return [
            'evidence_class' => $class,
            'source_type' => 'test',
            'source_id' => null,
            'source_url' => null,
            'value_hash' => CanonicalValueHash::fromValue($value),
            'confidence' => 1.0,
            'evidence_date' => null,
            'is_public' => true,
            'status' => 'active',
            'notes' => null,
        ];
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
                'currency' => 'USD',
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

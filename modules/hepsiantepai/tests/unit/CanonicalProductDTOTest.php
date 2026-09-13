<?php

use Hepsiantep\Ai\Domain\CanonicalProductDTO;
use PHPUnit\Framework\TestCase;

final class CanonicalProductDTOTest extends TestCase
{
    public function testAcceptsMinimalValidContract(): void
    {
        $dto = new CanonicalProductDTO($this->payload());
        self::assertSame('ps-1-123-0', $dto->toArray()['canonical_variant_id']);
    }

    public function testRejectsMissingCategoryType(): void
    {
        $payload = $this->payload();
        unset($payload['category_type']);
        $this->expectException(InvalidArgumentException::class);
        new CanonicalProductDTO($payload);
    }

    private function payload(): array
    {
        return [
            'schema_version' => '1.0',
            'canonical_variant_id' => 'ps-1-123-0',
            'product_group_id' => null,
            'category_type' => 'general',
            'source' => ['system' => 'prestashop', 'id_shop' => 1, 'id_product' => 123, 'id_product_attribute' => 0],
            'context' => ['language' => 'tr', 'currency' => 'TRY', 'pricing_context' => 'public_tax_included'],
            'timestamps' => ['source_updated_at' => null, 'canonical_generated_at' => '2026-09-13T09:00:00Z'],
            'identity' => ['title' => 'Test Product'],
            'variant_dimensions' => [],
            'commercial' => ['currency' => 'TRY', 'price' => 1.0, 'sale_unit' => 'piece', 'min_order_quantity' => 1, 'availability' => 'in_stock', 'stock_quantity' => 1, 'as_of' => '2026-09-13T09:00:00Z', 'realtime_required' => true],
            'verified_specs' => [],
            'declared_specs' => [],
            'derived_properties' => [],
            'suitability' => ['recommended_for' => [], 'conditionally_suitable_for' => [], 'not_recommended_for' => []],
            'evidence' => [],
            'links' => ['canonical_web' => 'https://hepsiantep.com/test'],
        ];
    }
}

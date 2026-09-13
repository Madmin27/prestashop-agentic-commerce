<?php

use PHPUnit\Framework\TestCase;
use PrestaShopAgenticCommerce\Domain\CanonicalProductDTO;
use PrestaShopAgenticCommerce\Ucp\UcpCatalogAdapter;

final class UcpCatalogAdapterTest extends TestCase
{
    public function testBuildsCurrentUcpProductShape(): void
    {
        $adapter = new UcpCatalogAdapter();
        $product = $adapter->product([
            new CanonicalProductDTO($this->payload('ps-1-10-2', 12.34, 'in_stock', 'M', 2)),
            new CanonicalProductDTO($this->payload('ps-1-10-3', 15.00, 'out_of_stock', 'L', 3)),
        ]);

        self::assertNotNull($product);
        self::assertSame('ps-1-10', $product['id']);
        self::assertSame(['amount' => 1234, 'currency' => 'TRY'], $product['price_range']['min']);
        self::assertSame(['amount' => 1500, 'currency' => 'TRY'], $product['price_range']['max']);
        self::assertSame(1234, $product['variants'][0]['price']['amount']);
        self::assertSame(['available' => true, 'status' => 'in_stock'], $product['variants'][0]['availability']);
        self::assertSame(['name' => 'Size', 'label' => 'M'], $product['variants'][0]['options'][0]);
        self::assertSame(['available' => false, 'status' => 'out_of_stock'], $product['variants'][1]['availability']);
        self::assertSame(['plain' => 'Public description'], $product['description']);
    }

    public function testOmitsProductWithoutPublicPrice(): void
    {
        $payload = $this->payload('ps-1-10-0', 10.0, 'in_stock', '', 0);
        $payload['commercial']['show_price'] = false;
        $payload['commercial']['price'] = null;

        self::assertNull((new UcpCatalogAdapter())->product([
            new CanonicalProductDTO($payload),
        ]));
    }

    private function payload(string $variantId, float $price, string $availability, string $size, int $attribute): array
    {
        return [
            'schema_version' => '1.0',
            'canonical_variant_id' => $variantId,
            'product_group_id' => 'ps-1-10',
            'category_type' => 'general',
            'source' => [
                'system' => 'prestashop',
                'id_shop' => 1,
                'id_product' => 10,
                'id_product_attribute' => $attribute,
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
                'sku' => $variantId,
                'gtin' => null,
                'mpn' => null,
                'brand' => 'Test',
                'title' => 'Test Product',
            ],
            'content' => [
                'short_description' => 'Short',
                'description' => 'Public description',
            ],
            'media' => [],
            'variant_dimensions' => $attribute > 0 ? [[
                'group_id' => 1,
                'group_name' => 'Size',
                'attribute_id' => $attribute,
                'value' => $size,
            ]] : [],
            'commercial' => [
                'currency' => 'TRY',
                'price' => $price,
                'sale_unit' => 'piece',
                'min_order_quantity' => 1,
                'availability' => $availability,
                'stock_quantity' => 10,
                'orderable' => true,
                'show_price' => true,
                'visibility' => 'both',
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
                'canonical_web' => 'https://example.test/p/test-product',
                'realtime_api' => null,
                'direct_cart' => null,
                'image' => null,
            ],
        ];
    }
}

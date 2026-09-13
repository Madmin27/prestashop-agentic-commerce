<?php

use PHPUnit\Framework\TestCase;
use PrestaShopAgenticCommerce\Contract\ProductVariantSourceInterface;
use PrestaShopAgenticCommerce\Contract\PublicCanonicalProductProviderInterface;
use PrestaShopAgenticCommerce\Domain\CanonicalProductDTO;
use PrestaShopAgenticCommerce\Export\AiJson\AiJsonExportCoordinator;
use PrestaShopAgenticCommerce\Export\AiJson\AiJsonExporter;
use PrestaShopAgenticCommerce\Export\AiJson\RepresentationKey;

if (!class_exists('Context')) {
    class Context
    {
        public $shop;
        public $language;
        public $currency;
    }
}

final class AiJsonExportCoordinatorTest extends TestCase
{
    public function testBuildsCompleteRepresentationBundle(): void
    {
        $source = new class implements ProductVariantSourceInterface {
            public function listForShop(int $idShop): array
            {
                return [
                    ['id_product' => 2, 'id_product_attribute' => 20],
                    ['id_product' => 1, 'id_product_attribute' => 10],
                    ['id_product' => 3, 'id_product_attribute' => 0],
                ];
            }
        };

        $provider = new class implements PublicCanonicalProductProviderInterface {
            public function build(int $idProduct, int $idProductAttribute = 0, ?Context $context = null): CanonicalProductDTO
            {
                $visibility = $idProduct === 3 ? 'none' : 'both';
                return new CanonicalProductDTO(AiJsonExportCoordinatorTest::payload(
                    'ps-1-' . $idProduct . '-' . $idProductAttribute,
                    $idProduct,
                    $idProductAttribute,
                    $visibility
                ));
            }
        };

        $context = new Context();
        $context->shop = (object) ['id' => 1];
        $context->language = (object) ['id' => 1];
        $context->currency = (object) ['id' => 1, 'iso_code' => 'TRY'];
        $representation = new RepresentationKey(1, 1, 'tr', 'tr-TR', 1, 'TRY', 224, 'TR');

        $bundle = (new AiJsonExportCoordinator($source, $provider, new AiJsonExporter()))->build(
            $representation,
            $context,
            'https://shop.example',
            '2026-09-13T12:00:00Z',
            'https://shop.example/.well-known/ucp'
        );

        self::assertSame(2, $bundle->catalog()['product_count']);
        self::assertSame(
            ['ps-1-1-10', 'ps-1-2-20'],
            array_keys($bundle->products())
        );
        self::assertSame(
            'https://shop.example/ai/v1/s1/tr-TR/TRY/TR/catalog.json',
            $bundle->manifest()['endpoints']['catalog']
        );
    }

    public function testRejectsMismatchedContextBeforeReadingProducts(): void
    {
        $source = new class implements ProductVariantSourceInterface {
            public function listForShop(int $idShop): array
            {
                throw new RuntimeException('Source must not be called.');
            }
        };
        $provider = new class implements PublicCanonicalProductProviderInterface {
            public function build(int $idProduct, int $idProductAttribute = 0, ?Context $context = null): CanonicalProductDTO
            {
                throw new RuntimeException('Provider must not be called.');
            }
        };

        $context = new Context();
        $context->shop = (object) ['id' => 2];
        $context->language = (object) ['id' => 1];
        $context->currency = (object) ['id' => 1, 'iso_code' => 'TRY'];

        $this->expectException(InvalidArgumentException::class);
        (new AiJsonExportCoordinator($source, $provider, new AiJsonExporter()))->build(
            new RepresentationKey(1, 1, 'tr', 'tr-TR', 1, 'TRY', 224, 'TR'),
            $context,
            'https://shop.example'
        );
    }

    public static function payload(
        string $variantId,
        int $idProduct,
        int $idProductAttribute,
        string $visibility
    ): array {
        return [
            'schema_version' => '1.0',
            'canonical_variant_id' => $variantId,
            'product_group_id' => 'ps-1-' . $idProduct,
            'category_type' => 'general',
            'source' => [
                'system' => 'prestashop',
                'id_shop' => 1,
                'id_product' => $idProduct,
                'id_product_attribute' => $idProductAttribute,
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
                'title' => 'Product ' . $idProduct,
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
                'stock_quantity' => 1,
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

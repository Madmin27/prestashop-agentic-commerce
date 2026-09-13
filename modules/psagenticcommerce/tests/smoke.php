<?php

declare(strict_types=1);

if (!defined('_PS_VERSION_')) {
    define('_PS_VERSION_', '8.0.0');
}

require_once dirname(__DIR__) . '/src/autoload.php';

use PrestaShopAgenticCommerce\Domain\CanonicalProductDTO;
use PrestaShopAgenticCommerce\Domain\CanonicalValueHash;
use PrestaShopAgenticCommerce\Export\AiJson\AiJsonExporter;
use PrestaShopAgenticCommerce\Export\AiJson\RepresentationKey;

function smokeAssert(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function smokePayload(string $variantId, string $title, string $visibility = 'both'): array
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

$representation = new RepresentationKey(1, 1, 'tr', 'tr_TR', 1, 'TRY', 224, 'TR');
smokeAssert(
    $representation->catalogPath() === '/ai/v1/s1/tr-TR/TRY/TR/catalog.json',
    'Representation path isolation failed.'
);

smokeAssert(
    CanonicalValueHash::fromValue(8) === CanonicalValueHash::fromValue(8.0),
    'Equivalent numeric values must hash identically.'
);

$exporter = new AiJsonExporter();
$catalog = $exporter->catalogDocument(
    $representation,
    [
        new CanonicalProductDTO(smokePayload('ps-1-2-0', 'B')),
        new CanonicalProductDTO(smokePayload('ps-1-3-0', 'Hidden', 'none')),
        new CanonicalProductDTO(smokePayload('ps-1-1-0', 'A')),
    ],
    'https://shop.example',
    '2026-09-13T12:00:00Z'
);

smokeAssert($catalog['product_count'] === 2, 'Hidden products must not enter the public catalog.');
smokeAssert(
    $catalog['products'][0]['canonical_variant_id'] === 'ps-1-1-0'
    && $catalog['products'][1]['canonical_variant_id'] === 'ps-1-2-0',
    'Catalog order must be deterministic.'
);

$product = smokePayload('ps-1-1-0', 'A');
$product['verified_specs']['diameter_mm'] = 8.0;
$product['evidence']['diameter_mm'] = [[
    'evidence_class' => 'verified',
    'source_type' => 'measurement',
    'source_id' => null,
    'source_url' => null,
    'value_hash' => CanonicalValueHash::fromValue(8.0),
    'confidence' => 1.0,
    'evidence_date' => null,
    'is_public' => true,
    'status' => 'active',
    'notes' => 'must not publish',
]];
$product['suitability']['recommended_for'] = ['lifting'];
$product['evidence']['suitability.recommended_for'] = [[
    'evidence_class' => 'derived',
    'source_type' => 'inference',
    'source_id' => null,
    'source_url' => null,
    'value_hash' => CanonicalValueHash::fromValue('lifting'),
    'confidence' => 0.8,
    'evidence_date' => null,
    'is_public' => true,
    'status' => 'active',
    'notes' => null,
]];

$document = $exporter->productDocument(new CanonicalProductDTO($product));
smokeAssert($document['verified_specs']['diameter_mm'] === 8.0, 'Verified fact was lost.');
smokeAssert($document['commercial']['stock_quantity'] === null, 'Exact stock must be redacted.');
smokeAssert($document['suitability']['recommended_for'] === [], 'Derived evidence must not create a direct recommendation.');
smokeAssert(!array_key_exists('notes', $document['evidence']['diameter_mm'][0]), 'Private evidence notes leaked.');

$manifest = $exporter->discoveryDocument(
    $representation,
    'https://shop.example',
    '2026-09-13T12:00:00Z',
    'https://shop.example/.well-known/ucp'
);
smokeAssert(
    $manifest['endpoints']['catalog'] === 'https://shop.example/ai/v1/s1/tr-TR/TRY/TR/catalog.json',
    'Manifest catalog endpoint is incorrect.'
);

json_decode($exporter->encode($catalog), true, 512, JSON_THROW_ON_ERROR);
json_decode($exporter->encode($document), true, 512, JSON_THROW_ON_ERROR);
json_decode($exporter->encode($manifest), true, 512, JSON_THROW_ON_ERROR);

echo "psagenticcommerce smoke: OK\n";

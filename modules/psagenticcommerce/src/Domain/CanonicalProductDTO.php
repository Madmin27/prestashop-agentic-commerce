<?php

namespace PrestaShopAgenticCommerce\Domain;

if (!defined('_PS_VERSION_')) {
    exit;
}

final class CanonicalProductDTO implements \JsonSerializable
{
    /** @param array<string,mixed> $data */
    public function __construct(private array $data)
    {
        $this->assertRequiredStructure($data);
    }

    /** @return array<string,mixed> */
    public function toArray(): array
    {
        return $this->data;
    }

    /** @return array<string,mixed> */
    public function jsonSerialize(): array
    {
        return $this->data;
    }

    /** @param array<string,mixed> $data */
    private function assertRequiredStructure(array $data): void
    {
        foreach ([
            'schema_version',
            'canonical_variant_id',
            'product_group_id',
            'category_type',
            'source',
            'context',
            'timestamps',
            'identity',
            'content',
            'media',
            'variant_dimensions',
            'commercial',
            'verified_specs',
            'declared_specs',
            'derived_properties',
            'suitability',
            'evidence',
            'links',
        ] as $key) {
            if (!array_key_exists($key, $data)) {
                throw new \InvalidArgumentException('Missing canonical product field: ' . $key);
            }
        }

        if (($data['schema_version'] ?? null) !== '1.0') {
            throw new \InvalidArgumentException('Unsupported canonical schema version.');
        }
        if (!is_string($data['canonical_variant_id']) || trim($data['canonical_variant_id']) === '') {
            throw new \InvalidArgumentException('Canonical variant id is required.');
        }
        if (!is_string($data['product_group_id']) || trim($data['product_group_id']) === '') {
            throw new \InvalidArgumentException('Product group id is required.');
        }
        if (!is_string($data['category_type']) || trim($data['category_type']) === '') {
            throw new \InvalidArgumentException('Category type is required.');
        }

        $source = $data['source'];
        if (!is_array($source)
            || ($source['system'] ?? null) !== 'prestashop'
            || (int) ($source['id_shop'] ?? 0) < 1
            || (int) ($source['id_product'] ?? 0) < 1
            || (int) ($source['id_product_attribute'] ?? -1) < 0
        ) {
            throw new \InvalidArgumentException('Invalid canonical source identity.');
        }

        $context = $data['context'];
        if (!is_array($context)
            || (int) ($context['id_language'] ?? 0) < 1
            || !is_string($context['language'] ?? null)
            || trim((string) $context['language']) === ''
            || !is_string($context['locale'] ?? null)
            || trim((string) $context['locale']) === ''
            || (int) ($context['id_currency'] ?? 0) < 1
            || !is_string($context['currency'] ?? null)
            || preg_match('/^[A-Z]{3}$/', (string) $context['currency']) !== 1
            || (int) ($context['id_country'] ?? 0) < 1
            || !is_string($context['country'] ?? null)
            || preg_match('/^[A-Z]{2}$/', (string) $context['country']) !== 1
            || !is_string($context['pricing_context'] ?? null)
            || trim((string) $context['pricing_context']) === ''
        ) {
            throw new \InvalidArgumentException('Invalid canonical representation context.');
        }

        $timestamps = $data['timestamps'];
        if (!is_array($timestamps)
            || !is_string($timestamps['canonical_generated_at'] ?? null)
            || strtotime((string) $timestamps['canonical_generated_at']) === false
            || (isset($timestamps['source_updated_at'])
                && $timestamps['source_updated_at'] !== null
                && (!is_string($timestamps['source_updated_at'])
                    || strtotime((string) $timestamps['source_updated_at']) === false))
        ) {
            throw new \InvalidArgumentException('Invalid canonical timestamps.');
        }

        $identity = $data['identity'];
        if (!is_array($identity)
            || !is_string($identity['title'] ?? null)
            || trim((string) $identity['title']) === ''
        ) {
            throw new \InvalidArgumentException('Canonical product title is required.');
        }

        $content = $data['content'];
        if (!is_array($content)
            || !is_string($content['short_description'] ?? null)
            || !is_string($content['description'] ?? null)
        ) {
            throw new \InvalidArgumentException('Invalid canonical content block.');
        }

        if (!is_array($data['media'])) {
            throw new \InvalidArgumentException('Canonical media must be an array.');
        }
        foreach ($data['media'] as $media) {
            if (!is_array($media)
                || !in_array(($media['type'] ?? null), ['image', 'video'], true)
                || !is_string($media['url'] ?? null)
                || filter_var((string) $media['url'], FILTER_VALIDATE_URL) === false
            ) {
                throw new \InvalidArgumentException('Invalid canonical media entry.');
            }
        }

        if (!is_array($data['variant_dimensions'])) {
            throw new \InvalidArgumentException('Canonical variant dimensions must be an array.');
        }
        foreach ($data['variant_dimensions'] as $dimension) {
            if (!is_array($dimension)
                || (int) ($dimension['group_id'] ?? 0) < 1
                || !is_string($dimension['group_name'] ?? null)
                || (int) ($dimension['attribute_id'] ?? 0) < 1
                || !is_string($dimension['value'] ?? null)
                || trim((string) $dimension['value']) === ''
            ) {
                throw new \InvalidArgumentException('Invalid canonical variant dimension.');
            }
        }

        $commercial = $data['commercial'];
        if (!is_array($commercial)
            || ($commercial['currency'] ?? null) !== $context['currency']
            || (($commercial['price'] ?? null) !== null
                && (!is_numeric($commercial['price']) || (float) $commercial['price'] < 0))
            || !is_string($commercial['sale_unit'] ?? null)
            || trim((string) $commercial['sale_unit']) === ''
            || (($commercial['min_order_quantity'] ?? null) !== null
                && (!is_numeric($commercial['min_order_quantity'])
                    || (float) $commercial['min_order_quantity'] <= 0))
            || !in_array(($commercial['availability'] ?? null), ['in_stock', 'out_of_stock', 'preorder', 'backorder', 'unknown'], true)
            || (($commercial['stock_quantity'] ?? null) !== null && !is_numeric($commercial['stock_quantity']))
            || !is_bool($commercial['orderable'] ?? null)
            || !is_bool($commercial['show_price'] ?? null)
            || !in_array(($commercial['visibility'] ?? null), ['both', 'catalog', 'search', 'none'], true)
            || !is_string($commercial['as_of'] ?? null)
            || strtotime((string) $commercial['as_of']) === false
            || !is_bool($commercial['realtime_required'] ?? null)
        ) {
            throw new \InvalidArgumentException('Invalid canonical commercial block.');
        }

        foreach (['verified_specs', 'declared_specs', 'derived_properties', 'evidence'] as $key) {
            if (!is_array($data[$key])) {
                throw new \InvalidArgumentException('Canonical ' . $key . ' must be an object.');
            }
        }

        $suitability = $data['suitability'];
        if (!is_array($suitability)) {
            throw new \InvalidArgumentException('Invalid canonical suitability block.');
        }
        foreach (['recommended_for', 'conditionally_suitable_for', 'not_recommended_for'] as $key) {
            if (!is_array($suitability[$key] ?? null)) {
                throw new \InvalidArgumentException('Invalid canonical suitability list: ' . $key);
            }
        }

        $links = $data['links'];
        if (!is_array($links)
            || !is_string($links['canonical_web'] ?? null)
            || filter_var((string) $links['canonical_web'], FILTER_VALIDATE_URL) === false
        ) {
            throw new \InvalidArgumentException('Invalid canonical links block.');
        }
        foreach (['realtime_api', 'direct_cart', 'image'] as $key) {
            if (array_key_exists($key, $links)
                && $links[$key] !== null
                && (!is_string($links[$key]) || filter_var((string) $links[$key], FILTER_VALIDATE_URL) === false)
            ) {
                throw new \InvalidArgumentException('Invalid canonical link: ' . $key);
            }
        }
    }
}

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

        if (!is_array($data['variant_dimensions']) || !is_array($data['commercial'])) {
            throw new \InvalidArgumentException('Invalid canonical variant or commercial data.');
        }
    }
}

<?php

namespace Hepsiantep\Ai\Domain;

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
            'source',
            'context',
            'timestamps',
            'identity',
            'commercial',
            'verified_specs',
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

        $source = $data['source'];
        if (!is_array($source)
            || (int) ($source['id_shop'] ?? 0) < 1
            || (int) ($source['id_product'] ?? 0) < 1
            || (int) ($source['id_product_attribute'] ?? -1) < 0
        ) {
            throw new \InvalidArgumentException('Invalid canonical source identity.');
        }
    }
}

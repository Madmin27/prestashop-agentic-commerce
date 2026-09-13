<?php

namespace PrestaShopAgenticCommerce\Publication;

use PrestaShopAgenticCommerce\Domain\CanonicalProductDTO;
use PrestaShopAgenticCommerce\Domain\CanonicalValueHash;

if (!defined('_PS_VERSION_')) {
    exit;
}

final class CanonicalPublicationPolicy
{
    /** @return array<string,mixed> */
    public function prepare(CanonicalProductDTO $dto): array
    {
        return $this->prepareInternal($dto, false);
    }

    /** @return array<string,mixed> */
    public function prepareStrict(CanonicalProductDTO $dto): array
    {
        return $this->prepareInternal($dto, true);
    }

    /** @return array<string,mixed> */
    private function prepareInternal(CanonicalProductDTO $dto, bool $strict): array
    {
        $data = $dto->toArray();
        $evidence = is_array($data['evidence'] ?? null) ? $data['evidence'] : [];

        $data['verified_specs'] = $this->filterSpecs(
            $data['verified_specs'] ?? [], $evidence, 'verified', $strict, false
        );
        $data['declared_specs'] = $this->filterSpecs(
            $data['declared_specs'] ?? [], $evidence, 'declared', $strict, false
        );
        $data['derived_properties'] = $this->filterSpecs(
            $data['derived_properties'] ?? [], $evidence, 'derived', $strict, true
        );

        $allowedEvidence = [];
        foreach ($data['verified_specs'] as $key => $value) {
            $allowedEvidence[$key][] = ['class' => 'verified', 'hash' => CanonicalValueHash::fromValue($value)];
        }
        foreach ($data['declared_specs'] as $key => $value) {
            $allowedEvidence[$key][] = ['class' => 'declared', 'hash' => CanonicalValueHash::fromValue($value)];
        }
        foreach ($data['derived_properties'] as $key => $property) {
            $allowedEvidence[$key][] = [
                'class' => 'derived',
                'hash' => CanonicalValueHash::fromValue($property['value']),
            ];
        }
        $data['evidence'] = $this->publicEvidence($evidence, $allowedEvidence);

        if (($data['context']['pricing_context'] ?? null) !== 'public_catalog_tax_included'
            || ($data['commercial']['show_price'] ?? false) !== true
        ) {
            $data['commercial']['price'] = null;
            $data['commercial']['realtime_required'] = true;
        }

        $data['commercial']['stock_quantity'] = null;

        return $data;
    }

    /**
     * @param mixed $specs
     * @param array<string,array<int,array<string,mixed>>> $evidence
     * @return array<string,mixed>
     */
    private function filterSpecs($specs, array $evidence, string $class, bool $strict, bool $derived): array
    {
        if (!is_array($specs)) {
            throw new \DomainException('Canonical spec block must be an object.');
        }

        $result = [];
        foreach ($specs as $propertyKey => $propertyValue) {
            if ($propertyValue === null) {
                continue;
            }

            if ($derived) {
                if (!$this->isValidDerivedProperty($propertyValue)) {
                    if ($strict) {
                        throw new \DomainException(sprintf(
                            'Derived property %s has an invalid structure.',
                            (string) $propertyKey
                        ));
                    }
                    continue;
                }
                $hashValue = $propertyValue['value'];
                if ($hashValue === null) {
                    continue;
                }
            } else {
                $hashValue = $propertyValue;
            }

            $expectedHash = CanonicalValueHash::fromValue($hashValue);
            $records = is_array($evidence[$propertyKey] ?? null) ? $evidence[$propertyKey] : [];
            $valid = false;

            foreach ($records as $record) {
                if (!is_array($record)) {
                    continue;
                }
                if (($record['evidence_class'] ?? null) !== $class
                    || ($record['status'] ?? null) !== 'active'
                    || ($record['is_public'] ?? false) !== true
                    || !is_string($record['value_hash'] ?? null)
                    || !hash_equals($expectedHash, (string) $record['value_hash'])
                ) {
                    continue;
                }
                $valid = true;
                break;
            }

            if ($valid) {
                $result[(string) $propertyKey] = $propertyValue;
                continue;
            }

            if ($strict) {
                throw new \DomainException(sprintf(
                    'Property %s has no active public %s evidence for its exact value.',
                    (string) $propertyKey,
                    $class
                ));
            }
        }

        return $result;
    }

    private function isValidDerivedProperty($property): bool
    {
        if (!is_array($property)
            || !array_key_exists('value', $property)
            || !is_string($property['method'] ?? null)
            || trim((string) $property['method']) === ''
            || !is_string($property['rule_version'] ?? null)
            || trim((string) $property['rule_version']) === ''
            || !is_numeric($property['confidence'] ?? null)
        ) {
            return false;
        }

        $confidence = (float) $property['confidence'];
        return $confidence >= 0.0 && $confidence <= 1.0;
    }

    /**
     * @param array<string,array<int,array<string,mixed>>> $evidence
     * @param array<string,array<int,array{class:string,hash:string}>> $allowedEvidence
     * @return array<string,array<int,array<string,mixed>>>
     */
    private function publicEvidence(array $evidence, array $allowedEvidence): array
    {
        $result = [];
        foreach ($allowedEvidence as $key => $allowed) {
            $records = is_array($evidence[$key] ?? null) ? $evidence[$key] : [];
            foreach ($records as $record) {
                if (!is_array($record)
                    || ($record['status'] ?? null) !== 'active'
                    || ($record['is_public'] ?? false) !== true
                    || !is_string($record['value_hash'] ?? null)
                ) {
                    continue;
                }

                $matches = false;
                foreach ($allowed as $expected) {
                    if (($record['evidence_class'] ?? null) === $expected['class']
                        && hash_equals($expected['hash'], (string) $record['value_hash'])
                    ) {
                        $matches = true;
                        break;
                    }
                }
                if (!$matches) {
                    continue;
                }

                unset($record['notes']);
                $result[$key][] = $record;
            }
        }

        ksort($result, SORT_STRING);
        return $result;
    }
}

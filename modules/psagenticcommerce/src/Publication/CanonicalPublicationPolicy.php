<?php

namespace PrestaShopAgenticCommerce\Publication;

use PrestaShopAgenticCommerce\Domain\CanonicalProductDTO;
use PrestaShopAgenticCommerce\Domain\CanonicalValueHash;

if (!defined('_PS_VERSION_')) {
    exit;
}

final class CanonicalPublicationPolicy
{
    /**
     * Prepare a safe public representation. Unsupported properties are omitted
     * rather than allowing one bad evidence row to suppress the whole product.
     * Potentially personalized prices and exact stock quantities are redacted.
     *
     * @return array<string,mixed>
     */
    public function prepare(CanonicalProductDTO $dto): array
    {
        return $this->prepareInternal($dto, false);
    }

    /**
     * Same rules as prepare(), but throws if a technical property cannot be
     * proven by an active public evidence row bound to its exact value.
     * Intended for CI, validation and feed-quality checks.
     *
     * @return array<string,mixed>
     */
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
            $data['verified_specs'] ?? [],
            $evidence,
            'verified',
            $strict,
            false
        );
        $data['declared_specs'] = $this->filterSpecs(
            $data['declared_specs'] ?? [],
            $evidence,
            'declared',
            $strict,
            false
        );
        $data['derived_properties'] = $this->filterSpecs(
            $data['derived_properties'] ?? [],
            $evidence,
            'derived',
            $strict,
            true
        );

        $allowedKeys = array_fill_keys(array_merge(
            array_keys($data['verified_specs']),
            array_keys($data['declared_specs']),
            array_keys($data['derived_properties'])
        ), true);
        $data['evidence'] = $this->publicEvidence($evidence, $allowedKeys);

        if (($data['context']['pricing_context'] ?? null) !== 'public_catalog_tax_included') {
            $data['commercial']['price'] = null;
            $data['commercial']['realtime_required'] = true;
        }

        // Exact stock can be commercially sensitive. Public surfaces should use
        // availability by default; an exporter may opt into exact stock later.
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
            $hashValue = $derived && is_array($propertyValue) && array_key_exists('value', $propertyValue)
                ? $propertyValue['value']
                : $propertyValue;
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

    /**
     * @param array<string,array<int,array<string,mixed>>> $evidence
     * @param array<string,bool> $allowedKeys
     * @return array<string,array<int,array<string,mixed>>>
     */
    private function publicEvidence(array $evidence, array $allowedKeys): array
    {
        $result = [];
        foreach ($evidence as $key => $records) {
            if (!isset($allowedKeys[$key]) || !is_array($records)) {
                continue;
            }
            foreach ($records as $record) {
                if (!is_array($record)
                    || ($record['status'] ?? null) !== 'active'
                    || ($record['is_public'] ?? false) !== true
                ) {
                    continue;
                }
                unset($record['notes']);
                $result[$key][] = $record;
            }
        }

        return $result;
    }
}

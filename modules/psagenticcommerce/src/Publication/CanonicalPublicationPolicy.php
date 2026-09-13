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
        $data['suitability'] = $this->filterSuitability(
            $data['suitability'] ?? [],
            $evidence,
            $strict
        );

        $allowedEvidence = [];
        foreach ($data['verified_specs'] as $key => $value) {
            $allowedEvidence[$key][] = [
                'class' => 'verified',
                'hash' => CanonicalValueHash::fromValue($value),
            ];
        }
        foreach ($data['declared_specs'] as $key => $value) {
            $allowedEvidence[$key][] = [
                'class' => 'declared',
                'hash' => CanonicalValueHash::fromValue($value),
            ];
        }
        foreach ($data['derived_properties'] as $key => $property) {
            $allowedEvidence[$key][] = [
                'class' => 'derived',
                'hash' => CanonicalValueHash::fromValue($property['value']),
            ];
        }
        foreach (['recommended_for', 'conditionally_suitable_for', 'not_recommended_for'] as $bucket) {
            $evidenceKey = 'suitability.' . $bucket;
            $classes = $this->suitabilityAllowedClasses($bucket);
            foreach ($data['suitability'][$bucket] as $term) {
                foreach ($classes as $class) {
                    $allowedEvidence[$evidenceKey][] = [
                        'class' => $class,
                        'hash' => CanonicalValueHash::fromValue($term),
                    ];
                }
            }
        }
        $data['evidence'] = $this->publicEvidence($evidence, $allowedEvidence);

        if (($data['context']['pricing_context'] ?? null) !== 'public_catalog_tax_included'
            || ($data['commercial']['show_price'] ?? false) !== true
        ) {
            $data['commercial']['price'] = null;
            $data['commercial']['realtime_required'] = true;
        }

        // Public discovery exposes an availability state, not exact stock depth.
        $data['commercial']['stock_quantity'] = null;

        return $data;
    }

    /**
     * Positive recommendations require declared or verified provenance.
     * Derived evidence may support only conditional suitability. Conservative
     * exclusions remain publishable without evidence so missing provenance can
     * never weaken a merchant safety guard.
     *
     * @param mixed $suitability
     * @param array<string,array<int,array<string,mixed>>> $evidence
     * @return array<string,array<int,string>>
     */
    private function filterSuitability($suitability, array $evidence, bool $strict): array
    {
        if (!is_array($suitability)) {
            throw new \DomainException('Canonical suitability block must be an object.');
        }

        $result = [
            'recommended_for' => [],
            'conditionally_suitable_for' => [],
            'not_recommended_for' => [],
        ];

        foreach (['recommended_for', 'conditionally_suitable_for'] as $bucket) {
            $terms = is_array($suitability[$bucket] ?? null) ? $suitability[$bucket] : [];
            $evidenceKey = 'suitability.' . $bucket;
            $records = is_array($evidence[$evidenceKey] ?? null) ? $evidence[$evidenceKey] : [];
            $classes = $this->suitabilityAllowedClasses($bucket);

            foreach ($terms as $term) {
                $term = trim((string) $term);
                if ($term === '') {
                    continue;
                }

                $expectedHash = CanonicalValueHash::fromValue($term);
                if ($this->hasPublicEvidence($records, $expectedHash, $classes)) {
                    $result[$bucket][] = $term;
                    continue;
                }

                if ($strict) {
                    throw new \DomainException(sprintf(
                        'Suitability claim %s:%s has no acceptable active public provenance.',
                        $bucket,
                        $term
                    ));
                }
            }
        }

        $negativeTerms = is_array($suitability['not_recommended_for'] ?? null)
            ? $suitability['not_recommended_for']
            : [];
        foreach ($negativeTerms as $term) {
            $term = trim((string) $term);
            if ($term !== '') {
                $result['not_recommended_for'][] = $term;
            }
        }

        foreach ($result as &$terms) {
            $terms = array_values(array_unique($terms));
            sort($terms, SORT_STRING);
        }
        unset($terms);

        return $result;
    }

    /** @return array<int,string> */
    private function suitabilityAllowedClasses(string $bucket): array
    {
        if ($bucket === 'recommended_for') {
            return ['verified', 'declared'];
        }

        return ['verified', 'declared', 'derived'];
    }

    /**
     * @param array<int,array<string,mixed>> $records
     * @param array<int,string> $allowedClasses
     */
    private function hasPublicEvidence(array $records, string $expectedHash, array $allowedClasses): bool
    {
        foreach ($records as $record) {
            if (!is_array($record)
                || !in_array(($record['evidence_class'] ?? null), $allowedClasses, true)
                || ($record['status'] ?? null) !== 'active'
                || ($record['is_public'] ?? false) !== true
                || !is_string($record['value_hash'] ?? null)
            ) {
                continue;
            }

            if (hash_equals($expectedHash, (string) $record['value_hash'])) {
                return true;
            }
        }

        return false;
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
            if ($this->hasPublicEvidence($records, $expectedHash, [$class])) {
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

        ksort($result, SORT_STRING);
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
        foreach ($result as &$records) {
            usort($records, static function (array $left, array $right): int {
                $leftKey = implode('|', [
                    (string) ($left['evidence_class'] ?? ''),
                    (string) ($left['source_type'] ?? ''),
                    (string) ($left['source_id'] ?? ''),
                    (string) ($left['evidence_date'] ?? ''),
                    (string) ($left['value_hash'] ?? ''),
                ]);
                $rightKey = implode('|', [
                    (string) ($right['evidence_class'] ?? ''),
                    (string) ($right['source_type'] ?? ''),
                    (string) ($right['source_id'] ?? ''),
                    (string) ($right['evidence_date'] ?? ''),
                    (string) ($right['value_hash'] ?? ''),
                ]);
                return $leftKey <=> $rightKey;
            });
        }
        unset($records);

        return $result;
    }
}

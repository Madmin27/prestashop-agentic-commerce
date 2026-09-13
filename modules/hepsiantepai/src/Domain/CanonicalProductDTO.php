<?php
namespace Hepsiantep\Ai\Domain;
if (!defined('_PS_VERSION_')) { exit; }
final class CanonicalProductDTO implements \JsonSerializable
{
    public function __construct(private array $data) { $this->assertRequiredStructure($data); }
    public function toArray(): array { return $this->data; }
    public function jsonSerialize(): array { return $this->data; }
    private function assertRequiredStructure(array $data): void
    {
        foreach (['schema_version','canonical_variant_id','category_type','source','context','timestamps','identity','commercial','verified_specs','declared_specs','derived_properties','suitability','evidence','links'] as $key) {
            if (!array_key_exists($key, $data)) { throw new \InvalidArgumentException('Missing canonical product field: ' . $key); }
        }
        if (($data['schema_version'] ?? null) !== '1.0') { throw new \InvalidArgumentException('Unsupported canonical schema version.'); }
        if (!is_string($data['canonical_variant_id']) || trim($data['canonical_variant_id']) === '') { throw new \InvalidArgumentException('Canonical variant id is required.'); }
        if (!is_string($data['category_type']) || trim($data['category_type']) === '') { throw new \InvalidArgumentException('Category type is required.'); }
        $source = $data['source'];
        if (!is_array($source) || (int)($source['id_shop'] ?? 0) < 1 || (int)($source['id_product'] ?? 0) < 1 || (int)($source['id_product_attribute'] ?? -1) < 0) {
            throw new \InvalidArgumentException('Invalid canonical source identity.');
        }
    }
}

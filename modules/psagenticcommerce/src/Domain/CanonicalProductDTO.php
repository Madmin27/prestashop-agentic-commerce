<?php
namespace PrestaShopAgenticCommerce\Domain;
if (!defined('_PS_VERSION_')) { exit; }
final class CanonicalProductDTO implements \JsonSerializable
{
    public function __construct(private array $data)
    {
        foreach (['schema_version','canonical_variant_id','product_group_id','category_type','source','context','timestamps','identity','content','media','commercial','verified_specs','declared_specs','derived_properties','suitability','evidence','links'] as $key) {
            if (!array_key_exists($key, $data)) { throw new \InvalidArgumentException('Missing canonical product field: '.$key); }
        }
        if (($data['schema_version'] ?? null) !== '1.0') { throw new \InvalidArgumentException('Unsupported canonical schema version.'); }
    }
    public function toArray(): array { return $this->data; }
    public function jsonSerialize(): array { return $this->data; }
}

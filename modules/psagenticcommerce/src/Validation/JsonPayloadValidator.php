<?php
namespace PrestaShopAgenticCommerce\Validation;
final class JsonPayloadValidator
{
    public static function decodeNullable(?string $json): ?array
    {
        if ($json===null || trim($json)==='') { return null; }
        try { $decoded=json_decode($json,true,512,JSON_THROW_ON_ERROR); }
        catch (\JsonException $e) { throw new \InvalidArgumentException('Invalid JSON payload: '.$e->getMessage(),0,$e); }
        if (!is_array($decoded)) { throw new \InvalidArgumentException('AI metadata JSON must decode to an object or array.'); }
        return $decoded;
    }
    public static function encodeNullable(?array $value): ?string
    {
        if ($value===null) { return null; }
        try { return json_encode($value,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); }
        catch (\JsonException $e) { throw new \InvalidArgumentException('Could not encode AI metadata JSON: '.$e->getMessage(),0,$e); }
    }
}

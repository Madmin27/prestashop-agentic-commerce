<?php

namespace PrestaShopAgenticCommerce\Validation;

final class JsonPayloadValidator
{
    public static function decodeNullable(?string $json): ?array
    {
        if ($json === null || trim($json) === '') {
            return null;
        }

        try {
            $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new \InvalidArgumentException('Invalid JSON payload: ' . $e->getMessage(), 0, $e);
        }

        if (!is_array($decoded)) {
            throw new \InvalidArgumentException('Metadata JSON must decode to an object or array.');
        }

        return $decoded;
    }

    /** @return array<string,mixed>|null */
    public static function decodeObjectNullable(?string $json): ?array
    {
        if ($json === null || trim($json) === '') {
            return null;
        }
        if (substr(ltrim($json), 0, 1) !== '{') {
            throw new \InvalidArgumentException('Metadata JSON must be an object.');
        }

        return self::decodeNullable($json);
    }

    public static function encodeNullable(?array $value): ?string
    {
        if ($value === null) {
            return null;
        }

        try {
            return json_encode(
                $value,
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            );
        } catch (\JsonException $e) {
            throw new \InvalidArgumentException('Could not encode metadata JSON: ' . $e->getMessage(), 0, $e);
        }
    }
}

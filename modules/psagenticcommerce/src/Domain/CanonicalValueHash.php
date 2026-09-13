<?php

namespace PrestaShopAgenticCommerce\Domain;

final class CanonicalValueHash
{
    /**
     * Stable SHA-256 over a recursively canonicalized JSON representation.
     * Associative-object keys are sorted; list order is preserved.
     */
    public static function fromValue($value): string
    {
        $normalized = self::normalize($value);
        $json = json_encode(
            $normalized,
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION
        );

        return hash('sha256', $json);
    }

    private static function normalize($value)
    {
        if (!is_array($value)) {
            return $value;
        }

        if (self::isList($value)) {
            return array_map([self::class, 'normalize'], $value);
        }

        ksort($value, SORT_STRING);
        foreach ($value as $key => $item) {
            $value[$key] = self::normalize($item);
        }

        return $value;
    }

    private static function isList(array $value): bool
    {
        $expected = 0;
        foreach ($value as $key => $_) {
            if ($key !== $expected) {
                return false;
            }
            ++$expected;
        }

        return true;
    }
}

<?php

use PHPUnit\Framework\TestCase;
use PrestaShopAgenticCommerce\Domain\CanonicalValueHash;

final class CanonicalValueHashTest extends TestCase
{
    public function testAssociativeKeyOrderDoesNotChangeHash(): void
    {
        $left = ['unit' => 'mm', 'value' => 8.0];
        $right = ['value' => 8.0, 'unit' => 'mm'];

        self::assertSame(
            CanonicalValueHash::fromValue($left),
            CanonicalValueHash::fromValue($right)
        );
    }

    public function testIntegralFloatAndIntegerAreEquivalent(): void
    {
        self::assertSame(
            CanonicalValueHash::fromValue(8),
            CanonicalValueHash::fromValue(8.0)
        );
    }

    public function testListOrderChangesHash(): void
    {
        self::assertNotSame(
            CanonicalValueHash::fromValue(['a', 'b']),
            CanonicalValueHash::fromValue(['b', 'a'])
        );
    }
}

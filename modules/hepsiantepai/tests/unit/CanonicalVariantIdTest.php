<?php

use Hepsiantep\Ai\Domain\CanonicalVariantId;
use PHPUnit\Framework\TestCase;

final class CanonicalVariantIdTest extends TestCase
{
    public function testBuildsDeterministicSourceScopedId(): void
    {
        self::assertSame('ps-2-123-456', CanonicalVariantId::fromPrestaShop(2, 123, 456));
        self::assertSame('ps-2-123-0', CanonicalVariantId::fromPrestaShop(2, 123, 0));
    }

    public function testRejectsInvalidSourceIdentity(): void
    {
        $this->expectException(InvalidArgumentException::class);
        CanonicalVariantId::fromPrestaShop(0, 123, 0);
    }
}

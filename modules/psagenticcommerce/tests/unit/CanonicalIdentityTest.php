<?php
use PHPUnit\Framework\TestCase;
use PrestaShopAgenticCommerce\Domain\CanonicalProductId;
use PrestaShopAgenticCommerce\Domain\CanonicalVariantId;
final class CanonicalIdentityTest extends TestCase {
  public function testIdentity(): void {
    self::assertSame('ps-2-123', CanonicalProductId::fromPrestaShop(2,123));
    self::assertSame('ps-2-123-456', CanonicalVariantId::fromPrestaShop(2,123,456));
  }
}

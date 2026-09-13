<?php
use PHPUnit\Framework\TestCase;
use PrestaShopAgenticCommerce\Validation\JsonPayloadValidator;
final class JsonPayloadValidatorTest extends TestCase {
  public function testRoundTrip(): void {
    $v=['x'=>1];
    self::assertSame($v, JsonPayloadValidator::decodeNullable(JsonPayloadValidator::encodeNullable($v)));
  }
}

<?php

use PHPUnit\Framework\TestCase;
use PrestaShopAgenticCommerce\Validation\JsonPayloadValidator;

final class JsonPayloadValidatorTest extends TestCase
{
    public function testRoundTrip(): void
    {
        $value = ['x' => 1];
        self::assertSame(
            $value,
            JsonPayloadValidator::decodeNullable(JsonPayloadValidator::encodeNullable($value))
        );
    }

    public function testObjectDecoderRejectsListPayload(): void
    {
        $this->expectException(InvalidArgumentException::class);
        JsonPayloadValidator::decodeObjectNullable('["x"]');
    }

    public function testObjectDecoderRejectsMalformedPayload(): void
    {
        $this->expectException(InvalidArgumentException::class);
        JsonPayloadValidator::decodeObjectNullable('{bad json}');
    }
}

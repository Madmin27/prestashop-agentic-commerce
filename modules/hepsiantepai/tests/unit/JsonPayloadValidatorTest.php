<?php

use Hepsiantep\Ai\Validation\JsonPayloadValidator;
use PHPUnit\Framework\TestCase;

final class JsonPayloadValidatorTest extends TestCase
{
    public function testRoundTrip(): void
    {
        $payload = ['recommended_for' => ['crafts']];
        $encoded = JsonPayloadValidator::encodeNullable($payload);

        self::assertNotNull($encoded);
        self::assertSame($payload, JsonPayloadValidator::decodeNullable($encoded));
    }

    public function testRejectsInvalidJson(): void
    {
        $this->expectException(InvalidArgumentException::class);
        JsonPayloadValidator::decodeNullable('{invalid');
    }

    public function testNullAndBlankRemainNull(): void
    {
        self::assertNull(JsonPayloadValidator::decodeNullable(null));
        self::assertNull(JsonPayloadValidator::decodeNullable('   '));
        self::assertNull(JsonPayloadValidator::encodeNullable(null));
    }
}

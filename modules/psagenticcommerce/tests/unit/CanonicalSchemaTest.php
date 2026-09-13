<?php

use PHPUnit\Framework\TestCase;

final class CanonicalSchemaTest extends TestCase
{
    public function testSchemaIsValidJsonAndDeclaresCountryContext(): void
    {
        $path = dirname(__DIR__, 4) . '/schemas/canonical-product-v1.schema.json';
        $raw = file_get_contents($path);
        self::assertIsString($raw);

        $schema = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('1.0', $schema['properties']['schema_version']['const']);
        self::assertContains('id_country', $schema['properties']['context']['required']);
        self::assertContains('country', $schema['properties']['context']['required']);
        self::assertSame('^[A-Z]{2}$', $schema['properties']['context']['properties']['country']['pattern']);
    }
}

<?php

use PHPUnit\Framework\TestCase;
use PrestaShopAgenticCommerce\Export\AiJson\RepresentationKey;

final class RepresentationKeyTest extends TestCase
{
    public function testRepresentationDimensionsChangeCacheKeyAndPath(): void
    {
        $tr = new RepresentationKey(1, 1, 'tr', 'tr-TR', 1, 'TRY', 224, 'TR');
        $us = new RepresentationKey(1, 1, 'tr', 'tr-TR', 2, 'USD', 21, 'US');

        self::assertNotSame($tr->cacheKey(), $us->cacheKey());
        self::assertSame('/ai/v1/s1/tr-TR/TRY/TR/catalog.json', $tr->catalogPath());
        self::assertSame(
            '/ai/v1/s1/tr-TR/TRY/TR/products/ps-1-123-456.json',
            $tr->productPath('ps-1-123-456')
        );
    }

    public function testLocaleUnderscoreNormalizesToHyphen(): void
    {
        $key = new RepresentationKey(3, 2, 'EN', 'en_US', 4, 'usd', 21, 'us');

        self::assertSame('/ai/v1/s3/en-US/USD/US', $key->pathPrefix());
        self::assertSame('en', $key->toArray()['language']);
    }
}

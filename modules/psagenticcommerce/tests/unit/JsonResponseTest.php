<?php

use PHPUnit\Framework\TestCase;
use PrestaShopAgenticCommerce\Http\JsonResponse;

final class JsonResponseTest extends TestCase
{
    public function testEtagIsStableAndConditionalMatchingWorks(): void
    {
        $etag = JsonResponse::etag("{\"a\":1}\n");

        self::assertSame($etag, JsonResponse::etag("{\"a\":1}\n"));
        self::assertTrue(JsonResponse::isNotModified($etag, $etag));
        self::assertTrue(JsonResponse::isNotModified($etag, '"other", ' . $etag));
        self::assertTrue(JsonResponse::isNotModified($etag, '*'));
        self::assertFalse(JsonResponse::isNotModified($etag, '"other"'));
    }
}

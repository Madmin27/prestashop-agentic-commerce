<?php

use PHPUnit\Framework\TestCase;
use PrestaShopAgenticCommerce\Export\AiJson\AiJsonCacheStore;

final class AiJsonCacheStoreTest extends TestCase
{
    public function testShopGenerationInvalidatesExistingEntriesWithoutTimestampRace(): void
    {
        $directory = sys_get_temp_dir() . '/psagenticcommerce-test-' . bin2hex(random_bytes(6));
        $cache = new AiJsonCacheStore($directory);

        $cache->putForShop('catalog-key', "{\"v\":1}\n", 1);
        self::assertSame("{\"v\":1}\n", $cache->getForShop('catalog-key', 300, 1));

        $cache->invalidateShop(1);
        self::assertNull($cache->getForShop('catalog-key', 300, 1));

        $cache->putForShop('catalog-key', "{\"v\":2}\n", 1);
        self::assertSame("{\"v\":2}\n", $cache->getForShop('catalog-key', 300, 1));
    }

    public function testShopInvalidationDoesNotInvalidateOtherShopGeneration(): void
    {
        $directory = sys_get_temp_dir() . '/psagenticcommerce-test-' . bin2hex(random_bytes(6));
        $cache = new AiJsonCacheStore($directory);

        $cache->putForShop('shop-one', 'one', 1);
        $cache->putForShop('shop-two', 'two', 2);
        $cache->invalidateShop(1);

        self::assertNull($cache->getForShop('shop-one', 300, 1));
        self::assertSame('two', $cache->getForShop('shop-two', 300, 2));
    }
}

<?php

use FD\PrismUcp\Catalog\CatalogLookupResult;
use FD\PrismUcp\Catalog\CatalogProviderInterface;
use FD\PrismUcp\Catalog\CatalogProviderRegistry;
use FD\PrismUcp\Catalog\CatalogSearchResult;
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/src/Catalog/CatalogSearchResult.php';
require_once dirname(__DIR__, 2) . '/src/Catalog/CatalogLookupResult.php';
require_once dirname(__DIR__, 2) . '/src/Catalog/CatalogProviderInterface.php';
require_once dirname(__DIR__, 2) . '/src/Catalog/CatalogProviderRegistry.php';

final class CatalogProviderRegistryTest extends TestCase
{
    public function testRegistersOneProvider(): void
    {
        $registry = new CatalogProviderRegistry();
        $provider = $this->provider();
        $registry->register($provider);

        self::assertTrue($registry->hasProvider());
        self::assertSame($provider, $registry->getProvider());
    }

    public function testRejectsAmbiguousMultipleProviders(): void
    {
        $registry = new CatalogProviderRegistry();
        $registry->register($this->provider());

        $this->expectException(LogicException::class);
        $registry->register($this->provider());
    }

    private function provider(): CatalogProviderInterface
    {
        return new class implements CatalogProviderInterface {
            public function search(array $params): CatalogSearchResult
            {
                return new CatalogSearchResult([], 0, false);
            }

            public function lookup(array $ids): CatalogLookupResult
            {
                return new CatalogLookupResult([]);
            }
        };
    }
}

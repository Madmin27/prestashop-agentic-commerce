<?php
use FD\PrismUcp\Catalog\CatalogLookupResult;
use FD\PrismUcp\Catalog\CatalogProviderInterface;
use FD\PrismUcp\Catalog\CatalogProviderRegistry;
use FD\PrismUcp\Catalog\CatalogSearchResult;
use PHPUnit\Framework\TestCase;
require_once dirname(__DIR__,2).'/src/Catalog/CatalogSearchResult.php';
require_once dirname(__DIR__,2).'/src/Catalog/CatalogLookupResult.php';
require_once dirname(__DIR__,2).'/src/Catalog/CatalogProviderInterface.php';
require_once dirname(__DIR__,2).'/src/Catalog/CatalogProviderRegistry.php';
final class CatalogProviderRegistryTest extends TestCase {
 public function testRegistersOneProvider():void{$r=new CatalogProviderRegistry();$p=$this->provider();$r->register($p);self::assertTrue($r->hasProvider());self::assertSame($p,$r->getProvider());}
 public function testRejectsMultipleProviders():void{$r=new CatalogProviderRegistry();$r->register($this->provider());$this->expectException(LogicException::class);$r->register($this->provider());}
 private function provider():CatalogProviderInterface{return new class implements CatalogProviderInterface{public function search(array $p):CatalogSearchResult{return new CatalogSearchResult([],0,false);}public function lookup(array $ids):CatalogLookupResult{return new CatalogLookupResult([]);}};}
}

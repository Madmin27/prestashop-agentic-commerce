<?php

use FD\PrismUcp\Catalog\CatalogSearchResult;
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/src/Catalog/CatalogSearchResult.php';

final class CatalogSearchResultTest extends TestCase
{
    public function testRequiresCursorWhenNextPageExists(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new CatalogSearchResult([], 20, true);
    }

    public function testCarriesOpaqueCursor(): void
    {
        $result = new CatalogSearchResult([], 20, true, 'bzoxMA');
        self::assertTrue($result->hasNextPage);
        self::assertSame('bzoxMA', $result->cursor);
    }
}

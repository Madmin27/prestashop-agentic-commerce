<?php
namespace FD\PrismUcp\Catalog;
if (!defined('_PS_VERSION_')) { exit; }
interface CatalogProviderInterface
{
    public function search(array $params): CatalogSearchResult;
    public function lookup(array $ids): CatalogLookupResult;
}

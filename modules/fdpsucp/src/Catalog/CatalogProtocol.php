<?php

namespace FD\PrismUcp\Catalog;

if (!defined('_PS_VERSION_')) {
    exit;
}

final class CatalogProtocol
{
    public const VERSION = '2026-08-25';

    public static function specBase(): string
    {
        return 'https://ucp.dev/' . self::VERSION;
    }
}

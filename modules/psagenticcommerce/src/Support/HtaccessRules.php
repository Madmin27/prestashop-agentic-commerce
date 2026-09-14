<?php

namespace PrestaShopAgenticCommerce\Support;

if (!defined('_PS_VERSION_')) {
    exit;
}

final class HtaccessRules
{
    public const MARKER = '# ~~psagenticcommerce~~';
    private const PS_MARKER = '# ~~start~~';

    public static function block(): string
    {
        return self::MARKER . " start — PrestaShop Agentic Commerce (do not edit inside this block)\n"
            . "<IfModule mod_rewrite.c>\n"
            . "RewriteEngine On\n"
            . "RewriteRule ^\\.well-known/ai-catalog\\.json$ index.php?fc=module&module=psagenticcommerce&controller=discovery [QSA,L]\n"
            . "RewriteRule ^ai/v1/s([1-9][0-9]*)/([^/]+)/([A-Za-z]{3})/([A-Za-z]{2})/catalog\\.json$ index.php?fc=module&module=psagenticcommerce&controller=ai&ai_resource=catalog&id_shop=$1&locale=$2&currency=$3&country=$4 [QSA,L]\n"
            . "RewriteRule ^ai/v1/s([1-9][0-9]*)/([^/]+)/([A-Za-z]{3})/([A-Za-z]{2})/products/(ps-[0-9]+-[0-9]+-[0-9]+)\\.json$ index.php?fc=module&module=psagenticcommerce&controller=ai&ai_resource=product&id_shop=$1&locale=$2&currency=$3&country=$4&canonical_variant_id=$5 [QSA,L]\n"
            . "</IfModule>\n"
            . self::MARKER . " end\n";
    }

    public static function contains(string $htaccess): bool
    {
        return strpos($htaccess, self::MARKER) !== false;
    }

    public static function apply(string $htaccess): string
    {
        $withoutOldBlock = self::remove($htaccess);
        $block = self::block();
        $pos = strpos($withoutOldBlock, self::PS_MARKER);
        if ($pos === false) {
            return $withoutOldBlock === '' ? $block : $block . "\n" . $withoutOldBlock;
        }

        return substr($withoutOldBlock, 0, $pos) . $block . "\n" . substr($withoutOldBlock, $pos);
    }

    public static function remove(string $htaccess): string
    {
        $marker = preg_quote(self::MARKER, '#');
        $pattern = '#' . $marker . ' start.*?' . $marker . " end\\n?\\n?#s";
        return (string) preg_replace($pattern, '', $htaccess);
    }
}

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
            . "</IfModule>\n"
            . self::MARKER . " end\n";
    }

    public static function contains(string $htaccess): bool
    {
        return strpos($htaccess, self::MARKER) !== false;
    }

    public static function apply(string $htaccess): string
    {
        if (self::contains($htaccess)) {
            return $htaccess;
        }

        $block = self::block();
        $pos = strpos($htaccess, self::PS_MARKER);
        if ($pos === false) {
            return $htaccess === '' ? $block : $block . "\n" . $htaccess;
        }

        return substr($htaccess, 0, $pos) . $block . "\n" . substr($htaccess, $pos);
    }

    public static function remove(string $htaccess): string
    {
        $marker = preg_quote(self::MARKER, '#');
        $pattern = '#' . $marker . ' start.*?' . $marker . " end\\n?\\n?#s";
        return (string) preg_replace($pattern, '', $htaccess);
    }
}

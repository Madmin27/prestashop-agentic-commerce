<?php
namespace PrestaShopAgenticCommerce\Domain;
if (!defined('_PS_VERSION_')) { exit; }
final class CanonicalProductId
{
    public static function fromPrestaShop(int $idShop, int $idProduct): string
    {
        if ($idShop < 1 || $idProduct < 1) { throw new \InvalidArgumentException('Invalid PrestaShop product identity.'); }
        return sprintf('ps-%d-%d', $idShop, $idProduct);
    }
}

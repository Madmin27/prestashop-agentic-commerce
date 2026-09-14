<?php
namespace PrestaShopAgenticCommerce\Domain;
if (!defined('_PS_VERSION_')) { exit; }
final class CanonicalVariantId
{
    public static function fromPrestaShop(int $idShop, int $idProduct, int $idProductAttribute = 0): string
    {
        if ($idShop < 1 || $idProduct < 1 || $idProductAttribute < 0) { throw new \InvalidArgumentException('Invalid PrestaShop variant identity.'); }
        return sprintf('ps-%d-%d-%d', $idShop, $idProduct, $idProductAttribute);
    }
}

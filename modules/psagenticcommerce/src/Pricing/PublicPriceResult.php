<?php

namespace PrestaShopAgenticCommerce\Pricing;

final class PublicPriceResult
{
    private float $price;
    private string $currency;
    private string $asOf;

    public function __construct(float $price, string $currency, string $asOf)
    {
        if ($price < 0) {
            throw new \InvalidArgumentException('Public price cannot be negative.');
        }
        if (!preg_match('/^[A-Z]{3}$/', $currency)) {
            throw new \InvalidArgumentException('Currency must be an ISO 4217 code.');
        }

        $this->price = $price;
        $this->currency = $currency;
        $this->asOf = $asOf;
    }

    public function price(): float
    {
        return $this->price;
    }

    public function currency(): string
    {
        return $this->currency;
    }

    public function asOf(): string
    {
        return $this->asOf;
    }
}

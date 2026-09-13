<?php

namespace PrestaShopAgenticCommerce\Pricing;

final class PublicPriceResult
{
    private float $price;
    private string $currency;
    private int $idCountry;
    private string $country;
    private string $asOf;

    public function __construct(
        float $price,
        string $currency,
        int $idCountry,
        string $country,
        string $asOf
    ) {
        if ($price < 0) {
            throw new \InvalidArgumentException('Public price cannot be negative.');
        }
        if (!preg_match('/^[A-Z]{3}$/', $currency)) {
            throw new \InvalidArgumentException('Currency must be an ISO 4217 code.');
        }
        if ($idCountry < 1 || !preg_match('/^[A-Z]{2}$/', $country)) {
            throw new \InvalidArgumentException('A valid ISO country is required for public tax pricing.');
        }
        if (strtotime($asOf) === false) {
            throw new \InvalidArgumentException('Public price timestamp must be a valid date-time.');
        }

        $this->price = $price;
        $this->currency = $currency;
        $this->idCountry = $idCountry;
        $this->country = $country;
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

    public function idCountry(): int
    {
        return $this->idCountry;
    }

    public function country(): string
    {
        return $this->country;
    }

    public function asOf(): string
    {
        return $this->asOf;
    }
}

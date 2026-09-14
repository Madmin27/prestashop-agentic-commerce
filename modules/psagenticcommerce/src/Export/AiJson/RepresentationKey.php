<?php

namespace PrestaShopAgenticCommerce\Export\AiJson;

use PrestaShopAgenticCommerce\Domain\CanonicalProductDTO;

final class RepresentationKey implements \JsonSerializable
{
    private int $idShop;
    private int $idLanguage;
    private string $language;
    private string $locale;
    private int $idCurrency;
    private string $currency;
    private int $idCountry;
    private string $country;

    public function __construct(
        int $idShop,
        int $idLanguage,
        string $language,
        string $locale,
        int $idCurrency,
        string $currency,
        int $idCountry,
        string $country
    ) {
        $locale = str_replace('_', '-', trim($locale));
        $language = strtolower(trim($language));
        $currency = strtoupper(trim($currency));
        $country = strtoupper(trim($country));

        if ($idShop < 1 || $idLanguage < 1 || $idCurrency < 1 || $idCountry < 1) {
            throw new \InvalidArgumentException('Representation identifiers must be positive.');
        }
        if ($language === '' || preg_match('/^[A-Za-z0-9-]+$/', $locale) !== 1) {
            throw new \InvalidArgumentException('Invalid language or locale representation.');
        }
        if (preg_match('/^[A-Z]{3}$/', $currency) !== 1 || preg_match('/^[A-Z]{2}$/', $country) !== 1) {
            throw new \InvalidArgumentException('Invalid currency or country representation.');
        }

        $this->idShop = $idShop;
        $this->idLanguage = $idLanguage;
        $this->language = $language;
        $this->locale = $locale;
        $this->idCurrency = $idCurrency;
        $this->currency = $currency;
        $this->idCountry = $idCountry;
        $this->country = $country;
    }

    public static function fromCanonical(CanonicalProductDTO $dto): self
    {
        $data = $dto->toArray();
        $source = $data['source'];
        $context = $data['context'];

        return new self(
            (int) $source['id_shop'],
            (int) $context['id_language'],
            (string) $context['language'],
            (string) $context['locale'],
            (int) $context['id_currency'],
            (string) $context['currency'],
            (int) $context['id_country'],
            (string) $context['country']
        );
    }

    public function idShop(): int { return $this->idShop; }
    public function idLanguage(): int { return $this->idLanguage; }
    public function idCurrency(): int { return $this->idCurrency; }
    public function idCountry(): int { return $this->idCountry; }
    public function language(): string { return $this->language; }
    public function locale(): string { return $this->locale; }
    public function currency(): string { return $this->currency; }
    public function country(): string { return $this->country; }

    public function equals(self $other): bool
    {
        return $this->descriptor() === $other->descriptor();
    }

    public function cacheKey(): string
    {
        return 'ai-json:v1:' . hash('sha256', $this->descriptor());
    }

    public function catalogCacheKey(): string
    {
        return $this->cacheKey() . ':catalog';
    }

    public function productCacheKey(string $canonicalVariantId): string
    {
        return $this->cacheKey() . ':product:' . hash('sha256', $canonicalVariantId);
    }

    public function pathPrefix(): string
    {
        return sprintf(
            '/ai/v1/s%d/%s/%s/%s',
            $this->idShop,
            rawurlencode($this->locale),
            $this->currency,
            $this->country
        );
    }

    public function catalogPath(): string
    {
        return $this->pathPrefix() . '/catalog.json';
    }

    public function productPath(string $canonicalVariantId): string
    {
        if (trim($canonicalVariantId) === '') {
            throw new \InvalidArgumentException('Canonical variant id is required for a product path.');
        }

        return $this->pathPrefix() . '/products/' . rawurlencode($canonicalVariantId) . '.json';
    }

    public function productPatternPath(): string
    {
        return $this->pathPrefix() . '/products/{canonical_variant_id}.json';
    }

    /** @return array<string,mixed> */
    public function toArray(): array
    {
        return [
            'shop_id' => $this->idShop,
            'language' => $this->language,
            'locale' => $this->locale,
            'currency' => $this->currency,
            'country' => $this->country,
        ];
    }

    /** @return array<string,mixed> */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    private function descriptor(): string
    {
        return implode('|', [
            (string) $this->idShop,
            (string) $this->idLanguage,
            $this->language,
            $this->locale,
            (string) $this->idCurrency,
            $this->currency,
            (string) $this->idCountry,
            $this->country,
        ]);
    }
}

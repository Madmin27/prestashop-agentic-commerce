<?php

namespace PrestaShopAgenticCommerce\Export\OpenAi;

if (!defined('_PS_VERSION_')) {
    exit;
}

final class OpenAiFeedConfigResolver
{
    public const SELLER_NAME = 'PSAGENTIC_OPENAI_SELLER_NAME';
    public const SELLER_URL = 'PSAGENTIC_OPENAI_SELLER_URL';
    public const RETURN_POLICY_URL = 'PSAGENTIC_OPENAI_RETURN_POLICY_URL';
    public const TARGET_COUNTRIES = 'PSAGENTIC_OPENAI_TARGET_COUNTRIES';
    public const STORE_COUNTRY = 'PSAGENTIC_OPENAI_STORE_COUNTRY';
    public const DEFAULT_BRAND = 'PSAGENTIC_OPENAI_DEFAULT_BRAND';
    public const ELIGIBLE_SEARCH = 'PSAGENTIC_OPENAI_ELIGIBLE_SEARCH';
    public const ELIGIBLE_CHECKOUT = 'PSAGENTIC_OPENAI_ELIGIBLE_CHECKOUT';
    public const PRIVACY_POLICY_URL = 'PSAGENTIC_OPENAI_PRIVACY_POLICY_URL';
    public const TERMS_URL = 'PSAGENTIC_OPENAI_TERMS_URL';
    public const ADS_ELIGIBLE = 'PSAGENTIC_OPENAI_ADS_ELIGIBLE';

    public function resolve(int $idShop): OpenAiFeedConfig
    {
        if ($idShop < 1) {
            throw new \InvalidArgumentException('Shop id must be positive.');
        }

        return new OpenAiFeedConfig(
            $this->required(self::SELLER_NAME, $idShop),
            $this->required(self::SELLER_URL, $idShop),
            $this->required(self::RETURN_POLICY_URL, $idShop),
            $this->countries($this->required(self::TARGET_COUNTRIES, $idShop)),
            strtoupper($this->required(self::STORE_COUNTRY, $idShop)),
            $this->optional(self::DEFAULT_BRAND, $idShop),
            $this->bool(self::ELIGIBLE_SEARCH, $idShop, true),
            $this->bool(self::ELIGIBLE_CHECKOUT, $idShop, false),
            $this->optional(self::PRIVACY_POLICY_URL, $idShop),
            $this->optional(self::TERMS_URL, $idShop),
            $this->nullableBool(self::ADS_ELIGIBLE, $idShop)
        );
    }

    private function required(string $key, int $idShop): string
    {
        $value = $this->optional($key, $idShop);
        if ($value === null) {
            throw new \RuntimeException('Missing OpenAI feed configuration: ' . $key);
        }
        return $value;
    }

    private function optional(string $key, int $idShop): ?string
    {
        $value = \Configuration::get($key, null, null, $idShop);
        if ($value === false || $value === null || trim((string) $value) === '') {
            return null;
        }
        return trim((string) $value);
    }

    private function bool(string $key, int $idShop, bool $default): bool
    {
        $value = $this->optional($key, $idShop);
        if ($value === null) {
            return $default;
        }
        return in_array(strtolower($value), ['1', 'true', 'yes', 'on'], true);
    }

    private function nullableBool(string $key, int $idShop): ?bool
    {
        $value = $this->optional($key, $idShop);
        if ($value === null) {
            return null;
        }
        return in_array(strtolower($value), ['1', 'true', 'yes', 'on'], true);
    }

    /** @return array<int,string> */
    private function countries(string $value): array
    {
        return array_values(array_filter(array_map(
            static fn(string $country): string => strtoupper(trim($country)),
            preg_split('/[,;\s]+/', $value) ?: []
        )));
    }
}

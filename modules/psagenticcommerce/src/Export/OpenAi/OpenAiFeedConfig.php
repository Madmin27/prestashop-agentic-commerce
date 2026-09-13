<?php

namespace PrestaShopAgenticCommerce\Export\OpenAi;

if (!defined('_PS_VERSION_')) {
    exit;
}

final class OpenAiFeedConfig
{
    /** @var array<int,string> */
    private array $targetCountries;

    /**
     * @param array<int,string> $targetCountries
     */
    public function __construct(
        private string $sellerName,
        private string $sellerUrl,
        private string $returnPolicyUrl,
        array $targetCountries,
        private string $storeCountry,
        private ?string $defaultBrand = null,
        private bool $eligibleSearch = true,
        private bool $eligibleCheckout = false,
        private ?string $privacyPolicyUrl = null,
        private ?string $termsUrl = null,
        private ?bool $adsEligible = null
    ) {
        $this->sellerName = trim($sellerName);
        $this->sellerUrl = trim($sellerUrl);
        $this->returnPolicyUrl = trim($returnPolicyUrl);
        $this->storeCountry = strtoupper(trim($storeCountry));
        $this->defaultBrand = $defaultBrand === null ? null : trim($defaultBrand);
        $this->privacyPolicyUrl = $privacyPolicyUrl === null ? null : trim($privacyPolicyUrl);
        $this->termsUrl = $termsUrl === null ? null : trim($termsUrl);

        if ($this->sellerName === '' || $this->length($this->sellerName) > 70) {
            throw new \InvalidArgumentException('OpenAI seller name must be 1-70 characters.');
        }
        foreach ([
            'seller_url' => $this->sellerUrl,
            'return_policy' => $this->returnPolicyUrl,
        ] as $field => $url) {
            $this->assertHttpUrl($field, $url);
        }
        if (preg_match('/^[A-Z]{2}$/', $this->storeCountry) !== 1) {
            throw new \InvalidArgumentException('OpenAI store country must be ISO 3166-1 alpha-2.');
        }
        if ($this->defaultBrand !== null && ($this->defaultBrand === '' || $this->length($this->defaultBrand) > 70)) {
            throw new \InvalidArgumentException('OpenAI default brand must be 1-70 characters when provided.');
        }
        if ($this->eligibleCheckout && !$this->eligibleSearch) {
            throw new \InvalidArgumentException('OpenAI checkout eligibility requires search eligibility.');
        }
        if ($this->eligibleCheckout) {
            if ($this->privacyPolicyUrl === null || $this->termsUrl === null) {
                throw new \InvalidArgumentException('Checkout-eligible OpenAI feeds require privacy policy and terms URLs.');
            }
            $this->assertHttpUrl('seller_privacy_policy', $this->privacyPolicyUrl);
            $this->assertHttpUrl('seller_tos', $this->termsUrl);
        }

        $normalized = [];
        foreach ($targetCountries as $country) {
            $country = strtoupper(trim((string) $country));
            if (preg_match('/^[A-Z]{2}$/', $country) !== 1) {
                throw new \InvalidArgumentException('OpenAI target countries must use ISO 3166-1 alpha-2 codes.');
            }
            $normalized[$country] = $country;
        }
        if ($normalized === []) {
            throw new \InvalidArgumentException('OpenAI feed requires at least one target country.');
        }
        ksort($normalized, SORT_STRING);
        $this->targetCountries = array_values($normalized);
    }

    public function sellerName(): string { return $this->sellerName; }
    public function sellerUrl(): string { return $this->sellerUrl; }
    public function returnPolicyUrl(): string { return $this->returnPolicyUrl; }
    /** @return array<int,string> */
    public function targetCountries(): array { return $this->targetCountries; }
    public function storeCountry(): string { return $this->storeCountry; }
    public function defaultBrand(): ?string { return $this->defaultBrand; }
    public function eligibleSearch(): bool { return $this->eligibleSearch; }
    public function eligibleCheckout(): bool { return $this->eligibleCheckout; }
    public function privacyPolicyUrl(): ?string { return $this->privacyPolicyUrl; }
    public function termsUrl(): ?string { return $this->termsUrl; }
    public function adsEligible(): ?bool { return $this->adsEligible; }

    private function assertHttpUrl(string $field, string $url): void
    {
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            throw new \InvalidArgumentException('Invalid OpenAI ' . $field . ' URL.');
        }
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        if (!in_array($scheme, ['http', 'https'], true)) {
            throw new \InvalidArgumentException('OpenAI ' . $field . ' URL must use HTTP or HTTPS.');
        }
    }

    private function length(string $value): int
    {
        return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
    }
}

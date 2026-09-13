<?php

namespace PrestaShopAgenticCommerce\Config;

use PrestaShopAgenticCommerce\Export\OpenAi\OpenAiFeedConfigResolver;
use PrestaShopAgenticCommerce\Export\OpenAi\OpenAiSftpConfigResolver;

if (!defined('_PS_VERSION_')) {
    exit;
}

final class OpenAiSettings
{
    /** @return array<string,string> */
    public function values(int $idShop): array
    {
        $keys = [
            OpenAiFeedConfigResolver::SELLER_NAME,
            OpenAiFeedConfigResolver::SELLER_URL,
            OpenAiFeedConfigResolver::RETURN_POLICY_URL,
            OpenAiFeedConfigResolver::TARGET_COUNTRIES,
            OpenAiFeedConfigResolver::STORE_COUNTRY,
            OpenAiFeedConfigResolver::DEFAULT_BRAND,
            OpenAiFeedConfigResolver::ELIGIBLE_SEARCH,
            OpenAiFeedConfigResolver::ELIGIBLE_CHECKOUT,
            OpenAiFeedConfigResolver::PRIVACY_POLICY_URL,
            OpenAiFeedConfigResolver::TERMS_URL,
            OpenAiFeedConfigResolver::ADS_ELIGIBLE,
            OpenAiSftpConfigResolver::ENABLED,
            OpenAiSftpConfigResolver::HOST,
            OpenAiSftpConfigResolver::PORT,
            OpenAiSftpConfigResolver::USERNAME,
            OpenAiSftpConfigResolver::AUTH_MODE,
            OpenAiSftpConfigResolver::PRIVATE_KEY_FILE,
            OpenAiSftpConfigResolver::PUBLIC_KEY_FILE,
            OpenAiSftpConfigResolver::REMOTE_PATH,
            OpenAiSftpConfigResolver::HOST_KEY_SHA256,
            OpenAiSftpConfigResolver::HOST_KEY_MD5,
            OpenAiSftpConfigResolver::KNOWN_HOSTS_FILE,
            OpenAiSftpConfigResolver::TIMEOUT,
        ];

        $values = [];
        foreach ($keys as $key) {
            $value = \Configuration::get($key, null, null, $idShop);
            $values[$key] = $value === false || $value === null ? '' : (string) $value;
        }
        return $values;
    }

    /** @param array<string,mixed> $input */
    public function save(int $idShop, array $input): void
    {
        $this->put($idShop, OpenAiFeedConfigResolver::SELLER_NAME, trim((string) ($input[OpenAiFeedConfigResolver::SELLER_NAME] ?? '')));
        $this->putUrl($idShop, OpenAiFeedConfigResolver::SELLER_URL, $input, true);
        $this->putUrl($idShop, OpenAiFeedConfigResolver::RETURN_POLICY_URL, $input, true);
        $this->putCountries($idShop, OpenAiFeedConfigResolver::TARGET_COUNTRIES, $input);
        $this->putCountry($idShop, OpenAiFeedConfigResolver::STORE_COUNTRY, $input);
        $this->put($idShop, OpenAiFeedConfigResolver::DEFAULT_BRAND, trim((string) ($input[OpenAiFeedConfigResolver::DEFAULT_BRAND] ?? '')));
        $this->put($idShop, OpenAiFeedConfigResolver::ELIGIBLE_SEARCH, !empty($input[OpenAiFeedConfigResolver::ELIGIBLE_SEARCH]) ? '1' : '0');
        $this->put($idShop, OpenAiFeedConfigResolver::ELIGIBLE_CHECKOUT, !empty($input[OpenAiFeedConfigResolver::ELIGIBLE_CHECKOUT]) ? '1' : '0');
        $this->putUrl($idShop, OpenAiFeedConfigResolver::PRIVACY_POLICY_URL, $input, false);
        $this->putUrl($idShop, OpenAiFeedConfigResolver::TERMS_URL, $input, false);
        $ads = (string) ($input[OpenAiFeedConfigResolver::ADS_ELIGIBLE] ?? '');
        $this->put($idShop, OpenAiFeedConfigResolver::ADS_ELIGIBLE, in_array($ads, ['0', '1'], true) ? $ads : '');

        $this->put($idShop, OpenAiSftpConfigResolver::ENABLED, !empty($input[OpenAiSftpConfigResolver::ENABLED]) ? '1' : '0');
        $this->put($idShop, OpenAiSftpConfigResolver::HOST, trim((string) ($input[OpenAiSftpConfigResolver::HOST] ?? '')));
        $this->put($idShop, OpenAiSftpConfigResolver::PORT, (string) max(1, min(65535, (int) ($input[OpenAiSftpConfigResolver::PORT] ?? 22))));
        $this->put($idShop, OpenAiSftpConfigResolver::USERNAME, trim((string) ($input[OpenAiSftpConfigResolver::USERNAME] ?? '')));
        $authMode = (string) ($input[OpenAiSftpConfigResolver::AUTH_MODE] ?? 'secret');
        if (!in_array($authMode, ['secret', 'public_key'], true)) {
            throw new \InvalidArgumentException('Invalid SFTP authentication mode.');
        }
        $this->put($idShop, OpenAiSftpConfigResolver::AUTH_MODE, $authMode);
        $this->put($idShop, OpenAiSftpConfigResolver::PRIVATE_KEY_FILE, trim((string) ($input[OpenAiSftpConfigResolver::PRIVATE_KEY_FILE] ?? '')));
        $this->put($idShop, OpenAiSftpConfigResolver::PUBLIC_KEY_FILE, trim((string) ($input[OpenAiSftpConfigResolver::PUBLIC_KEY_FILE] ?? '')));
        $this->put($idShop, OpenAiSftpConfigResolver::REMOTE_PATH, trim((string) ($input[OpenAiSftpConfigResolver::REMOTE_PATH] ?? 'products.jsonl.gz')));
        $this->put($idShop, OpenAiSftpConfigResolver::HOST_KEY_SHA256, trim((string) ($input[OpenAiSftpConfigResolver::HOST_KEY_SHA256] ?? '')));
        $this->put($idShop, OpenAiSftpConfigResolver::HOST_KEY_MD5, trim((string) ($input[OpenAiSftpConfigResolver::HOST_KEY_MD5] ?? '')));
        $this->put($idShop, OpenAiSftpConfigResolver::KNOWN_HOSTS_FILE, trim((string) ($input[OpenAiSftpConfigResolver::KNOWN_HOSTS_FILE] ?? '')));
        $this->put($idShop, OpenAiSftpConfigResolver::TIMEOUT, (string) max(5, min(300, (int) ($input[OpenAiSftpConfigResolver::TIMEOUT] ?? 60))));
    }

    /** @param array<string,mixed> $input */
    private function putUrl(int $idShop, string $key, array $input, bool $required): void
    {
        $value = trim((string) ($input[$key] ?? ''));
        if ($value === '' && !$required) {
            $this->put($idShop, $key, '');
            return;
        }
        if ($value === '' || filter_var($value, FILTER_VALIDATE_URL) === false) {
            throw new \InvalidArgumentException('Invalid URL for ' . $key);
        }
        $scheme = strtolower((string) parse_url($value, PHP_URL_SCHEME));
        if (!in_array($scheme, ['http', 'https'], true)) {
            throw new \InvalidArgumentException('Only HTTP(S) URLs are allowed.');
        }
        $this->put($idShop, $key, $value);
    }

    /** @param array<string,mixed> $input */
    private function putCountries(int $idShop, string $key, array $input): void
    {
        $parts = preg_split('/[,;\s]+/', strtoupper((string) ($input[$key] ?? ''))) ?: [];
        $parts = array_values(array_unique(array_filter(array_map('trim', $parts))));
        if ($parts === []) {
            throw new \InvalidArgumentException('At least one target country is required.');
        }
        foreach ($parts as $country) {
            if (preg_match('/^[A-Z]{2}$/', $country) !== 1) {
                throw new \InvalidArgumentException('Invalid target country: ' . $country);
            }
        }
        $this->put($idShop, $key, implode(',', $parts));
    }

    /** @param array<string,mixed> $input */
    private function putCountry(int $idShop, string $key, array $input): void
    {
        $country = strtoupper(trim((string) ($input[$key] ?? '')));
        if (preg_match('/^[A-Z]{2}$/', $country) !== 1) {
            throw new \InvalidArgumentException('Invalid store country.');
        }
        $this->put($idShop, $key, $country);
    }

    private function put(int $idShop, string $key, string $value): void
    {
        if (!\Configuration::updateValue($key, $value, false, null, $idShop)) {
            throw new \RuntimeException('Unable to save configuration: ' . $key);
        }
    }
}

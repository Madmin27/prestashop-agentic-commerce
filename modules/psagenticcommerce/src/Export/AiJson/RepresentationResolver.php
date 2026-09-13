<?php

namespace PrestaShopAgenticCommerce\Export\AiJson;

if (!defined('_PS_VERSION_')) {
    exit;
}

final class RepresentationResolver
{
    /**
     * @return array{representation:RepresentationKey,original_language:mixed,original_currency:mixed,original_country:mixed}
     */
    public function apply(
        \Context $context,
        int $idShop,
        string $locale,
        string $currencyIso,
        string $countryIso
    ): array {
        if ((int) ($context->shop->id ?? 0) !== $idShop) {
            throw new \RuntimeException('Requested shop does not match the current host shop.');
        }

        $locale = str_replace('_', '-', trim($locale));
        $currencyIso = strtoupper(trim($currencyIso));
        $countryIso = strtoupper(trim($countryIso));

        $languageRow = null;
        foreach (\Language::getLanguages(true, $idShop, false) as $row) {
            $candidate = str_replace('_', '-', (string) ($row['locale'] ?? ''));
            if (strcasecmp($candidate, $locale) === 0) {
                $languageRow = $row;
                break;
            }
        }
        if ($languageRow === null) {
            throw new \RuntimeException('Requested locale is not active for this shop.');
        }

        $idCurrency = (int) \Currency::getIdByIsoCode($currencyIso, $idShop);
        $currency = new \Currency($idCurrency);
        if ($idCurrency < 1 || !\Validate::isLoadedObject($currency) || !(bool) $currency->active) {
            throw new \RuntimeException('Requested currency is not active for this shop.');
        }

        $idCountry = (int) \Configuration::get('PS_COUNTRY_DEFAULT', null, null, $idShop);
        $country = new \Country($idCountry, (int) $languageRow['id_lang']);
        if (!\Validate::isLoadedObject($country)
            || strtoupper((string) $country->iso_code) !== $countryIso
        ) {
            throw new \RuntimeException('Requested country is not the public catalog tax country.');
        }

        $language = new \Language((int) $languageRow['id_lang']);
        if (!\Validate::isLoadedObject($language)) {
            throw new \RuntimeException('Requested language could not be loaded.');
        }

        $originalLanguage = $context->language;
        $originalCurrency = $context->currency;
        $originalCountry = $context->country ?? null;

        $context->language = $language;
        $context->currency = $currency;
        $context->country = $country;

        return [
            'representation' => new RepresentationKey(
                $idShop,
                (int) $language->id,
                (string) $language->iso_code,
                (string) ($language->locale ?: $locale),
                (int) $currency->id,
                (string) $currency->iso_code,
                $idCountry,
                $countryIso
            ),
            'original_language' => $originalLanguage,
            'original_currency' => $originalCurrency,
            'original_country' => $originalCountry,
        ];
    }

    /** @param array<string,mixed> $state */
    public function restore(\Context $context, array $state): void
    {
        $context->language = $state['original_language'];
        $context->currency = $state['original_currency'];
        $context->country = $state['original_country'];
    }
}

<?php

namespace PrestaShopAgenticCommerce\Export\AiJson;

if (!defined('_PS_VERSION_')) {
    exit;
}

final class RepresentationResolver
{
    /**
     * Resolve a representation requested in the URL and apply it to Context.
     * The caller must restore the returned original objects in a finally block.
     *
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

        $languageRow = \Db::getInstance()->getRow(
            'SELECT l.id_lang, l.iso_code, l.locale FROM `' . _DB_PREFIX_ . 'lang` l '
            . 'INNER JOIN `' . _DB_PREFIX_ . 'lang_shop` ls ON ls.id_lang = l.id_lang '
            . 'WHERE ls.id_shop = ' . (int) $idShop . ' AND l.active = 1 '
            . "AND REPLACE(l.locale, '_', '-') = '" . pSQL($locale) . "'"
        );
        if (!$languageRow) {
            throw new \RuntimeException('Requested locale is not active for this shop.');
        }

        $currencyRow = \Db::getInstance()->getRow(
            'SELECT c.id_currency, c.iso_code FROM `' . _DB_PREFIX_ . 'currency` c '
            . 'INNER JOIN `' . _DB_PREFIX_ . 'currency_shop` cs ON cs.id_currency = c.id_currency '
            . 'WHERE cs.id_shop = ' . (int) $idShop . ' AND c.active = 1 '
            . "AND c.iso_code = '" . pSQL($currencyIso) . "'"
        );
        if (!$currencyRow) {
            throw new \RuntimeException('Requested currency is not active for this shop.');
        }

        $idCountry = (int) \Configuration::get('PS_COUNTRY_DEFAULT');
        $country = new \Country($idCountry, (int) $languageRow['id_lang']);
        if (!\Validate::isLoadedObject($country)
            || strtoupper((string) $country->iso_code) !== $countryIso
        ) {
            throw new \RuntimeException('Requested country is not the public catalog tax country.');
        }

        $language = new \Language((int) $languageRow['id_lang']);
        $currency = new \Currency((int) $currencyRow['id_currency']);
        if (!\Validate::isLoadedObject($language) || !\Validate::isLoadedObject($currency)) {
            throw new \RuntimeException('Requested representation objects could not be loaded.');
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

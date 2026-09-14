<?php

namespace PrestaShopAgenticCommerce\Export\OpenAi;

use PrestaShopAgenticCommerce\Domain\CanonicalProductDTO;

if (!defined('_PS_VERSION_')) {
    exit;
}

final class OpenAiProductFeedExporter
{
    public function __construct(private OpenAiFeedConfig $config)
    {
    }

    /** @return array<string,mixed> */
    public function row(CanonicalProductDTO $dto): array
    {
        $data = $dto->toArray();
        $identity = is_array($data['identity'] ?? null) ? $data['identity'] : [];
        $content = is_array($data['content'] ?? null) ? $data['content'] : [];
        $commercial = is_array($data['commercial'] ?? null) ? $data['commercial'] : [];
        $links = is_array($data['links'] ?? null) ? $data['links'] : [];

        if (($commercial['show_price'] ?? false) !== true || !is_numeric($commercial['price'] ?? null)) {
            throw new \InvalidArgumentException('OpenAI feed requires a public product price.');
        }
        $price = (float) $commercial['price'];
        if ($price <= 0) {
            throw new \InvalidArgumentException('OpenAI feed price must be positive.');
        }

        $currency = strtoupper(trim((string) ($commercial['currency'] ?? '')));
        if (preg_match('/^[A-Z]{3}$/', $currency) !== 1) {
            throw new \InvalidArgumentException('OpenAI feed requires an ISO 4217 currency.');
        }

        $availability = (string) ($commercial['availability'] ?? 'unknown');
        if ($availability === 'preorder') {
            throw new \InvalidArgumentException(
                'OpenAI pre_order rows require availability_date; canonical availability dates are not supported yet.'
            );
        }

        $title = $this->requiredText((string) ($identity['title'] ?? ''), 'title', 150);
        $description = trim((string) ($content['description'] ?? ''));
        if ($description === '') {
            $description = trim((string) ($content['short_description'] ?? ''));
        }
        $description = $this->requiredText($description, 'description', 5000);

        $url = $this->requiredHttpUrl((string) ($links['canonical_web'] ?? ''), 'url');
        $imageUrl = $this->primaryImage($data);
        $brand = trim((string) ($identity['brand'] ?? ''));
        if ($brand === '') {
            $brand = (string) ($this->config->defaultBrand() ?? '');
        }
        $brand = $this->requiredText($brand, 'brand', 70);

        $row = [
            'is_eligible_search' => $this->config->eligibleSearch(),
            'is_eligible_checkout' => $this->config->eligibleCheckout(),
            'item_id' => $this->requiredText((string) ($data['canonical_variant_id'] ?? ''), 'item_id', 100),
            'title' => $title,
            'description' => $description,
            'url' => $url,
            'brand' => $brand,
            'image_url' => $imageUrl,
            'price' => $this->formatPrice($price, $currency),
            'availability' => $this->availability($availability),
            'seller_name' => $this->config->sellerName(),
            'seller_url' => $this->config->sellerUrl(),
            'return_policy' => $this->config->returnPolicyUrl(),
            'target_countries' => $this->config->targetCountries(),
            'store_country' => $this->config->storeCountry(),
        ];

        $gtin = $this->normalizeGtin($identity['gtin'] ?? null);
        if ($gtin !== null) {
            $row['gtin'] = $gtin;
        }
        $mpn = trim((string) ($identity['mpn'] ?? ''));
        if ($mpn !== '') {
            $row['mpn'] = $this->truncate($mpn, 70);
        }

        $material = $this->material($data);
        if ($material !== null) {
            $row['material'] = $this->truncate($material, 100);
        }

        $dimensions = $this->variantDict($data['variant_dimensions'] ?? []);
        $isVariant = (int) ($data['source']['id_product_attribute'] ?? 0) > 0;
        if ($isVariant) {
            $row['group_id'] = $this->requiredText((string) ($data['product_group_id'] ?? ''), 'group_id', 100);
            $row['listing_has_variations'] = true;
            if ($dimensions !== []) {
                $row['variant_dict'] = $dimensions;
            }
            $row['item_group_title'] = $title;
        } else {
            $row['listing_has_variations'] = false;
        }

        $adsEligible = $this->config->adsEligible();
        if ($adsEligible !== null) {
            $row['is_ads_eligible'] = $adsEligible;
        }

        if ($this->config->eligibleCheckout()) {
            $row['seller_privacy_policy'] = $this->config->privacyPolicyUrl();
            $row['seller_tos'] = $this->config->termsUrl();
        }

        return $row;
    }

    /** @param iterable<CanonicalProductDTO> $products */
    public function jsonl(iterable $products): string
    {
        $lines = [];
        foreach ($products as $product) {
            if (!$product instanceof CanonicalProductDTO) {
                throw new \InvalidArgumentException('OpenAI JSONL exporter accepts CanonicalProductDTO values only.');
            }
            $lines[] = json_encode(
                $this->row($product),
                JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
            );
        }

        return $lines === [] ? '' : implode("\n", $lines) . "\n";
    }

    /** @param array<string,mixed> $data */
    private function primaryImage(array $data): string
    {
        foreach (is_array($data['media'] ?? null) ? $data['media'] : [] as $media) {
            if (is_array($media) && ($media['type'] ?? null) === 'image' && !empty($media['url'])) {
                return $this->requiredHttpUrl((string) $media['url'], 'image_url');
            }
        }
        $linkImage = is_array($data['links'] ?? null) ? (string) ($data['links']['image'] ?? '') : '';
        if ($linkImage !== '') {
            return $this->requiredHttpUrl($linkImage, 'image_url');
        }

        throw new \InvalidArgumentException('OpenAI feed requires a public product image.');
    }

    private function availability(string $value): string
    {
        return match ($value) {
            'in_stock' => 'in_stock',
            'out_of_stock' => 'out_of_stock',
            'backorder' => 'backorder',
            default => 'unknown',
        };
    }

    private function formatPrice(float $price, string $currency): string
    {
        $number = rtrim(rtrim(number_format($price, 6, '.', ''), '0'), '.');
        return ($number === '' ? '0' : $number) . ' ' . $currency;
    }

    /** @param mixed $value */
    private function normalizeGtin($value): ?string
    {
        if ($value === null) {
            return null;
        }
        $normalized = preg_replace('/[\s-]+/', '', trim((string) $value)) ?? '';
        return preg_match('/^\d{8,14}$/', $normalized) === 1 ? $normalized : null;
    }

    /** @param array<string,mixed> $data */
    private function material(array $data): ?string
    {
        foreach (['verified_specs', 'declared_specs'] as $bucket) {
            $specs = is_array($data[$bucket] ?? null) ? $data[$bucket] : [];
            $value = $specs['material'] ?? null;
            if (is_scalar($value) && trim((string) $value) !== '') {
                return trim((string) $value);
            }
        }
        return null;
    }

    /** @param mixed $raw @return array<string,string> */
    private function variantDict($raw): array
    {
        if (!is_array($raw)) {
            return [];
        }
        $result = [];
        foreach ($raw as $dimension) {
            if (!is_array($dimension)) {
                continue;
            }
            $name = trim((string) ($dimension['group_name'] ?? ''));
            $value = trim((string) ($dimension['value'] ?? ''));
            if ($name !== '' && $value !== '') {
                $result[$name] = $value;
            }
        }
        ksort($result, SORT_STRING);
        return $result;
    }

    private function requiredText(string $value, string $field, int $maxLength): string
    {
        $value = trim($value);
        if ($value === '') {
            throw new \InvalidArgumentException('OpenAI feed requires ' . $field . '.');
        }
        return $this->truncate($value, $maxLength);
    }

    private function requiredHttpUrl(string $value, string $field): string
    {
        $value = trim($value);
        if (filter_var($value, FILTER_VALIDATE_URL) === false) {
            throw new \InvalidArgumentException('OpenAI feed requires a valid ' . $field . ' URL.');
        }
        $scheme = strtolower((string) parse_url($value, PHP_URL_SCHEME));
        if (!in_array($scheme, ['http', 'https'], true) || parse_url($value, PHP_URL_USER) !== null) {
            throw new \InvalidArgumentException('OpenAI feed ' . $field . ' must be a public HTTP(S) URL without credentials.');
        }
        return $value;
    }

    private function truncate(string $value, int $maxLength): string
    {
        if (function_exists('mb_substr')) {
            return mb_substr($value, 0, $maxLength, 'UTF-8');
        }
        return substr($value, 0, $maxLength);
    }
}

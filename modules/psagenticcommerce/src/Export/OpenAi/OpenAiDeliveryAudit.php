<?php

namespace PrestaShopAgenticCommerce\Export\OpenAi;

if (!defined('_PS_VERSION_')) {
    exit;
}

final class OpenAiDeliveryAudit
{
    public const LAST_STATUS = 'PSAGENTIC_OPENAI_LAST_STATUS';
    public const LAST_AT = 'PSAGENTIC_OPENAI_LAST_AT';
    public const LAST_MESSAGE = 'PSAGENTIC_OPENAI_LAST_MESSAGE';
    public const LAST_BYTES = 'PSAGENTIC_OPENAI_LAST_BYTES';
    public const LAST_EXPORTED = 'PSAGENTIC_OPENAI_LAST_EXPORTED';
    public const LAST_SKIPPED = 'PSAGENTIC_OPENAI_LAST_SKIPPED';

    /** @param array<string,mixed> $result */
    public function record(int $idShop, string $status, array $result = []): void
    {
        $this->put($idShop, self::LAST_STATUS, $status);
        $this->put($idShop, self::LAST_AT, gmdate('c'));
        $this->put($idShop, self::LAST_MESSAGE, substr((string) ($result['message'] ?? ''), 0, 1000));
        $this->put($idShop, self::LAST_BYTES, (string) ((int) ($result['bytes'] ?? 0)));
        $this->put($idShop, self::LAST_EXPORTED, (string) ((int) ($result['exported'] ?? 0)));
        $this->put($idShop, self::LAST_SKIPPED, (string) ((int) ($result['skipped'] ?? 0)));
    }

    /** @return array<string,string> */
    public function status(int $idShop): array
    {
        $result = [];
        foreach ([self::LAST_STATUS, self::LAST_AT, self::LAST_MESSAGE, self::LAST_BYTES, self::LAST_EXPORTED, self::LAST_SKIPPED] as $key) {
            $value = \Configuration::get($key, null, null, $idShop);
            $result[$key] = $value === false || $value === null ? '' : (string) $value;
        }
        return $result;
    }

    private function put(int $idShop, string $key, string $value): void
    {
        \Configuration::updateValue($key, $value, false, null, $idShop);
    }
}

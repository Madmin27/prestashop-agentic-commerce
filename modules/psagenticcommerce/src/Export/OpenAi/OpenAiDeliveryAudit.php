<?php

namespace PrestaShopAgenticCommerce\Export\OpenAi;

if (!defined('_PS_VERSION_')) { exit; }

final class OpenAiDeliveryAudit
{
    public const LAST_STATUS = 'PSAGENTIC_OPENAI_LAST_STATUS';
    public const LAST_AT = 'PSAGENTIC_OPENAI_LAST_AT';
    public const LAST_MESSAGE = 'PSAGENTIC_OPENAI_LAST_MESSAGE';
    public const LAST_BYTES = 'PSAGENTIC_OPENAI_LAST_BYTES';

    /** @param array<string,mixed> $result */
    public function record(int $idShop, string $status, array $result = []): void
    {
        Configuration::updateValue(self::LAST_STATUS, $status, false, null, $idShop);
        Configuration::updateValue(self::LAST_AT, gmdate('c'), false, null, $idShop);
        Configuration::updateValue(self::LAST_MESSAGE, (string) ($result['message'] ?? ''), false, null, $idShop);
        Configuration::updateValue(self::LAST_BYTES, (string) ((int) ($result['bytes'] ?? 0)), false, null, $idShop);
    }
}

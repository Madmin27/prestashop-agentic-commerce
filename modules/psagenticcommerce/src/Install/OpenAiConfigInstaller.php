<?php

namespace PrestaShopAgenticCommerce\Install;

if (!defined('_PS_VERSION_')) {
    exit;
}

final class OpenAiConfigInstaller
{
    public const CRON_TOKEN = 'PSAGENTIC_OPENAI_CRON_TOKEN';

    public function install(): bool
    {
        $existing = (string) \Configuration::get(self::CRON_TOKEN);
        if ($existing !== '') {
            return true;
        }

        try {
            $token = bin2hex(random_bytes(32));
        } catch (\Throwable $e) {
            return false;
        }

        return (bool) \Configuration::updateValue(self::CRON_TOKEN, $token);
    }
}

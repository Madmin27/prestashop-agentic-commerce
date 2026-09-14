<?php

namespace PrestaShopAgenticCommerce\Install;

use PrestaShopAgenticCommerce\Export\OpenAi\OpenAiFeedConfigResolver;
use PrestaShopAgenticCommerce\Export\OpenAi\OpenAiSftpConfigResolver;

if (!defined('_PS_VERSION_')) {
    exit;
}

final class OpenAiConfigInstaller
{
    public const CRON_TOKEN = 'PSAGENTIC_OPENAI_CRON_TOKEN';

    public function install(): bool
    {
        if ((string) \Configuration::get(self::CRON_TOKEN) === '') {
            try {
                if (!\Configuration::updateValue(self::CRON_TOKEN, bin2hex(random_bytes(32)))) {
                    return false;
                }
            } catch (\Throwable $e) {
                return false;
            }
        }

        $defaults = [
            OpenAiFeedConfigResolver::ELIGIBLE_SEARCH => '1',
            OpenAiFeedConfigResolver::ELIGIBLE_CHECKOUT => '0',
            OpenAiSftpConfigResolver::ENABLED => '0',
            OpenAiSftpConfigResolver::PORT => '22',
            OpenAiSftpConfigResolver::AUTH_MODE => 'secret',
            OpenAiSftpConfigResolver::REMOTE_PATH => 'products.jsonl.gz',
            OpenAiSftpConfigResolver::TIMEOUT => '60',
        ];
        foreach ($defaults as $key => $value) {
            if (\Configuration::get($key) === false && !\Configuration::updateValue($key, $value)) {
                return false;
            }
        }
        return true;
    }

    public function uninstall(): bool
    {
        \Configuration::deleteByName(self::CRON_TOKEN);
        \Configuration::deleteByName(OpenAiSftpConfigResolver::AUTH_SECRET);
        \Configuration::deleteByName(OpenAiSftpConfigResolver::KEY_PASSPHRASE);
        return true;
    }
}

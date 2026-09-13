<?php

namespace PrestaShopAgenticCommerce\Export\OpenAi;

if (!defined('_PS_VERSION_')) { exit; }

final class OpenAiSftpConfig
{
    /** @param array<string,mixed> $values */
    public function __construct(private array $values) {}

    /** @return mixed */
    public function get(string $key, $default = null)
    {
        return $this->values[$key] ?? $default;
    }

    public function enabled(): bool
    {
        return (bool) ($this->values['enabled'] ?? false);
    }
}

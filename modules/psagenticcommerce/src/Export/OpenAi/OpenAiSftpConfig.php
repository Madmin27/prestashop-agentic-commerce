<?php

namespace PrestaShopAgenticCommerce\Export\OpenAi;

if (!defined('_PS_VERSION_')) {
    exit;
}

final class OpenAiSftpConfig
{
    /** @param array<string,mixed> $values */
    public function __construct(private array $values)
    {
        $port = (int) ($values['port'] ?? 22);
        $timeout = (int) ($values['timeout'] ?? 60);
        if ($port < 1 || $port > 65535) {
            throw new \InvalidArgumentException('Invalid SFTP port.');
        }
        if ($timeout < 5 || $timeout > 300) {
            throw new \InvalidArgumentException('Invalid SFTP timeout.');
        }
        if (!in_array((string) ($values['auth_mode'] ?? 'secret'), ['secret', 'public_key'], true)) {
            throw new \InvalidArgumentException('Invalid SFTP authentication mode.');
        }
        $host = trim((string) ($values['host'] ?? ''));
        if ($host !== '' && ($host !== parse_url('sftp://' . $host, PHP_URL_HOST) || preg_match('/[\s\/@?#]/', $host))) {
            throw new \InvalidArgumentException('SFTP host must be a hostname or IP address without scheme, path or credentials.');
        }
    }

    /** @return mixed */
    public function get(string $key, $default = null)
    {
        return $this->values[$key] ?? $default;
    }

    public function enabled(): bool
    {
        return (bool) ($this->values['enabled'] ?? false);
    }

    public function assertReady(): void
    {
        if (!$this->enabled()) {
            throw new \RuntimeException('OpenAI SFTP delivery is disabled.');
        }
        foreach (['host', 'username', 'remote_path'] as $key) {
            if (trim((string) $this->get($key, '')) === '') {
                throw new \RuntimeException('Missing OpenAI SFTP setting: ' . $key);
            }
        }
        if ((string) $this->get('auth_mode', 'secret') === 'secret'
            && trim((string) $this->get('auth_secret', '')) === '') {
            throw new \RuntimeException('OpenAI SFTP authentication secret is required.');
        }
        if ((string) $this->get('auth_mode') === 'public_key'
            && trim((string) $this->get('private_key_file', '')) === '') {
            throw new \RuntimeException('OpenAI SFTP private key path is required.');
        }
        if (trim((string) $this->get('host_key_sha256', '')) === ''
            && trim((string) $this->get('host_key_md5', '')) === ''
            && trim((string) $this->get('known_hosts_file', '')) === '') {
            throw new \RuntimeException('OpenAI SFTP host-key verification is required.');
        }
    }
}

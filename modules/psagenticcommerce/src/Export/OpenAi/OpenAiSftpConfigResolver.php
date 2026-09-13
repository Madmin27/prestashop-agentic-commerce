<?php

namespace PrestaShopAgenticCommerce\Export\OpenAi;

use PrestaShopAgenticCommerce\Security\SecretCipher;

if (!defined('_PS_VERSION_')) {
    exit;
}

final class OpenAiSftpConfigResolver
{
    public const ENABLED = 'PSAGENTIC_OPENAI_SFTP_ENABLED';
    public const HOST = 'PSAGENTIC_OPENAI_SFTP_HOST';
    public const PORT = 'PSAGENTIC_OPENAI_SFTP_PORT';
    public const USERNAME = 'PSAGENTIC_OPENAI_SFTP_USERNAME';
    public const AUTH_MODE = 'PSAGENTIC_OPENAI_SFTP_AUTH_MODE';
    public const AUTH_SECRET = 'PSAGENTIC_OPENAI_SFTP_AUTH_SECRET';
    public const PRIVATE_KEY_FILE = 'PSAGENTIC_OPENAI_SFTP_PRIVATE_KEY_FILE';
    public const PUBLIC_KEY_FILE = 'PSAGENTIC_OPENAI_SFTP_PUBLIC_KEY_FILE';
    public const KEY_PASSPHRASE = 'PSAGENTIC_OPENAI_SFTP_KEY_PASSPHRASE';
    public const REMOTE_PATH = 'PSAGENTIC_OPENAI_SFTP_REMOTE_PATH';
    public const HOST_KEY_SHA256 = 'PSAGENTIC_OPENAI_SFTP_HOSTKEY_SHA256';
    public const HOST_KEY_MD5 = 'PSAGENTIC_OPENAI_SFTP_HOSTKEY_MD5';
    public const KNOWN_HOSTS_FILE = 'PSAGENTIC_OPENAI_SFTP_KNOWN_HOSTS';
    public const TIMEOUT = 'PSAGENTIC_OPENAI_SFTP_TIMEOUT';

    public function __construct(private ?SecretCipher $cipher = null)
    {
        $this->cipher ??= new SecretCipher();
    }

    public function resolve(int $idShop): OpenAiSftpConfig
    {
        if ($idShop < 1) {
            throw new \InvalidArgumentException('Shop id must be positive.');
        }

        return new OpenAiSftpConfig([
            'enabled' => $this->bool(self::ENABLED, $idShop, false),
            'host' => $this->text(self::HOST, $idShop),
            'port' => (int) ($this->text(self::PORT, $idShop) ?? '22'),
            'username' => $this->text(self::USERNAME, $idShop),
            'auth_mode' => $this->text(self::AUTH_MODE, $idShop) ?? 'secret',
            'auth_secret' => $this->secret(self::AUTH_SECRET, $idShop),
            'private_key_file' => $this->text(self::PRIVATE_KEY_FILE, $idShop),
            'public_key_file' => $this->text(self::PUBLIC_KEY_FILE, $idShop),
            'key_passphrase' => $this->secret(self::KEY_PASSPHRASE, $idShop),
            'remote_path' => $this->text(self::REMOTE_PATH, $idShop) ?? 'products.jsonl.gz',
            'host_key_sha256' => $this->text(self::HOST_KEY_SHA256, $idShop),
            'host_key_md5' => $this->text(self::HOST_KEY_MD5, $idShop),
            'known_hosts_file' => $this->text(self::KNOWN_HOSTS_FILE, $idShop),
            'timeout' => (int) ($this->text(self::TIMEOUT, $idShop) ?? '60'),
        ]);
    }

    private function text(string $key, int $idShop): ?string
    {
        $value = \Configuration::get($key, null, null, $idShop);
        if ($value === false || $value === null || trim((string) $value) === '') {
            return null;
        }
        return trim((string) $value);
    }

    private function bool(string $key, int $idShop, bool $default): bool
    {
        $value = $this->text($key, $idShop);
        if ($value === null) {
            return $default;
        }
        return in_array(strtolower($value), ['1', 'true', 'yes', 'on'], true);
    }

    private function secret(string $key, int $idShop): ?string
    {
        $value = $this->text($key, $idShop);
        return $value === null ? null : $this->cipher->decrypt($value);
    }
}

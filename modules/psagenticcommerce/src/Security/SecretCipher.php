<?php

namespace PrestaShopAgenticCommerce\Security;

if (!defined('_PS_VERSION_')) {
    exit;
}

final class SecretCipher
{
    private const PREFIX = 'v1:';
    private const CIPHER = 'aes-256-gcm';

    public function encrypt(string $plainText): string
    {
        if ($plainText === '') {
            return '';
        }
        if (!function_exists('openssl_encrypt')) {
            throw new \RuntimeException('OpenSSL extension is required to store OpenAI SFTP secrets securely.');
        }

        $iv = random_bytes(12);
        $tag = '';
        $cipherText = openssl_encrypt(
            $plainText,
            self::CIPHER,
            $this->key(),
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            'psagenticcommerce/openai-sftp',
            16
        );
        if ($cipherText === false || strlen($tag) !== 16) {
            throw new \RuntimeException('Unable to encrypt OpenAI SFTP secret.');
        }

        return self::PREFIX . base64_encode($iv . $tag . $cipherText);
    }

    public function decrypt(?string $encoded): ?string
    {
        if ($encoded === null || $encoded === '') {
            return null;
        }
        if (strncmp($encoded, self::PREFIX, strlen(self::PREFIX)) !== 0) {
            throw new \RuntimeException('Unsupported encrypted secret format.');
        }
        if (!function_exists('openssl_decrypt')) {
            throw new \RuntimeException('OpenSSL extension is required to read OpenAI SFTP secrets.');
        }

        $raw = base64_decode(substr($encoded, strlen(self::PREFIX)), true);
        if ($raw === false || strlen($raw) < 29) {
            throw new \RuntimeException('Corrupted OpenAI SFTP secret.');
        }

        $iv = substr($raw, 0, 12);
        $tag = substr($raw, 12, 16);
        $cipherText = substr($raw, 28);
        $plainText = openssl_decrypt(
            $cipherText,
            self::CIPHER,
            $this->key(),
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            'psagenticcommerce/openai-sftp'
        );
        if ($plainText === false) {
            throw new \RuntimeException('Unable to decrypt OpenAI SFTP secret.');
        }

        return $plainText;
    }

    private function key(): string
    {
        $material = defined('_COOKIE_KEY_') ? (string) _COOKIE_KEY_ : '';
        if ($material === '') {
            throw new \RuntimeException('PrestaShop cookie key is unavailable.');
        }

        return hash('sha256', 'psagenticcommerce|openai-sftp|' . $material, true);
    }
}

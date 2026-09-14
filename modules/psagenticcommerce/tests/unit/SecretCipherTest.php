<?php

use PHPUnit\Framework\TestCase;
use PrestaShopAgenticCommerce\Security\SecretCipher;

final class SecretCipherTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        if (!defined('_COOKIE_KEY_')) {
            define('_COOKIE_KEY_', 'test-cookie-key-for-agentic-commerce');
        }
    }

    public function testRoundTrip(): void
    {
        $cipher = new SecretCipher();
        $encoded = $cipher->encrypt('sensitive-value');

        self::assertStringStartsWith('v1:', $encoded);
        self::assertNotSame('sensitive-value', $encoded);
        self::assertSame('sensitive-value', $cipher->decrypt($encoded));
    }

    public function testTamperedCiphertextFailsClosed(): void
    {
        $cipher = new SecretCipher();
        $encoded = $cipher->encrypt('sensitive-value');
        $last = substr($encoded, -1);
        $tampered = substr($encoded, 0, -1) . ($last === 'A' ? 'B' : 'A');

        $this->expectException(RuntimeException::class);
        $cipher->decrypt($tampered);
    }
}

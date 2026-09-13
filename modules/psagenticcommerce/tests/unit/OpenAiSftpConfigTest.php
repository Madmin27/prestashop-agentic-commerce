<?php

use PHPUnit\Framework\TestCase;
use PrestaShopAgenticCommerce\Export\OpenAi\OpenAiSftpConfig;

final class OpenAiSftpConfigTest extends TestCase
{
    public function testReadyConfigurationAcceptsPinnedHostKey(): void
    {
        $config = new OpenAiSftpConfig([
            'enabled' => true,
            'host' => 'sftp.example.test',
            'port' => 22,
            'username' => 'merchant',
            'auth_mode' => 'secret',
            'auth_secret' => 'secret',
            'remote_path' => 'products.jsonl.gz',
            'host_key_sha256' => 'base64fingerprint',
            'timeout' => 60,
        ]);

        $config->assertReady();
        self::assertTrue($config->enabled());
    }

    public function testRejectsHostWithScheme(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new OpenAiSftpConfig([
            'enabled' => true,
            'host' => 'sftp://example.test',
            'port' => 22,
            'timeout' => 60,
        ]);
    }

    public function testRequiresHostKeyVerification(): void
    {
        $config = new OpenAiSftpConfig([
            'enabled' => true,
            'host' => 'sftp.example.test',
            'port' => 22,
            'username' => 'merchant',
            'auth_mode' => 'secret',
            'auth_secret' => 'secret',
            'remote_path' => 'products.jsonl.gz',
            'timeout' => 60,
        ]);

        $this->expectException(RuntimeException::class);
        $config->assertReady();
    }
}

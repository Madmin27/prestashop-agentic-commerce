<?php

namespace PrestaShopAgenticCommerce\Export\AiJson;

if (!defined('_PS_VERSION_')) {
    exit;
}

final class AiJsonCacheStore
{
    private string $directory;

    public function __construct(?string $directory = null)
    {
        $base = $directory ?: (defined('_PS_CACHE_DIR_') ? _PS_CACHE_DIR_ : sys_get_temp_dir());
        $this->directory = rtrim($base, '/\\') . DIRECTORY_SEPARATOR . 'psagenticcommerce-ai-json';
    }

    public function get(string $key, int $ttl): ?string
    {
        $path = $this->path($key);
        if (!is_file($path)) {
            return null;
        }
        if ($ttl >= 0 && (time() - (int) @filemtime($path)) > $ttl) {
            return null;
        }

        $body = @file_get_contents($path);
        return $body === false ? null : $body;
    }

    public function put(string $key, string $body): void
    {
        if (!is_dir($this->directory) && !@mkdir($this->directory, 0775, true) && !is_dir($this->directory)) {
            throw new \RuntimeException('Could not create AI JSON cache directory.');
        }

        $path = $this->path($key);
        $tmp = $path . '.' . bin2hex(random_bytes(6)) . '.tmp';
        if (@file_put_contents($tmp, $body, LOCK_EX) === false) {
            throw new \RuntimeException('Could not write AI JSON cache file.');
        }
        if (!@rename($tmp, $path)) {
            @unlink($tmp);
            throw new \RuntimeException('Could not atomically publish AI JSON cache file.');
        }
    }

    public function delete(string $key): void
    {
        $path = $this->path($key);
        if (is_file($path)) {
            @unlink($path);
        }
    }

    private function path(string $key): string
    {
        return $this->directory . DIRECTORY_SEPARATOR . hash('sha256', $key) . '.json';
    }
}

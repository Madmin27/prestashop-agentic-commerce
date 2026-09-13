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
        return $this->readBody($key, $ttl);
    }

    public function getForShop(string $key, int $ttl, int $idShop): ?string
    {
        $body = $this->readBody($key, $ttl);
        if ($body === null) {
            return null;
        }
        if ($idShop < 1) {
            return $body;
        }

        $entryGeneration = @file_get_contents($this->generationSidecar($key));
        if (!is_string($entryGeneration)
            || trim($entryGeneration) !== $this->shopGeneration($idShop)
        ) {
            return null;
        }

        return $body;
    }

    public function put(string $key, string $body): void
    {
        $this->atomicWrite($this->path($key), $body);
    }

    public function putForShop(string $key, string $body, int $idShop): void
    {
        if ($idShop < 1) {
            throw new \InvalidArgumentException('Shop id must be positive.');
        }

        $this->atomicWrite($this->path($key), $body);
        $this->atomicWrite($this->generationSidecar($key), $this->shopGeneration($idShop));
    }

    public function invalidateShop(int $idShop): void
    {
        if ($idShop < 1) {
            return;
        }

        $this->atomicWrite(
            $this->shopGenerationPath($idShop),
            bin2hex(random_bytes(16))
        );
    }

    public function delete(string $key): void
    {
        foreach ([$this->path($key), $this->generationSidecar($key)] as $path) {
            if (is_file($path)) {
                @unlink($path);
            }
        }
    }

    private function readBody(string $key, int $ttl): ?string
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

    private function shopGeneration(int $idShop): string
    {
        $path = $this->shopGenerationPath($idShop);
        if (!is_file($path)) {
            return '0';
        }

        $generation = @file_get_contents($path);
        return is_string($generation) && trim($generation) !== '' ? trim($generation) : '0';
    }

    private function shopGenerationPath(int $idShop): string
    {
        return $this->directory . DIRECTORY_SEPARATOR . 'shop-' . $idShop . '.generation';
    }

    private function generationSidecar(string $key): string
    {
        return $this->path($key) . '.generation';
    }

    private function path(string $key): string
    {
        return $this->directory . DIRECTORY_SEPARATOR . hash('sha256', $key) . '.json';
    }

    private function atomicWrite(string $path, string $contents): void
    {
        if (!is_dir($this->directory) && !@mkdir($this->directory, 0775, true) && !is_dir($this->directory)) {
            throw new \RuntimeException('Could not create AI JSON cache directory.');
        }

        $tmp = $path . '.' . bin2hex(random_bytes(6)) . '.tmp';
        if (@file_put_contents($tmp, $contents, LOCK_EX) === false) {
            @unlink($tmp);
            throw new \RuntimeException('Could not write AI JSON cache file.');
        }
        if (!@rename($tmp, $path)) {
            @unlink($tmp);
            throw new \RuntimeException('Could not atomically publish AI JSON cache file.');
        }
    }
}

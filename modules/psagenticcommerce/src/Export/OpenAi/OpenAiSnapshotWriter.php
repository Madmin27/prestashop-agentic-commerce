<?php

namespace PrestaShopAgenticCommerce\Export\OpenAi;

if (!defined('_PS_VERSION_')) {
    exit;
}

final class OpenAiSnapshotWriter
{
    public function writeGzip(OpenAiSnapshotResult $snapshot, string $targetPath): void
    {
        $directory = dirname($targetPath);
        if (!is_dir($directory) || !is_writable($directory)) {
            throw new \RuntimeException('OpenAI snapshot directory is not writable.');
        }

        $compressed = gzencode($snapshot->jsonl(), 9, ZLIB_ENCODING_GZIP);
        if ($compressed === false) {
            throw new \RuntimeException('Failed to gzip OpenAI snapshot.');
        }

        $tempPath = $targetPath . '.tmp.' . bin2hex(random_bytes(6));
        try {
            if (file_put_contents($tempPath, $compressed, LOCK_EX) === false) {
                throw new \RuntimeException('Failed to write OpenAI snapshot temporary file.');
            }
            if (!@rename($tempPath, $targetPath)) {
                throw new \RuntimeException('Failed to atomically publish OpenAI snapshot.');
            }
        } finally {
            if (is_file($tempPath)) {
                @unlink($tempPath);
            }
        }
    }
}

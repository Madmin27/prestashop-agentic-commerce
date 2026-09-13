<?php

use PHPUnit\Framework\TestCase;
use PrestaShopAgenticCommerce\Export\OpenAi\OpenAiSnapshotResult;
use PrestaShopAgenticCommerce\Export\OpenAi\OpenAiSnapshotWriter;

final class OpenAiSnapshotWriterTest extends TestCase
{
    public function testWritesReadableGzipSnapshot(): void
    {
        $directory = sys_get_temp_dir() . '/psagentic-openai-' . bin2hex(random_bytes(4));
        mkdir($directory, 0700, true);
        $path = $directory . '/products.jsonl.gz';

        try {
            $snapshot = new OpenAiSnapshotResult("{\"item_id\":\"A\"}\n", 1, []);
            (new OpenAiSnapshotWriter())->writeGzip($snapshot, $path);

            self::assertFileExists($path);
            self::assertSame($snapshot->jsonl(), gzdecode((string) file_get_contents($path)));
            self::assertSame([], glob($path . '.tmp.*') ?: []);
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
            if (is_dir($directory)) {
                rmdir($directory);
            }
        }
    }
}

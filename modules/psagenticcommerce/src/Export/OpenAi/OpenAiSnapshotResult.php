<?php

namespace PrestaShopAgenticCommerce\Export\OpenAi;

if (!defined('_PS_VERSION_')) {
    exit;
}

final class OpenAiSnapshotResult
{
    /**
     * @param array<int,array{id_product:int,id_product_attribute:int,error:string}> $skipped
     */
    public function __construct(
        private string $jsonl,
        private int $exportedCount,
        private array $skipped
    ) {
    }

    public function jsonl(): string { return $this->jsonl; }
    public function exportedCount(): int { return $this->exportedCount; }
    /** @return array<int,array{id_product:int,id_product_attribute:int,error:string}> */
    public function skipped(): array { return $this->skipped; }
}

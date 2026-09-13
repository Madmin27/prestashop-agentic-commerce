<?php

namespace PrestaShopAgenticCommerce\Export\OpenAi;

if (!defined('_PS_VERSION_')) { exit; }

final class OpenAiDeliveryService
{
    public function __construct(private OpenAiSftpConfigResolver $configResolver, private CurlSftpClient $client, private OpenAiDeliveryAudit $audit) {}

    public function run(int $idShop, string $localPath): array
    {
        $config = $this->configResolver->resolve($idShop);
        try {
            $result = $this->client->upload($localPath, $config);
            $this->audit->record($idShop, 'success', $result);
            return $result;
        } catch (\Throwable $e) {
            $this->audit->record($idShop, 'failed', ['message' => $e->getMessage()]);
            throw $e;
        }
    }
}

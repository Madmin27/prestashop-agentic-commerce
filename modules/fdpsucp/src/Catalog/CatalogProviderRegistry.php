<?php
namespace FD\PrismUcp\Catalog;
if (!defined('_PS_VERSION_')) { exit; }
final class CatalogProviderRegistry
{
    private ?CatalogProviderInterface $provider = null;
    public function register(CatalogProviderInterface $provider): void
    {
        if ($this->provider !== null) {
            throw new \LogicException('A catalog provider is already registered.');
        }
        $this->provider = $provider;
    }
    public function hasProvider(): bool { return $this->provider !== null; }
    public function getProvider(): ?CatalogProviderInterface { return $this->provider; }
    public static function collect(): self
    {
        $registry = new self();
        \Hook::exec('actionUcpCollectCatalogProviders', ['registry' => $registry]);
        return $registry;
    }
}

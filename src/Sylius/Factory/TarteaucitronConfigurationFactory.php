<?php

declare(strict_types=1);

namespace CylleneDigital\SyliusTarteaucitronPlugin\Sylius\Factory;

use CylleneDigital\SyliusTarteaucitronPlugin\Entity\TarteaucitronConfiguration;
use CylleneDigital\SyliusTarteaucitronPlugin\Sylius\Provider\CatalogConsentDefaults;
use CylleneDigital\SyliusTarteaucitronPlugin\Sylius\Synchronizer\ServiceCatalogSynchronizer;
use Sylius\Component\Channel\Model\ChannelInterface;

/**
 * @internal
 */
final readonly class TarteaucitronConfigurationFactory
{
    public function __construct(
        private CatalogConsentDefaults $catalogConsentDefaults,
        private ServiceCatalogSynchronizer $synchronizer,
    ) {
    }

    public function createForChannel(ChannelInterface $channel): TarteaucitronConfiguration
    {
        $configuration = new TarteaucitronConfiguration();
        $configuration->setChannel($channel);
        $configuration->setInitOptions($this->catalogConsentDefaults->defaultInitOptions());
        $this->synchronizer->ensureSeeded($configuration);

        return $configuration;
    }

    public function ensureCatalog(TarteaucitronConfiguration $configuration): void
    {
        $this->synchronizer->ensureSeeded($configuration);
    }
}

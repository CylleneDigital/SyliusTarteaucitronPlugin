<?php

declare(strict_types=1);

namespace CylleneDigital\SyliusTarteaucitronPlugin\Sylius\Factory;

use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Init\InitOptionCatalog;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Init\InitOptions;
use CylleneDigital\SyliusTarteaucitronPlugin\Entity\TarteaucitronConfiguration;
use CylleneDigital\SyliusTarteaucitronPlugin\Sylius\Synchronizer\ServiceCatalogSynchronizer;
use Sylius\Component\Channel\Model\ChannelInterface;

/**
 * @internal
 */
final readonly class TarteaucitronConfigurationFactory
{
    public function __construct(
        private InitOptionCatalog $initOptionCatalog,
        private ServiceCatalogSynchronizer $synchronizer,
    ) {
    }

    public function createForChannel(ChannelInterface $channel): TarteaucitronConfiguration
    {
        $configuration = new TarteaucitronConfiguration();
        $configuration->setChannel($channel);
        $configuration->setInitOptions(InitOptions::defaults($this->initOptionCatalog)->toArray());
        $this->synchronizer->ensureSeeded($configuration);

        return $configuration;
    }
}

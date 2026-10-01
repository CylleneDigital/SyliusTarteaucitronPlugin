<?php

declare(strict_types=1);

namespace CylleneDigital\SyliusTarteaucitronPlugin\Sylius\Synchronizer;

use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\TrackerRegistry;
use CylleneDigital\SyliusTarteaucitronPlugin\Entity\TarteaucitronConfiguration;
use CylleneDigital\SyliusTarteaucitronPlugin\Entity\TarteaucitronService;

/**
 * @internal
 */
final readonly class ServiceCatalogSynchronizer
{
    public function __construct(
        private TrackerRegistry $trackerRegistry,
    ) {
    }

    /**
     * Creates missing catalogue services (disabled, empty parameters). Does not persist.
     */
    public function ensureSeeded(TarteaucitronConfiguration $configuration): void
    {
        foreach ($this->trackerRegistry->seededByDefault() as $definition) {
            if (null !== $configuration->getServiceByType($definition->getType())) {
                continue;
            }

            $service = new TarteaucitronService();
            $service->setType($definition->getType());
            $service->setEnabled(false);

            $parameters = [];
            foreach ($definition->getParameters() as $parameter) {
                $parameters[$parameter->key] = '';
            }
            $service->setParameters($parameters);
            $configuration->addService($service);
        }
    }
}

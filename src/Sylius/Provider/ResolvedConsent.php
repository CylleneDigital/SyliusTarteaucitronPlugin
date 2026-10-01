<?php

declare(strict_types=1);

namespace CylleneDigital\SyliusTarteaucitronPlugin\Sylius\Provider;

use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\TrackerRuntimeState;
use CylleneDigital\SyliusTarteaucitronPlugin\Entity\TarteaucitronConfiguration;

/**
 * @internal
 */
final readonly class ResolvedConsent
{
    /**
     * @param array<string, mixed>        $init     tarteaucitron.init() payload (vendor jsKeys)
     * @param list<TrackerRuntimeState>   $services
     * @param array<string, array<string, string>> $localizedOptions per-locale links and texts (normalized)
     */
    public function __construct(
        public bool $enabled,
        public array $init,
        public array $services,
        public int $consentLifetimeDays = TarteaucitronConfiguration::DEFAULT_CONSENT_LIFETIME_DAYS,
        public array $localizedOptions = [],
    ) {
    }
}

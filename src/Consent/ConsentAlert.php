<?php

declare(strict_types=1);

namespace CylleneDigital\SyliusTarteaucitronPlugin\Consent;

use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\ConsentMode;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\TrackerRegistryInterface;

/**
 * @internal
 */
final readonly class ConsentAlert
{
    public function __construct(
        private TrackerRegistryInterface $trackerRegistry,
    ) {
    }

    /**
     * @param array<string, bool> $serviceEnabled
     *
     * @return array{level: string, message: string}|null
     */
    public function forOption(string $optionKey, array $serviceEnabled): ?array
    {
        return match ($optionKey) {
            'google_consent_mode' => $this->google($serviceEnabled),
            'bing_consent_mode' => $this->bing($serviceEnabled),
            default => null,
        };
    }

    /**
     * @param array<string, bool> $serviceEnabled
     *
     * @return array{level: string, message: string}|null
     */
    private function google(array $serviceEnabled): ?array
    {
        $googleOn = $this->anyEnabled($serviceEnabled, ConsentMode::Google);
        $gtmOn = $this->anyEnabled($serviceEnabled, ConsentMode::Gtm);

        if ($gtmOn && !$googleOn) {
            return [
                'level' => 'warning',
                'message' => 'cyllene_digital_sylius_tarteaucitron.ui.google_consent_mode_gtm_warning',
            ];
        }

        if (!$googleOn && !$gtmOn) {
            return [
                'level' => 'info',
                'message' => 'cyllene_digital_sylius_tarteaucitron.ui.google_consent_mode_no_service_hint',
            ];
        }

        return null;
    }

    /**
     * @param array<string, bool> $serviceEnabled
     *
     * @return array{level: string, message: string}|null
     */
    private function bing(array $serviceEnabled): ?array
    {
        if ($this->anyEnabled($serviceEnabled, ConsentMode::Bing)) {
            return null;
        }

        return [
            'level' => 'info',
            'message' => 'cyllene_digital_sylius_tarteaucitron.ui.bing_consent_mode_no_service_hint',
        ];
    }

    /**
     * @param array<string, bool> $serviceEnabled
     */
    private function anyEnabled(array $serviceEnabled, ConsentMode $mode): bool
    {
        foreach ($this->trackerRegistry->byConsentMode($mode) as $definition) {
            if ($serviceEnabled[$definition->getType()] ?? false) {
                return true;
            }
        }

        return false;
    }
}

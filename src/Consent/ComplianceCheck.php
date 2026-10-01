<?php

declare(strict_types=1);

namespace CylleneDigital\SyliusTarteaucitronPlugin\Consent;

use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Init\InitOptions;
use CylleneDigital\SyliusTarteaucitronPlugin\Entity\TarteaucitronConfiguration;

/**
 * Settings that move away from the CNIL cookie guidance. Warnings only: the admin keeps the last
 * word, the back office just says it out loud.
 *
 * @internal
 */
final readonly class ComplianceCheck
{
    public const LIFETIME_FIELD = 'consent_lifetime_days';

    /**
     * @return list<array{field: string, message: string}>
     */
    public function check(InitOptions $options, int $consentLifetimeDays): array
    {
        $ui = static fn (string $suffix): string => 'cyllene_digital_sylius_tarteaucitron.ui.compliance_' . $suffix;
        $findings = [];

        if (false === $options->get('high_privacy')) {
            $findings[] = ['field' => 'high_privacy', 'message' => $ui('high_privacy')];
        }

        // Without high privacy, tarteaucitron always shows « Accept all » on the banner.
        $acceptOnBanner = true === $options->get('accept_all_cta') || false === $options->get('high_privacy');
        if ($acceptOnBanner && false === $options->get('deny_all_cta')) {
            $findings[] = ['field' => 'deny_all_cta', 'message' => $ui('deny_all_cta')];
        }

        if ('true' === $options->get('service_default_state')) {
            $findings[] = ['field' => 'service_default_state', 'message' => $ui('service_default_state')];
        }

        if ($consentLifetimeDays > TarteaucitronConfiguration::DEFAULT_CONSENT_LIFETIME_DAYS) {
            $findings[] = ['field' => self::LIFETIME_FIELD, 'message' => $ui('consent_lifetime')];
        }

        return $findings;
    }
}

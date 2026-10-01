<?php

declare(strict_types=1);

namespace CylleneDigital\SyliusTarteaucitronPlugin\Tracker\Analytic;

use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\AbstractTrackerDefinition;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\ConsentMode;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\TrackerCategory;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\TrackerParameter;
use CylleneDigital\SyliusTarteaucitronPlugin\Tracker\TrackerAdminHints;

/**
 * @internal
 */
final class GtagTracker extends AbstractTrackerDefinition
{
    public function getType(): string
    {
        return 'gtag';
    }

    public function getCategory(): TrackerCategory
    {
        return TrackerCategory::Analytic;
    }

    public function getParameters(): array
    {
        return [
            new TrackerParameter('gtag_ua', 'gtagUa', placeholder: 'G-XXXXXXXXXX'),
            new TrackerParameter(
                'gtag_custom_domain',
                'gtagCustomDomain',
                required: false,
                placeholder: 'www.googletagmanager.com',
            ),
        ];
    }

    public function getConsentMode(): ConsentMode
    {
        return ConsentMode::Google;
    }

    public function getAdminHintTranslationKey(): string
    {
        return TrackerAdminHints::GOOGLE_NATIVE;
    }
}

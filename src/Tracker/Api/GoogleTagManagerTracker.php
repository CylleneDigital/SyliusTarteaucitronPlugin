<?php

declare(strict_types=1);

namespace CylleneDigital\SyliusTarteaucitronPlugin\Tracker\Api;

use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\AbstractTrackerDefinition;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\ConsentMode;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\TrackerCategory;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\TrackerParameter;
use CylleneDigital\SyliusTarteaucitronPlugin\Tracker\TrackerAdminHints;

/**
 * @internal
 */
final class GoogleTagManagerTracker extends AbstractTrackerDefinition
{
    public function getType(): string
    {
        return 'googletagmanager';
    }

    public function getCategory(): TrackerCategory
    {
        return TrackerCategory::Api;
    }

    public function getParameters(): array
    {
        return [
            new TrackerParameter('googletagmanager_id', 'googletagmanagerId', placeholder: 'GTM-XXXXXXX'),
            new TrackerParameter(
                'googletagmanager_custom_domain',
                'googletagmanagerCustomDomain',
                required: false,
                placeholder: 'www.googletagmanager.com',
            ),
        ];
    }

    public function getConsentMode(): ConsentMode
    {
        return ConsentMode::Gtm;
    }

    public function getAdminHintTranslationKey(): string
    {
        return TrackerAdminHints::GTM;
    }
}

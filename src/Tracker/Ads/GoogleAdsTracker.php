<?php

declare(strict_types=1);

namespace CylleneDigital\SyliusTarteaucitronPlugin\Tracker\Ads;

use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\AbstractTrackerDefinition;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\ConsentMode;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\TrackerCategory;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\TrackerParameter;
use CylleneDigital\SyliusTarteaucitronPlugin\Tracker\TrackerAdminHints;

/**
 * @internal
 */
final class GoogleAdsTracker extends AbstractTrackerDefinition
{
    public function getType(): string
    {
        return 'googleads';
    }

    public function getCategory(): TrackerCategory
    {
        return TrackerCategory::Ads;
    }

    public function getParameters(): array
    {
        return [
            new TrackerParameter('googleads_id', 'googleadsId', placeholder: 'AW-XXXXXXXXX'),
            new TrackerParameter(
                'googleads_custom_domain',
                'googleadsCustomDomain',
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

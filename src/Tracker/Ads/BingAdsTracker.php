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
final class BingAdsTracker extends AbstractTrackerDefinition
{
    public function getType(): string
    {
        return 'bingads';
    }

    public function getCategory(): TrackerCategory
    {
        return TrackerCategory::Ads;
    }

    public function getParameters(): array
    {
        return [
            new TrackerParameter('bingads_id', 'bingadsID', placeholder: 'XXXXXXX'),
        ];
    }

    public function getConsentMode(): ConsentMode
    {
        return ConsentMode::Bing;
    }

    public function getAdminHintTranslationKey(): string
    {
        return TrackerAdminHints::BING_NATIVE;
    }
}

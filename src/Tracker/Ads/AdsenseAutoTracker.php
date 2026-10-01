<?php

declare(strict_types=1);

namespace CylleneDigital\SyliusTarteaucitronPlugin\Tracker\Ads;

use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\AbstractTrackerDefinition;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\TrackerCategory;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\TrackerParameter;

/**
 * @internal
 */
final class AdsenseAutoTracker extends AbstractTrackerDefinition
{
    public function getType(): string
    {
        return 'adsenseauto';
    }

    public function getCategory(): TrackerCategory
    {
        return TrackerCategory::Ads;
    }

    public function getParameters(): array
    {
        return [
            new TrackerParameter(
                'adsense_ca_pub',
                'adsensecapub',
                placeholder: 'ca-pub-XXXXXXXXXXXXXXXX',
            ),
        ];
    }
}

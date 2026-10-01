<?php

declare(strict_types=1);

namespace CylleneDigital\SyliusTarteaucitronPlugin\Tracker\Ads;

use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\AbstractTrackerDefinition;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\TrackerCategory;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\TrackerParameter;

/**
 * @internal
 */
final class CriteoOneTagTracker extends AbstractTrackerDefinition
{
    public function getType(): string
    {
        return 'criteoonetag';
    }

    public function getCategory(): TrackerCategory
    {
        return TrackerCategory::Ads;
    }

    public function getParameters(): array
    {
        return [
            new TrackerParameter('criteoonetag_account', 'criteoonetagAccount', placeholder: '12345'),
        ];
    }
}

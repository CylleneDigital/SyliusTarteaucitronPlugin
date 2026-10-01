<?php

declare(strict_types=1);

namespace CylleneDigital\SyliusTarteaucitronPlugin\Tracker\Api;

use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\AbstractTrackerDefinition;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\TrackerCategory;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\TrackerParameter;

/**
 * @internal
 */
final class AbtastyTracker extends AbstractTrackerDefinition
{
    public function getType(): string
    {
        return 'abtasty';
    }

    public function getCategory(): TrackerCategory
    {
        return TrackerCategory::Api;
    }

    public function getParameters(): array
    {
        return [
            new TrackerParameter('abtasty_id', 'abtastyID'),
        ];
    }
}

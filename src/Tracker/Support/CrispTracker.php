<?php

declare(strict_types=1);

namespace CylleneDigital\SyliusTarteaucitronPlugin\Tracker\Support;

use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\AbstractTrackerDefinition;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\TrackerCategory;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\TrackerParameter;

/**
 * @internal
 */
final class CrispTracker extends AbstractTrackerDefinition
{
    public function getType(): string
    {
        return 'crisp';
    }

    public function getCategory(): TrackerCategory
    {
        return TrackerCategory::Support;
    }

    public function getParameters(): array
    {
        return [
            new TrackerParameter('crisp_id', 'crispID', placeholder: 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx'),
        ];
    }
}

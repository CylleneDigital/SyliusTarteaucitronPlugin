<?php

declare(strict_types=1);

namespace CylleneDigital\SyliusTarteaucitronPlugin\Tracker\Analytic;

use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\AbstractTrackerDefinition;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\TrackerCategory;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\TrackerParameter;

/**
 * @internal
 */
final class HubspotTracker extends AbstractTrackerDefinition
{
    public function getType(): string
    {
        return 'hubspot';
    }

    public function getCategory(): TrackerCategory
    {
        return TrackerCategory::Analytic;
    }

    public function getParameters(): array
    {
        return [
            new TrackerParameter('hubspot_id', 'hubspotId', placeholder: 'XXXXXXXX'),
            new TrackerParameter('hubspot_business_unit_id', 'hubspotBusinessUnitId', required: false),
        ];
    }
}

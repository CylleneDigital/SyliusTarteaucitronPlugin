<?php

declare(strict_types=1);

namespace CylleneDigital\SyliusTarteaucitronPlugin\Tracker\Analytic;

use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\AbstractTrackerDefinition;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\TrackerCategory;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\TrackerParameter;

/**
 * @internal
 */
final class PlausibleTracker extends AbstractTrackerDefinition
{
    public function getType(): string
    {
        return 'plausible';
    }

    public function getCategory(): TrackerCategory
    {
        return TrackerCategory::Analytic;
    }

    public function getParameters(): array
    {
        return [
            new TrackerParameter('plausible_domain', 'plausibleDomain', placeholder: 'example.com'),
            new TrackerParameter('plausible_endpoint', 'plausibleEndpoint', required: false, placeholder: 'plausible.io'),
        ];
    }
}

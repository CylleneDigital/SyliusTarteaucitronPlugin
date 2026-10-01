<?php

declare(strict_types=1);

namespace CylleneDigital\SyliusTarteaucitronPlugin\Tracker\Analytic;

use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\AbstractTrackerDefinition;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\TrackerCategory;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\TrackerParameter;

/**
 * @internal
 */
final class MatomoTracker extends AbstractTrackerDefinition
{
    public function getType(): string
    {
        return 'matomo';
    }

    public function getCategory(): TrackerCategory
    {
        return TrackerCategory::Analytic;
    }

    public function getParameters(): array
    {
        return [
            new TrackerParameter('matomo_id', 'matomoId', placeholder: '1'),
            new TrackerParameter('matomo_host', 'matomoHost', placeholder: 'https://matomo.example.com/'),
        ];
    }
}

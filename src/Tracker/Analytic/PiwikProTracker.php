<?php

declare(strict_types=1);

namespace CylleneDigital\SyliusTarteaucitronPlugin\Tracker\Analytic;

use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\AbstractTrackerDefinition;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\TrackerCategory;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\TrackerParameter;

/**
 * @internal
 */
final class PiwikProTracker extends AbstractTrackerDefinition
{
    public function getType(): string
    {
        return 'piwikpro';
    }

    public function getCategory(): TrackerCategory
    {
        return TrackerCategory::Analytic;
    }

    public function getParameters(): array
    {
        return [
            new TrackerParameter('piwik_pro_id', 'piwikProId', placeholder: 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx'),
            new TrackerParameter('piwik_pro_container', 'piwikProContainer', placeholder: 'example.containers.piwik.pro'),
        ];
    }
}

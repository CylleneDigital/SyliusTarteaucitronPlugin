<?php

declare(strict_types=1);

namespace CylleneDigital\SyliusTarteaucitronPlugin\Tracker\Analytic;

use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\AbstractTrackerDefinition;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\TrackerCategory;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\TrackerParameter;

/**
 * @internal
 */
final class HotjarTracker extends AbstractTrackerDefinition
{
    public function getType(): string
    {
        return 'hotjar';
    }

    public function getCategory(): TrackerCategory
    {
        return TrackerCategory::Analytic;
    }

    public function getParameters(): array
    {
        return [
            new TrackerParameter('hotjar_id', 'hotjarId', placeholder: 'XXXXXXX'),
            new TrackerParameter('hotjar_sv', 'HotjarSv', placeholder: '6'),
        ];
    }
}

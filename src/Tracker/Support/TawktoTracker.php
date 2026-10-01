<?php

declare(strict_types=1);

namespace CylleneDigital\SyliusTarteaucitronPlugin\Tracker\Support;

use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\AbstractTrackerDefinition;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\TrackerCategory;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\TrackerParameter;

/**
 * @internal
 */
final class TawktoTracker extends AbstractTrackerDefinition
{
    public function getType(): string
    {
        return 'tawkto';
    }

    public function getCategory(): TrackerCategory
    {
        return TrackerCategory::Support;
    }

    public function getParameters(): array
    {
        return [
            new TrackerParameter('tawkto_id', 'tawktoId', placeholder: 'xxxxxxxxxxxxxxxxxxxxxxxx'),
            new TrackerParameter('tawkto_widget_id', 'tawktoWidgetId', required: false, placeholder: 'default'),
        ];
    }
}

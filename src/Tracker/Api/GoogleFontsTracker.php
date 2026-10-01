<?php

declare(strict_types=1);

namespace CylleneDigital\SyliusTarteaucitronPlugin\Tracker\Api;

use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\AbstractTrackerDefinition;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\TrackerCategory;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\TrackerParameter;

/**
 * @internal
 */
final class GoogleFontsTracker extends AbstractTrackerDefinition
{
    public function getType(): string
    {
        return 'googlefonts';
    }

    public function getCategory(): TrackerCategory
    {
        return TrackerCategory::Api;
    }

    public function getParameters(): array
    {
        return [
            new TrackerParameter('google_fonts', 'googleFonts', placeholder: 'Roboto:400,700'),
        ];
    }
}

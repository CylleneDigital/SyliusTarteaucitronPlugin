<?php

declare(strict_types=1);

namespace CylleneDigital\SyliusTarteaucitronPlugin\Tracker\Api;

use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\AbstractTrackerDefinition;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\TrackerCategory;

/**
 * @internal
 */
final class GoogleMapsEmbedTracker extends AbstractTrackerDefinition
{
    public function getType(): string
    {
        return 'googlemapsembed';
    }

    public function getCategory(): TrackerCategory
    {
        return TrackerCategory::Api;
    }

    public function isEmbed(): bool
    {
        return true;
    }
}

<?php

declare(strict_types=1);

namespace CylleneDigital\SyliusTarteaucitronPlugin\Tracker\Social;

use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\AbstractTrackerDefinition;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\TrackerCategory;

/**
 * @internal
 */
final class PinterestTracker extends AbstractTrackerDefinition
{
    public function getType(): string
    {
        return 'pinterest';
    }

    public function getCategory(): TrackerCategory
    {
        return TrackerCategory::Social;
    }

    public function isEmbed(): bool
    {
        return true;
    }
}

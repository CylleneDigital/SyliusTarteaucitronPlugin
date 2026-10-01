<?php

declare(strict_types=1);

namespace CylleneDigital\SyliusTarteaucitronPlugin\Tracker\Social;

use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\AbstractTrackerDefinition;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\TrackerCategory;

/**
 * @internal
 */
final class TwitterTracker extends AbstractTrackerDefinition
{
    public function getType(): string
    {
        return 'twitter';
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

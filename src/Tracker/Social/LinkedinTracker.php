<?php

declare(strict_types=1);

namespace CylleneDigital\SyliusTarteaucitronPlugin\Tracker\Social;

use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\AbstractTrackerDefinition;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\TrackerCategory;

/**
 * @internal
 */
final class LinkedinTracker extends AbstractTrackerDefinition
{
    public function getType(): string
    {
        return 'linkedin';
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

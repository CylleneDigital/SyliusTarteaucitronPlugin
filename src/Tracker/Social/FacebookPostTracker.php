<?php

declare(strict_types=1);

namespace CylleneDigital\SyliusTarteaucitronPlugin\Tracker\Social;

use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\AbstractTrackerDefinition;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\TrackerCategory;

/**
 * @internal
 */
final class FacebookPostTracker extends AbstractTrackerDefinition
{
    public function getType(): string
    {
        return 'facebookpost';
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

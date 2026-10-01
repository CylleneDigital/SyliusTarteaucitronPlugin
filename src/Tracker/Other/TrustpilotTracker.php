<?php

declare(strict_types=1);

namespace CylleneDigital\SyliusTarteaucitronPlugin\Tracker\Other;

use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\AbstractTrackerDefinition;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\TrackerCategory;

/**
 * @internal
 */
final class TrustpilotTracker extends AbstractTrackerDefinition
{
    public function getType(): string
    {
        return 'trustpilot';
    }

    public function getCategory(): TrackerCategory
    {
        return TrackerCategory::Other;
    }

    public function isEmbed(): bool
    {
        return true;
    }
}

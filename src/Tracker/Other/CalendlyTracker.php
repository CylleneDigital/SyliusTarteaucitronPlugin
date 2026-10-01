<?php

declare(strict_types=1);

namespace CylleneDigital\SyliusTarteaucitronPlugin\Tracker\Other;

use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\AbstractTrackerDefinition;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\TrackerCategory;

/**
 * @internal
 */
final class CalendlyTracker extends AbstractTrackerDefinition
{
    public function getType(): string
    {
        return 'calendly';
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

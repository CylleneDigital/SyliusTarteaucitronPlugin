<?php

declare(strict_types=1);

namespace CylleneDigital\SyliusTarteaucitronPlugin\Tracker\Video;

use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\AbstractTrackerDefinition;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\TrackerCategory;

/**
 * @internal
 */
final class VimeoTracker extends AbstractTrackerDefinition
{
    public function getType(): string
    {
        return 'vimeo';
    }

    public function getCategory(): TrackerCategory
    {
        return TrackerCategory::Video;
    }

    public function isEmbed(): bool
    {
        return true;
    }
}

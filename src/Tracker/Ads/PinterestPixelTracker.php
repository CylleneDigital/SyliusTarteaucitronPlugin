<?php

declare(strict_types=1);

namespace CylleneDigital\SyliusTarteaucitronPlugin\Tracker\Ads;

use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\AbstractTrackerDefinition;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\TrackerCategory;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\TrackerParameter;

/**
 * @internal
 */
final class PinterestPixelTracker extends AbstractTrackerDefinition
{
    public function getType(): string
    {
        return 'pinterestpixel';
    }

    public function getCategory(): TrackerCategory
    {
        return TrackerCategory::Ads;
    }

    public function getParameters(): array
    {
        return [
            new TrackerParameter('pinterestpixel_id', 'pinterestpixelId', placeholder: 'XXXXXXXXXXXXXXXX'),
        ];
    }
}

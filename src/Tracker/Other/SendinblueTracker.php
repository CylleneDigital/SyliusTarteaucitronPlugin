<?php

declare(strict_types=1);

namespace CylleneDigital\SyliusTarteaucitronPlugin\Tracker\Other;

use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\AbstractTrackerDefinition;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\TrackerCategory;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\TrackerParameter;

/**
 * @internal
 */
final class SendinblueTracker extends AbstractTrackerDefinition
{
    public function getType(): string
    {
        return 'sendinblue';
    }

    public function getCategory(): TrackerCategory
    {
        return TrackerCategory::Other;
    }

    public function getParameters(): array
    {
        return [
            new TrackerParameter('sendinblue_key', 'sendinblueKey'),
        ];
    }
}

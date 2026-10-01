<?php

declare(strict_types=1);

namespace CylleneDigital\SyliusTarteaucitronPlugin\Tracker\Analytic;

use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\AbstractTrackerDefinition;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\ConsentMode;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\TrackerCategory;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\TrackerParameter;
use CylleneDigital\SyliusTarteaucitronPlugin\Tracker\TrackerAdminHints;

/**
 * @internal
 */
final class ClarityTracker extends AbstractTrackerDefinition
{
    public function getType(): string
    {
        return 'clarity';
    }

    public function getCategory(): TrackerCategory
    {
        return TrackerCategory::Analytic;
    }

    public function getParameters(): array
    {
        return [
            new TrackerParameter('clarity_id', 'clarity', placeholder: 'xxxxxxxxxx'),
        ];
    }

    public function getConsentMode(): ConsentMode
    {
        return ConsentMode::Bing;
    }

    public function getAdminHintTranslationKey(): string
    {
        return TrackerAdminHints::BING_NATIVE;
    }
}

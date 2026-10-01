<?php

declare(strict_types=1);

namespace CylleneDigital\SyliusTarteaucitronPlugin\Tracker\Api;

use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\AbstractTrackerDefinition;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\TrackerCategory;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\TrackerParameter;

/**
 * @internal
 */
final class RecaptchaTracker extends AbstractTrackerDefinition
{
    public function getType(): string
    {
        return 'recaptcha';
    }

    public function getCategory(): TrackerCategory
    {
        return TrackerCategory::Api;
    }

    public function getParameters(): array
    {
        return [
            new TrackerParameter(
                'recaptcha_api',
                'recaptchaapi',
                required: false,
                placeholder: 'explicit / site key',
            ),
            new TrackerParameter(
                'recaptcha_hl',
                'recaptcha_hl',
                required: false,
                placeholder: 'fr',
            ),
        ];
    }

    public function isEmbed(): bool
    {
        return true;
    }
}

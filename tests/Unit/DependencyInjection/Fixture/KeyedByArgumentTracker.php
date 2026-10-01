<?php

declare(strict_types=1);

namespace Tests\CylleneDigital\SyliusTarteaucitronPlugin\Unit\DependencyInjection\Fixture;

use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\AbstractTrackerDefinition;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\TrackerCategory;

/**
 * An application tracker whose job key comes from its service definition, with a default that a
 * bare `new` would read instead.
 */
final class KeyedByArgumentTracker extends AbstractTrackerDefinition
{
    public function __construct(
        private readonly string $type = 'acme_default',
    ) {
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getCategory(): TrackerCategory
    {
        return TrackerCategory::Other;
    }

    public function getParameters(): array
    {
        return [];
    }
}

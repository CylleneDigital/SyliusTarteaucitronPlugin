<?php

declare(strict_types=1);

namespace CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker;

/**
 * Runtime tracker payload (not a Doctrine entity).
 *
 * @internal
 */
final readonly class TrackerRuntimeState
{
    /**
     * @param array<string, string> $parameters
     */
    public function __construct(
        public string $type,
        public bool $enabled,
        public array $parameters = [],
    ) {
    }

    public function getParameter(string $key, string $default = ''): string
    {
        return $this->parameters[$key] ?? $default;
    }
}

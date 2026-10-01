<?php

declare(strict_types=1);

namespace CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker;

/**
 * Runtime payload of an enabled tracker (not a Doctrine entity): the shop query only reads enabled
 * services.
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
        public array $parameters = [],
    ) {
    }

    public function getParameter(string $key): string
    {
        return $this->parameters[$key] ?? '';
    }
}

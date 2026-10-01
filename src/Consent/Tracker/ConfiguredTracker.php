<?php

declare(strict_types=1);

namespace CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker;

/**
 * A vendor service declared in bundle configuration (`trackers:`) instead of a PHP class.
 *
 * @internal
 */
final class ConfiguredTracker extends AbstractTrackerDefinition
{
    /**
     * @param list<array{user_key: string, required: bool, placeholder: string, label: string|null}> $parameters
     */
    public function __construct(
        private readonly string $type,
        private readonly string $category,
        private readonly bool $embed,
        private readonly array $parameters,
        private readonly ?string $label,
    ) {
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getCategory(): TrackerCategory
    {
        return TrackerCategory::from($this->category);
    }

    public function getLabel(): string
    {
        return $this->label ?? $this->type;
    }

    public function getParameters(): array
    {
        return array_map(
            static fn (array $parameter): TrackerParameter => new TrackerParameter(
                $parameter['user_key'],
                $parameter['user_key'],
                $parameter['required'],
                $parameter['placeholder'],
                $parameter['label'] ?? $parameter['user_key'],
            ),
            $this->parameters,
        );
    }

    public function isEmbed(): bool
    {
        return $this->embed;
    }
}

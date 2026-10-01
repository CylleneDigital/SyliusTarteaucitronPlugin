<?php

declare(strict_types=1);

namespace CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker;

use InvalidArgumentException;

/**
 * @internal
 */
final class TrackerRegistry implements TrackerRegistryInterface
{
    /** @var list<string> */
    private const CATEGORY_ORDER = [
        'analytic',
        'ads',
        'api',
        'video',
        'social',
        'support',
        'comment',
        'other',
    ];

    /** @var array<string, TrackerDefinitionInterface> */
    private array $definitions = [];

    /**
     * @param iterable<TrackerDefinitionInterface> $definitions
     */
    public function __construct(iterable $definitions)
    {
        foreach ($definitions as $definition) {
            $type = $definition->getType();
            if (isset($this->definitions[$type])) {
                throw new InvalidArgumentException(sprintf('Duplicate tracker definition for type "%s".', $type));
            }
            $this->definitions[$type] = $definition;
        }
    }

    public function has(string $type): bool
    {
        return isset($this->definitions[$type]);
    }

    public function get(string $type): TrackerDefinitionInterface
    {
        if (!isset($this->definitions[$type])) {
            throw new InvalidArgumentException(sprintf('Unknown tracker type "%s".', $type));
        }

        return $this->definitions[$type];
    }

    public function getOrNull(string $type): ?TrackerDefinitionInterface
    {
        return $this->definitions[$type] ?? null;
    }

    public function all(): array
    {
        return array_values($this->definitions);
    }

    public function seededByDefault(): array
    {
        return array_values(array_filter(
            $this->definitions,
            static fn (TrackerDefinitionInterface $definition): bool => $definition->isSeededByDefault(),
        ));
    }

    public function types(): array
    {
        return array_keys($this->definitions);
    }

    public function groupByCategory(): array
    {
        $grouped = [];
        foreach ($this->definitions as $definition) {
            $grouped[$definition->getCategory()->value][] = $definition;
        }

        return $this->sortByCategoryOrder($grouped);
    }

    public function sortByCategoryOrder(array $grouped): array
    {
        $sorted = [];
        foreach (self::CATEGORY_ORDER as $category) {
            if (isset($grouped[$category])) {
                $sorted[$category] = $grouped[$category];
                unset($grouped[$category]);
            }
        }

        foreach ($grouped as $category => $items) {
            $sorted[$category] = $items;
        }

        return $sorted;
    }

    public function byConsentMode(ConsentMode $mode): array
    {
        return array_values(array_filter(
            $this->definitions,
            static fn (TrackerDefinitionInterface $definition): bool => $definition->getConsentMode() === $mode,
        ));
    }
}

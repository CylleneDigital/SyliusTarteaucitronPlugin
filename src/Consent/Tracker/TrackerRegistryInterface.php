<?php

declare(strict_types=1);

namespace CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker;

/**
 * @internal
 */
interface TrackerRegistryInterface
{
    public function has(string $type): bool;

    public function get(string $type): TrackerDefinitionInterface;

    public function getOrNull(string $type): ?TrackerDefinitionInterface;

    /**
     * @return list<TrackerDefinitionInterface>
     */
    public function all(): array;

    /**
     * @return list<TrackerDefinitionInterface>
     */
    public function seededByDefault(): array;

    /**
     * @return list<string>
     */
    public function types(): array;

    /**
     * @return array<string, list<TrackerDefinitionInterface>>
     */
    public function groupByCategory(): array;

    /**
     * @template T
     *
     * @param array<string, list<T>> $grouped
     *
     * @return array<string, list<T>>
     */
    public function sortByCategoryOrder(array $grouped): array;

    /**
     * @return list<TrackerDefinitionInterface>
     */
    public function byConsentMode(ConsentMode $mode): array;
}

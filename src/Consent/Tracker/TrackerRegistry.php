<?php

declare(strict_types=1);

namespace CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker;

use InvalidArgumentException;
use Symfony\Contracts\Service\ServiceProviderInterface;

/**
 * The shop only asks for the 1–3 enabled trackers: those whose job key is known when the container
 * is compiled (built-ins, `trackers:` entries) sit in a locator and are built on demand. Trackers
 * needing constructor arguments only reveal their key once built, so they come as a plain iterable.
 *
 * @internal
 */
final class TrackerRegistry
{
    /** @var array<string, TrackerDefinitionInterface>|null */
    private ?array $unkeyed = null;

    /**
     * @param ServiceProviderInterface<mixed>      $keyed           job key => tracker (type-checked on read)
     * @param iterable<TrackerDefinitionInterface> $unkeyedTrackers trackers whose key is only known once built
     */
    public function __construct(
        private readonly ServiceProviderInterface $keyed,
        private readonly iterable $unkeyedTrackers = [],
    ) {
    }

    public function getOrNull(string $type): ?TrackerDefinitionInterface
    {
        if ($this->keyed->has($type)) {
            $definition = $this->keyed->get($type);

            return $definition instanceof TrackerDefinitionInterface ? $definition : null;
        }

        return $this->unkeyed()[$type] ?? null;
    }

    /**
     * @return list<TrackerDefinitionInterface>
     */
    public function seededByDefault(): array
    {
        return array_values(array_filter(
            $this->all(),
            static fn (TrackerDefinitionInterface $definition): bool => $definition->isSeededByDefault(),
        ));
    }

    /**
     * @return list<TrackerDefinitionInterface>
     */
    public function byConsentMode(ConsentMode $mode): array
    {
        return array_values(array_filter(
            $this->all(),
            static fn (TrackerDefinitionInterface $definition): bool => $definition->getConsentMode() === $mode,
        ));
    }

    /**
     * @return list<TrackerDefinitionInterface>
     */
    private function all(): array
    {
        $all = [];
        foreach (array_keys($this->keyed->getProvidedServices()) as $type) {
            $definition = $this->getOrNull($type);
            if (null !== $definition) {
                $all[] = $definition;
            }
        }

        return [...$all, ...array_values($this->unkeyed())];
    }

    /**
     * @return array<string, TrackerDefinitionInterface>
     */
    private function unkeyed(): array
    {
        if (null !== $this->unkeyed) {
            return $this->unkeyed;
        }

        $unkeyed = [];
        foreach ($this->unkeyedTrackers as $definition) {
            $type = $definition->getType();
            if (isset($unkeyed[$type]) || $this->keyed->has($type)) {
                throw new InvalidArgumentException(sprintf('Duplicate tracker definition for type "%s".', $type));
            }
            $unkeyed[$type] = $definition;
        }

        return $this->unkeyed = $unkeyed;
    }
}

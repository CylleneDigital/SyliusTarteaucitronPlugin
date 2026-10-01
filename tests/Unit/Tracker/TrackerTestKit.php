<?php

declare(strict_types=1);

namespace Tests\CylleneDigital\SyliusTarteaucitronPlugin\Unit\Tracker;

use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\TrackerDefinitionInterface;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\TrackerRegistry;
use Symfony\Component\DependencyInjection\ServiceLocator;

final class TrackerTestKit
{
    /**
     * @return list<TrackerDefinitionInterface>
     */
    public static function all(): array
    {
        $trackers = [];
        foreach (self::trackerClassesOnDisk() as $class) {
            $trackers[] = new $class();
        }

        return $trackers;
    }

    /**
     * The registry as `UniqueTrackerTypePass` wires it: every tracker in the locator, keyed by type.
     *
     * @param iterable<TrackerDefinitionInterface>|null $definitions every built-in when null
     */
    public static function registry(?iterable $definitions = null): TrackerRegistry
    {
        $factories = [];
        foreach ($definitions ?? self::all() as $definition) {
            $factories[$definition->getType()] = static fn (): TrackerDefinitionInterface => $definition;
        }

        return new TrackerRegistry(new ServiceLocator($factories));
    }

    public static function get(string $type): TrackerDefinitionInterface
    {
        return self::registry()->getOrNull($type) ?? throw new \InvalidArgumentException(sprintf('Unknown tracker type "%s".', $type));
    }

    /**
     * @return list<class-string<TrackerDefinitionInterface>>
     */
    public static function trackerClassesOnDisk(): array
    {
        $root = \dirname(__DIR__, 3) . '/src/Tracker';
        $prefix = 'CylleneDigital\\SyliusTarteaucitronPlugin\\';
        $srcRoot = \dirname(__DIR__, 3) . '/src/';
        $classes = [];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS),
        );

        /** @var \SplFileInfo $file */
        foreach ($iterator as $file) {
            if (!$file->isFile() || !str_ends_with($file->getFilename(), 'Tracker.php')) {
                continue;
            }

            $relative = substr($file->getPathname(), strlen($srcRoot));
            $class = $prefix . str_replace(['/', '.php'], ['\\', ''], $relative);

            if (!is_a($class, TrackerDefinitionInterface::class, true)) {
                continue;
            }

            $reflection = new \ReflectionClass($class);
            if ($reflection->isAbstract() || $reflection->isInterface()) {
                continue;
            }

            $classes[] = $class;
        }

        sort($classes);

        return $classes;
    }
}

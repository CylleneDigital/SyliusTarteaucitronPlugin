<?php

declare(strict_types=1);

namespace CylleneDigital\SyliusTarteaucitronPlugin\DependencyInjection\Compiler;

use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\ConfiguredTracker;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\TrackerDefinitionInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;

/**
 * Two trackers on the same job key make TrackerRegistry throw on every shop page. Catch it at
 * container build instead, for every tracker whose type is known without running the app:
 * `trackers:` entries and classes constructible without arguments (all built-ins).
 *
 * @internal
 */
final class UniqueTrackerTypePass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $owners = [];
        foreach (array_keys($container->findTaggedServiceIds('cyllene_digital_sylius_tarteaucitron.tracker')) as $id) {
            $type = $this->typeOf($container, $id);
            if (null === $type) {
                continue;
            }

            if (isset($owners[$type])) {
                throw new InvalidArgumentException(sprintf(
                    'Two trackers use the tarteaucitron job key "%s": "%s" and "%s". Remove one of them (a `trackers:` entry cannot redeclare a built-in tracker).',
                    $type,
                    $owners[$type],
                    $id,
                ));
            }
            $owners[$type] = $id;
        }
    }

    private function typeOf(ContainerBuilder $container, string $id): ?string
    {
        $definition = $container->findDefinition($id);
        $class = $container->getParameterBag()->resolveValue($definition->getClass() ?? $id);
        if (!is_string($class) || !class_exists($class) || !is_a($class, TrackerDefinitionInterface::class, true)) {
            return null;
        }

        if (ConfiguredTracker::class === $class) {
            $type = $definition->getArgument(0);

            return is_string($type) ? $type : null;
        }

        $constructor = (new \ReflectionClass($class))->getConstructor();
        if (null !== $constructor && $constructor->getNumberOfRequiredParameters() > 0) {
            return null;
        }

        return (new $class())->getType();
    }
}

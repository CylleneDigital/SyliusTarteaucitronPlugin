<?php

declare(strict_types=1);

namespace CylleneDigital\SyliusTarteaucitronPlugin\DependencyInjection\Compiler;

use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\ConfiguredTracker;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\TrackerDefinitionInterface;
use Symfony\Component\DependencyInjection\Argument\IteratorArgument;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Two trackers on the same job key would silently shadow each other in the registry: the build
 * fails instead, for every tracker whose type is known without running the app (`trackers:`
 * entries, and classes the container builds without arguments, so all built-ins). Those also go
 * into the registry's locator, so the shop only builds the trackers it renders; the others
 * (arguments, factory, method calls) are passed as an iterable, and checked once built.
 *
 * @internal
 */
final class UniqueTrackerTypePass implements CompilerPassInterface
{
    private const REGISTRY = 'cyllene_digital_sylius_tarteaucitron.tracker.registry';

    public function process(ContainerBuilder $container): void
    {
        $owners = [];
        $unkeyed = [];
        foreach (array_keys($container->findTaggedServiceIds('cyllene_digital_sylius_tarteaucitron.tracker')) as $id) {
            // A parent definition is never instantiated, nor can it be referenced.
            if ($container->findDefinition($id)->isAbstract()) {
                continue;
            }

            $type = $this->typeOf($container, $id);
            if (null === $type) {
                $unkeyed[] = new Reference($id);

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

        if (!$container->hasDefinition(self::REGISTRY)) {
            return;
        }

        $container->getDefinition(self::REGISTRY)->setArguments([
            ServiceLocatorTagPass::register($container, array_map(static fn (string $id): Reference => new Reference($id), $owners)),
            new IteratorArgument($unkeyed),
        ]);
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

        // The job key is read on an instance built here, so it must be the instance the container
        // builds: a definition passing arguments or set up otherwise may give it another key.
        if ([] !== $definition->getArguments() || [] !== $definition->getMethodCalls() ||
            null !== $definition->getFactory() || null !== $definition->getConfigurator()) {
            return null;
        }

        $constructor = (new \ReflectionClass($class))->getConstructor();
        if (null !== $constructor && $constructor->getNumberOfRequiredParameters() > 0) {
            return null;
        }

        return (new $class())->getType();
    }
}

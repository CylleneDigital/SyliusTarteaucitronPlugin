<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Init\InitOptionCatalog;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Init\InitOptionsMapper;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\TrackerRegistry;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\TrackerRegistryInterface;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\TrackerScriptRenderer;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set('cyllene_digital_sylius_tarteaucitron.init.catalog', InitOptionCatalog::class);
    $services->alias(InitOptionCatalog::class, 'cyllene_digital_sylius_tarteaucitron.init.catalog');

    $services->set('cyllene_digital_sylius_tarteaucitron.init.mapper', InitOptionsMapper::class)
        ->args([service('cyllene_digital_sylius_tarteaucitron.init.catalog')]);
    $services->alias(InitOptionsMapper::class, 'cyllene_digital_sylius_tarteaucitron.init.mapper');

    $services->set('cyllene_digital_sylius_tarteaucitron.tracker.registry', TrackerRegistry::class)
        ->args([tagged_iterator('cyllene_digital_sylius_tarteaucitron.tracker')]);
    $services->alias(TrackerRegistry::class, 'cyllene_digital_sylius_tarteaucitron.tracker.registry');
    $services->alias(TrackerRegistryInterface::class, 'cyllene_digital_sylius_tarteaucitron.tracker.registry');

    $services->set('cyllene_digital_sylius_tarteaucitron.tracker.script_renderer', TrackerScriptRenderer::class)
        ->args([service('cyllene_digital_sylius_tarteaucitron.tracker.registry')]);
    $services->alias(TrackerScriptRenderer::class, 'cyllene_digital_sylius_tarteaucitron.tracker.script_renderer');
};

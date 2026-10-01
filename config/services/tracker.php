<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Init\InitOptionCatalog;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Init\InitOptionsMapper;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\TrackerRegistry;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\TrackerScriptRenderer;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set('cyllene_digital_sylius_tarteaucitron.init.catalog', InitOptionCatalog::class);

    $services->set('cyllene_digital_sylius_tarteaucitron.init.mapper', InitOptionsMapper::class)
        ->args([service('cyllene_digital_sylius_tarteaucitron.init.catalog')]);

    // Arguments set by UniqueTrackerTypePass: a locator of the trackers keyed by job key.
    $services->set('cyllene_digital_sylius_tarteaucitron.tracker.registry', TrackerRegistry::class);

    $services->set('cyllene_digital_sylius_tarteaucitron.tracker.script_renderer', TrackerScriptRenderer::class)
        ->args([service('cyllene_digital_sylius_tarteaucitron.tracker.registry')]);
};

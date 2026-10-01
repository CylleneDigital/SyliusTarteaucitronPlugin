<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use CylleneDigital\SyliusTarteaucitronPlugin\Sylius\Admin\AdminChannelResolver;
use CylleneDigital\SyliusTarteaucitronPlugin\Sylius\Factory\TarteaucitronConfigurationFactory;
use CylleneDigital\SyliusTarteaucitronPlugin\Sylius\Provider\ConsentConfigurationProvider;
use CylleneDigital\SyliusTarteaucitronPlugin\Sylius\Synchronizer\ServiceCatalogSynchronizer;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set('cyllene_digital_sylius_tarteaucitron.provider.consent', ConsentConfigurationProvider::class)
        ->args([
            service('cyllene_digital_sylius_tarteaucitron.repository.configuration'),
            service('sylius.context.channel'),
            service('cyllene_digital_sylius_tarteaucitron.init.catalog'),
            service('cyllene_digital_sylius_tarteaucitron.init.mapper'),
            param('cyllene_digital_sylius_tarteaucitron.integration_init'),
        ])
        ->tag('kernel.reset', ['method' => 'reset']);

    $services->set('cyllene_digital_sylius_tarteaucitron.synchronizer.services', ServiceCatalogSynchronizer::class)
        ->args([service('cyllene_digital_sylius_tarteaucitron.tracker.registry')]);

    $services->set('cyllene_digital_sylius_tarteaucitron.factory.configuration', TarteaucitronConfigurationFactory::class)
        ->args([
            service('cyllene_digital_sylius_tarteaucitron.init.catalog'),
            service('cyllene_digital_sylius_tarteaucitron.synchronizer.services'),
        ]);

    $services->set('cyllene_digital_sylius_tarteaucitron.admin.channel_resolver', AdminChannelResolver::class)
        ->args([service('sylius.repository.channel')]);
};

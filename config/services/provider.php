<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use CylleneDigital\SyliusTarteaucitronPlugin\Sylius\Admin\AdminChannelResolver;
use CylleneDigital\SyliusTarteaucitronPlugin\Sylius\Factory\TarteaucitronConfigurationFactory;
use CylleneDigital\SyliusTarteaucitronPlugin\Sylius\Provider\CatalogConsentDefaults;
use CylleneDigital\SyliusTarteaucitronPlugin\Sylius\Provider\ConsentConfigurationProvider;
use CylleneDigital\SyliusTarteaucitronPlugin\Sylius\Provider\ConsentConfigurationProviderInterface;
use CylleneDigital\SyliusTarteaucitronPlugin\Sylius\Synchronizer\ServiceCatalogSynchronizer;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set('cyllene_digital_sylius_tarteaucitron.catalog_defaults', CatalogConsentDefaults::class)
        ->args([service('cyllene_digital_sylius_tarteaucitron.init.catalog')]);
    $services->alias(CatalogConsentDefaults::class, 'cyllene_digital_sylius_tarteaucitron.catalog_defaults');

    $services->set('cyllene_digital_sylius_tarteaucitron.provider.consent', ConsentConfigurationProvider::class)
        ->args([
            service('cyllene_digital_sylius_tarteaucitron.repository.configuration'),
            service('sylius.context.channel'),
            service('cyllene_digital_sylius_tarteaucitron.init.catalog'),
            service('cyllene_digital_sylius_tarteaucitron.init.mapper'),
            param('cyllene_digital_sylius_tarteaucitron.integration_init'),
        ])
        ->tag('kernel.reset', ['method' => 'reset']);
    $services->alias(ConsentConfigurationProvider::class, 'cyllene_digital_sylius_tarteaucitron.provider.consent');
    $services->alias(ConsentConfigurationProviderInterface::class, 'cyllene_digital_sylius_tarteaucitron.provider.consent');

    $services->set('cyllene_digital_sylius_tarteaucitron.synchronizer.services', ServiceCatalogSynchronizer::class)
        ->args([service('cyllene_digital_sylius_tarteaucitron.tracker.registry')]);
    $services->alias(ServiceCatalogSynchronizer::class, 'cyllene_digital_sylius_tarteaucitron.synchronizer.services');

    $services->set('cyllene_digital_sylius_tarteaucitron.factory.configuration', TarteaucitronConfigurationFactory::class)
        ->args([
            service('cyllene_digital_sylius_tarteaucitron.catalog_defaults'),
            service('cyllene_digital_sylius_tarteaucitron.synchronizer.services'),
        ]);
    $services->alias(TarteaucitronConfigurationFactory::class, 'cyllene_digital_sylius_tarteaucitron.factory.configuration');

    $services->set('cyllene_digital_sylius_tarteaucitron.admin.channel_resolver', AdminChannelResolver::class)
        ->args([service('sylius.repository.channel')]);
    $services->alias(AdminChannelResolver::class, 'cyllene_digital_sylius_tarteaucitron.admin.channel_resolver');
};

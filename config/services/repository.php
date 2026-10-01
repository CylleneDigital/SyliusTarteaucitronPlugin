<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use CylleneDigital\SyliusTarteaucitronPlugin\Repository\TarteaucitronConfigurationRepository;
use CylleneDigital\SyliusTarteaucitronPlugin\Repository\TarteaucitronConfigurationRepositoryInterface;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set('cyllene_digital_sylius_tarteaucitron.repository.configuration', TarteaucitronConfigurationRepository::class)
        ->args([service('doctrine')])
        ->tag('doctrine.repository_service');
    $services->alias(TarteaucitronConfigurationRepository::class, 'cyllene_digital_sylius_tarteaucitron.repository.configuration');
    $services->alias(TarteaucitronConfigurationRepositoryInterface::class, 'cyllene_digital_sylius_tarteaucitron.repository.configuration');
};

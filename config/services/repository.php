<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use CylleneDigital\SyliusTarteaucitronPlugin\Repository\TarteaucitronConfigurationRepository;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    // DoctrineBundle indexes tagged repositories by service id, and EntityManager::getRepository()
    // asks for the class name: the id must be the class, an alias is not enough.
    $services->set(TarteaucitronConfigurationRepository::class)
        ->args([service('doctrine')])
        ->tag('doctrine.repository_service');
    $services->alias('cyllene_digital_sylius_tarteaucitron.repository.configuration', TarteaucitronConfigurationRepository::class);
};

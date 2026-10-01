<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use CylleneDigital\SyliusTarteaucitronPlugin\Controller\Admin\TarteaucitronConfigurationAction;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set(TarteaucitronConfigurationAction::class)
        ->args([
            service('cyllene_digital_sylius_tarteaucitron.repository.configuration'),
            service('cyllene_digital_sylius_tarteaucitron.admin.channel_resolver'),
            service('cyllene_digital_sylius_tarteaucitron.factory.configuration'),
            service('cyllene_digital_sylius_tarteaucitron.synchronizer.services'),
            service('doctrine.orm.entity_manager'),
            service('form.factory'),
            service('twig'),
            service('router'),
        ])
        ->tag('controller.service_arguments');
};

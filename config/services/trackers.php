<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return static function (ContainerConfigurator $container): void {
    $container->services()->load('CylleneDigital\\SyliusTarteaucitronPlugin\\Tracker\\', '../../src/Tracker/**/*Tracker.php')
        ->tag('cyllene_digital_sylius_tarteaucitron.tracker');
};

<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Localized\VendorLanguageTexts;
use CylleneDigital\SyliusTarteaucitronPlugin\Form\DataMapper\TarteaucitronConfigurationDataMapper;
use CylleneDigital\SyliusTarteaucitronPlugin\Form\DataMapper\TarteaucitronServiceDataMapper;
use CylleneDigital\SyliusTarteaucitronPlugin\Form\Type\LocalizedOptionsType;
use CylleneDigital\SyliusTarteaucitronPlugin\Form\Type\TarteaucitronConfigurationType;
use CylleneDigital\SyliusTarteaucitronPlugin\Form\Type\TarteaucitronServiceType;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set('cyllene_digital_sylius_tarteaucitron.form.data_mapper.configuration', TarteaucitronConfigurationDataMapper::class)
        ->args([service('cyllene_digital_sylius_tarteaucitron.init.catalog')]);

    $services->set('cyllene_digital_sylius_tarteaucitron.form.data_mapper.service', TarteaucitronServiceDataMapper::class)
        ->args([service('cyllene_digital_sylius_tarteaucitron.tracker.registry')]);

    $services->set('cyllene_digital_sylius_tarteaucitron.form.type.configuration', TarteaucitronConfigurationType::class)
        ->args([
            service('cyllene_digital_sylius_tarteaucitron.init.catalog'),
            service('cyllene_digital_sylius_tarteaucitron.form.data_mapper.configuration'),
        ])
        ->tag('form.type');

    $services->set('cyllene_digital_sylius_tarteaucitron.form.type.localized_options', LocalizedOptionsType::class)
        ->args([
            service('cyllene_digital_sylius_tarteaucitron.locale.language_resolver'),
            service('cyllene_digital_sylius_tarteaucitron.localized.vendor_texts'),
        ])
        ->tag('form.type');

    $services->set('cyllene_digital_sylius_tarteaucitron.localized.vendor_texts', VendorLanguageTexts::class);

    $services->set('cyllene_digital_sylius_tarteaucitron.form.type.service', TarteaucitronServiceType::class)
        ->args([
            service('cyllene_digital_sylius_tarteaucitron.tracker.registry'),
            service('cyllene_digital_sylius_tarteaucitron.form.data_mapper.service'),
        ])
        ->tag('form.type');
};

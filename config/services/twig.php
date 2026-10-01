<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use CylleneDigital\SyliusTarteaucitronPlugin\Consent\ComplianceCheck;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\ConsentAlert;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\OptionConflicts;
use CylleneDigital\SyliusTarteaucitronPlugin\Sylius\Locale\TarteaucitronLanguageResolver;
use CylleneDigital\SyliusTarteaucitronPlugin\Twig\TarteaucitronAdminRuntime;
use CylleneDigital\SyliusTarteaucitronPlugin\Twig\TarteaucitronExtension;
use CylleneDigital\SyliusTarteaucitronPlugin\Twig\TarteaucitronRuntime;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set('cyllene_digital_sylius_tarteaucitron.twig.extension', TarteaucitronExtension::class)
        ->tag('twig.extension');

    $services->set('cyllene_digital_sylius_tarteaucitron.locale.language_resolver', TarteaucitronLanguageResolver::class)
        ->args([
            service('sylius.context.locale'),
            param('cyllene_digital_sylius_tarteaucitron.library_languages'),
            param('cyllene_digital_sylius_tarteaucitron.locale_aliases'),
        ]);

    $services->set('cyllene_digital_sylius_tarteaucitron.twig.runtime', TarteaucitronRuntime::class)
        ->args([
            service('cyllene_digital_sylius_tarteaucitron.provider.consent'),
            service('cyllene_digital_sylius_tarteaucitron.tracker.registry'),
            service('cyllene_digital_sylius_tarteaucitron.tracker.script_renderer'),
            service('cyllene_digital_sylius_tarteaucitron.locale.language_resolver'),
            service('cyllene_digital_sylius_tarteaucitron.csp.script_nonce_provider'),
        ])
        ->arg('$assetVersions', param('cyllene_digital_sylius_tarteaucitron.asset_versions'))
        ->arg('$libraryVersion', param('cyllene_digital_sylius_tarteaucitron.library_version'))
        ->tag('twig.runtime');

    $services->set('cyllene_digital_sylius_tarteaucitron.consent.alert', ConsentAlert::class)
        ->args([service('cyllene_digital_sylius_tarteaucitron.tracker.registry')]);

    $services->set('cyllene_digital_sylius_tarteaucitron.consent.compliance_check', ComplianceCheck::class);

    $services->set('cyllene_digital_sylius_tarteaucitron.consent.option_conflicts', OptionConflicts::class)
        ->args([param('cyllene_digital_sylius_tarteaucitron.integration_init')]);

    $services->set('cyllene_digital_sylius_tarteaucitron.twig.admin_runtime', TarteaucitronAdminRuntime::class)
        ->args([
            service('cyllene_digital_sylius_tarteaucitron.init.catalog'),
            service('cyllene_digital_sylius_tarteaucitron.tracker.registry'),
            service('cyllene_digital_sylius_tarteaucitron.consent.alert'),
            service('cyllene_digital_sylius_tarteaucitron.consent.compliance_check'),
            service('cyllene_digital_sylius_tarteaucitron.consent.option_conflicts'),
        ])
        ->arg('$assetVersions', param('cyllene_digital_sylius_tarteaucitron.admin_asset_versions'))
        ->tag('twig.runtime');
};

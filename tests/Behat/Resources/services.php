<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use CylleneDigital\SyliusTarteaucitronPlugin\Tests\Behat\Context\Setup\TarteaucitronContext;
use CylleneDigital\SyliusTarteaucitronPlugin\Tests\Behat\Context\Ui\Admin\ManagingTarteaucitronConfigurationContext;
use CylleneDigital\SyliusTarteaucitronPlugin\Tests\Behat\Context\Ui\Shop\ConsentBannerContext;
use CylleneDigital\SyliusTarteaucitronPlugin\Tests\Behat\Page\Admin\Configuration\UpdatePage;
use CylleneDigital\SyliusTarteaucitronPlugin\Tests\Behat\Page\Shop\HomepageWithConsentPage;

return static function (ContainerConfigurator $container): void {
    $services = $container->services()
        ->defaults()
            ->public();

    $services->set('cyllene_digital_sylius_tarteaucitron.behat.page.shop.homepage_with_consent', HomepageWithConsentPage::class)
        ->parent('sylius.behat.symfony_page')
        ->private();

    $services->set('cyllene_digital_sylius_tarteaucitron.behat.page.admin.configuration.update', UpdatePage::class)
        ->parent('sylius.behat.symfony_page')
        ->private();

    $services->set('cyllene_digital_sylius_tarteaucitron.behat.context.setup.tarteaucitron', TarteaucitronContext::class)
        ->args([
            service('sylius.behat.shared_storage'),
            service('cyllene_digital_sylius_tarteaucitron.factory.configuration'),
            service('cyllene_digital_sylius_tarteaucitron.repository.configuration'),
            service('doctrine.orm.entity_manager'),
            service('sylius.repository.locale'),
        ]);

    $services->set('cyllene_digital_sylius_tarteaucitron.behat.context.ui.shop.consent_banner', ConsentBannerContext::class)
        ->args([
            service('cyllene_digital_sylius_tarteaucitron.behat.page.shop.homepage_with_consent'),
            service('sylius.behat.shared_storage'),
        ]);

    $services->set('cyllene_digital_sylius_tarteaucitron.behat.context.ui.admin.managing_configuration', ManagingTarteaucitronConfigurationContext::class)
        ->args([
            service('cyllene_digital_sylius_tarteaucitron.behat.page.admin.configuration.update'),
            service('sylius.behat.element.admin.notifications'),
            service('sylius.repository.channel'),
        ]);
};

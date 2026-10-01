<?php

declare(strict_types=1);

use Behat\Config\Config;

return new Config([
    'default' => [
        'suites' => [
            'ui_displaying_consent_banner' => [
                'contexts' => [
                    'sylius.behat.context.hook.doctrine_orm',
                    'sylius.behat.context.hook.session',

                    'sylius.behat.context.transform.shared_storage',

                    'sylius.behat.context.setup.channel',
                    'sylius.behat.context.setup.locale',
                    'cyllene_digital_sylius_tarteaucitron.behat.context.setup.tarteaucitron',

                    'cyllene_digital_sylius_tarteaucitron.behat.context.ui.shop.consent_banner',
                ],
                'filters' => [
                    'tags' => '@displaying_consent_banner&&@ui',
                ],
            ],
            'ui_managing_tarteaucitron_configuration' => [
                'contexts' => [
                    'sylius.behat.context.hook.doctrine_orm',
                    'sylius.behat.context.hook.session',

                    'sylius.behat.context.transform.shared_storage',

                    'sylius.behat.context.setup.admin_security',
                    'sylius.behat.context.setup.channel',
                    'cyllene_digital_sylius_tarteaucitron.behat.context.setup.tarteaucitron',

                    'sylius.behat.context.ui.save',
                    'cyllene_digital_sylius_tarteaucitron.behat.context.ui.admin.managing_configuration',
                ],
                'filters' => [
                    'tags' => '@managing_tarteaucitron_configuration&&@ui',
                ],
            ],
        ],
    ],
]);

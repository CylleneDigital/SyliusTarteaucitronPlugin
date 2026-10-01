<?php

declare(strict_types=1);

use Behat\Config\Config;
use Behat\MinkExtension\ServiceContainer\MinkExtension;
use DMore\ChromeExtension\Behat\ServiceContainer\ChromeExtension;
use FriendsOfBehat\MinkDebugExtension\ServiceContainer\MinkDebugExtension;
use FriendsOfBehat\SuiteSettingsExtension\ServiceContainer\SuiteSettingsExtension;
use FriendsOfBehat\SymfonyExtension\ServiceContainer\SymfonyExtension;
use FriendsOfBehat\VariadicExtension\ServiceContainer\VariadicExtension;
use SyliusLabs\SuiteTagsExtension\ServiceContainer\SuiteTagsExtension;

// Sylius suites are not imported: Sylius 2.1/2.2 ship them in YAML, which Behat 4 no longer reads,
// and the plugin suites only need Sylius contexts as services (tests/TestApplication/config/services_test.php).
return (new Config([
    'default' => [
        'formatters' => [
            'pretty' => [
                'verbose' => true,
                'paths' => false,
                'snippets' => false,
            ],
        ],
        'extensions' => [
            ChromeExtension::class => null,
            MinkDebugExtension::class => [
                'directory' => 'etc/build',
                'clean_start' => false,
                'screenshot' => true,
            ],
            MinkExtension::class => [
                'files_path' => '%paths.base%/vendor/sylius/sylius/src/Sylius/Behat/Resources/fixtures/',
                'base_url' => '%env(BEHAT_BASE_URL)%',
                'default_session' => 'symfony',
                'javascript_session' => 'chrome',
                'sessions' => [
                    'symfony' => [
                        'symfony' => null,
                    ],
                    'chrome' => [
                        'chrome' => [
                            'api_url' => 'http://127.0.0.1:9222',
                            'validate_certificate' => false,
                        ],
                    ],
                ],
                'show_auto' => false,
            ],
            SymfonyExtension::class => [
                'bootstrap' => 'vendor/sylius/test-application/config/bootstrap.php',
                'kernel' => [
                    'class' => 'Sylius\TestApplication\Kernel',
                    'environment' => 'test',
                ],
            ],
            VariadicExtension::class => null,
            SuiteSettingsExtension::class => [
                'paths' => ['features'],
            ],
            SuiteTagsExtension::class => null,
        ],
    ],
]))->import('tests/Behat/Resources/suites.php');

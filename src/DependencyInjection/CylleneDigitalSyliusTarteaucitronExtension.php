<?php

declare(strict_types=1);

namespace CylleneDigital\SyliusTarteaucitronPlugin\DependencyInjection;

use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Init\IntegrationOptions;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\ConfiguredTracker;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\TrackerDefinitionInterface;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\VendorServiceCatalog;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\VendorLibrary;
use CylleneDigital\SyliusTarteaucitronPlugin\Csp\NelmioScriptNonceProvider;
use CylleneDigital\SyliusTarteaucitronPlugin\Csp\StaticScriptNonceProvider;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Resource\DirectoryResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;

/**
 * @internal
 */
final class CylleneDigitalSyliusTarteaucitronExtension extends Extension implements PrependExtensionInterface
{
    /** @param array<array-key, mixed> $configs */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration(new Configuration(), $configs);

        $this->registerScriptNonceProvider(
            $container,
            is_string($config['script_nonce'] ?? null) ? $config['script_nonce'] : null,
            is_string($config['script_nonce_provider'] ?? null) ? $config['script_nonce_provider'] : null,
        );

        /** @var array<string, mixed> $integration */
        $integration = $config['integration'];
        $container->setParameter(
            'cyllene_digital_sylius_tarteaucitron.integration_init',
            IntegrationOptions::toInit($integration),
        );

        // The resolver compares lower-case, dash-separated codes: accept `nb_NO` as well as `nb-no`.
        /** @var array<string, string> $configuredAliases */
        $configuredAliases = $config['locale_aliases'];
        $localeAliases = [];
        foreach ($configuredAliases as $from => $to) {
            $localeAliases[strtolower(str_replace('_', '-', $from))] = strtolower($to);
        }
        $container->setParameter('cyllene_digital_sylius_tarteaucitron.locale_aliases', $localeAliases);

        $library = VendorLibrary::directory();
        $container->addResource(new DirectoryResource($library));
        $container->setParameter('cyllene_digital_sylius_tarteaucitron.library_version', VendorLibrary::version($library));
        $container->setParameter('cyllene_digital_sylius_tarteaucitron.library_languages', VendorLibrary::availableLanguages($library));
        $container->setParameter('cyllene_digital_sylius_tarteaucitron.asset_versions', VendorLibrary::fingerprints($library));
        $adminAssets = dirname(__DIR__, 2) . '/public/admin';
        $container->addResource(new DirectoryResource($adminAssets));
        $container->setParameter('cyllene_digital_sylius_tarteaucitron.admin_asset_versions', VendorLibrary::fingerprints($adminAssets));

        $container->registerForAutoconfiguration(TrackerDefinitionInterface::class)
            ->addTag('cyllene_digital_sylius_tarteaucitron.tracker');

        /** @var array<string, array{category: string, label: string|null, embed: bool, parameters: array<string, array{required: bool, placeholder: string, label: string|null}>}> $trackers */
        $trackers = $config['trackers'];
        $this->registerConfiguredTrackers($container, $trackers, new VendorServiceCatalog());

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__ . '/../../config'));
        $loader->load('services.php');
    }

    private function registerScriptNonceProvider(ContainerBuilder $container, ?string $nonce, ?string $provider): void
    {
        $id = 'cyllene_digital_sylius_tarteaucitron.csp.script_nonce_provider';

        if ('nelmio' === $provider) {
            // Fails at container build with a clear "non-existent service" when the bundle is missing.
            $container->register($id, NelmioScriptNonceProvider::class)
                ->setArguments([new Reference('nelmio_security.csp_listener')]);
        } elseif (null !== $provider) {
            $container->setAlias($id, $provider);
        } else {
            $container->register($id, StaticScriptNonceProvider::class)->setArguments(['' === $nonce ? null : $nonce]);
        }
    }

    /**
     * Fails at container build, not in the shop: a typo in a job key or a user key would otherwise
     * give a service that shows up in the back office and silently never loads.
     *
     * @param array<string, array{category: string, label: string|null, embed: bool, parameters: array<string, array{required: bool, placeholder: string, label: string|null}>}> $trackers
     */
    private function registerConfiguredTrackers(ContainerBuilder $container, array $trackers, VendorServiceCatalog $vendor): void
    {
        foreach ($trackers as $type => $tracker) {
            if (!$vendor->has($type)) {
                throw new InvalidConfigurationException(sprintf(
                    'cyllene_digital_sylius_tarteaucitron.trackers: "%s" is not a service of the vendored tarteaucitron.js (tarteaucitron.services.js).',
                    $type,
                ));
            }

            $parameters = [];
            foreach ($tracker['parameters'] as $userKey => $parameter) {
                if (!in_array($userKey, $vendor->userKeysOf($type), true)) {
                    throw new InvalidConfigurationException(sprintf(
                        'cyllene_digital_sylius_tarteaucitron.trackers.%s: the service never reads tarteaucitron.user.%s (it reads: %s).',
                        $type,
                        $userKey,
                        implode(', ', $vendor->userKeysOf($type)) ?: 'none',
                    ));
                }

                $parameters[] = ['user_key' => $userKey] + $parameter;
            }

            $container
                ->register('cyllene_digital_sylius_tarteaucitron.tracker.configured.' . $type, ConfiguredTracker::class)
                ->setArguments([$type, $tracker['category'], $tracker['embed'], $parameters, $tracker['label']])
                ->addTag('cyllene_digital_sylius_tarteaucitron.tracker');
        }
    }

    public function prepend(ContainerBuilder $container): void
    {
        $this->prependTwigHooks($container);
        $this->prependDoctrine($container);
    }

    private function prependTwigHooks(ContainerBuilder $container): void
    {
        if (!$container->hasExtension('sylius_twig_hooks')) {
            return;
        }

        $files = (new Finder())->files()->in(__DIR__ . '/../../config/twig_hooks')->name('*.yaml')->sortByName();
        foreach ($files as $file) {
            $parsed = Yaml::parseFile($file->getPathname());
            if (!is_array($parsed) || !isset($parsed['sylius_twig_hooks']) || !is_array($parsed['sylius_twig_hooks'])) {
                continue;
            }

            /** @var array<string, mixed> $hooks */
            $hooks = $parsed['sylius_twig_hooks'];
            $container->prependExtensionConfig('sylius_twig_hooks', $hooks);
        }
    }

    private function prependDoctrine(ContainerBuilder $container): void
    {
        if ($container->hasExtension('doctrine')) {
            $container->prependExtensionConfig('doctrine', [
                'orm' => [
                    'mappings' => [
                        'CylleneDigitalSyliusTarteaucitronPlugin' => [
                            'type' => 'attribute',
                            'dir' => __DIR__ . '/../Entity',
                            'prefix' => 'CylleneDigital\SyliusTarteaucitronPlugin\Entity',
                            'is_bundle' => false,
                        ],
                    ],
                ],
            ]);
        }

        if ($container->hasExtension('doctrine_migrations')) {
            $container->prependExtensionConfig('doctrine_migrations', [
                'migrations_paths' => [
                    'CylleneDigital\SyliusTarteaucitronPlugin\Migrations' => __DIR__ . '/../Migrations',
                ],
            ]);
        }
    }
}

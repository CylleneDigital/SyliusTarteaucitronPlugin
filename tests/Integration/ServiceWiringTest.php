<?php

declare(strict_types=1);

namespace CylleneDigital\SyliusTarteaucitronPlugin\Tests\Integration;

use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Init\InitOptionCatalog;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\ConfiguredTracker;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\TrackerDefinitionInterface;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\TrackerRegistry;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\TrackerScriptRenderer;
use CylleneDigital\SyliusTarteaucitronPlugin\Csp\StaticScriptNonceProvider;
use CylleneDigital\SyliusTarteaucitronPlugin\Form\Type\TarteaucitronConfigurationType;
use CylleneDigital\SyliusTarteaucitronPlugin\Sylius\Admin\AdminChannelResolver;
use CylleneDigital\SyliusTarteaucitronPlugin\Sylius\Locale\TarteaucitronLanguageResolver;
use CylleneDigital\SyliusTarteaucitronPlugin\Sylius\Provider\ConsentConfigurationProvider;
use CylleneDigital\SyliusTarteaucitronPlugin\Tests\Unit\Tracker\TrackerTestKit;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Wiring is the most frequent class of bug on a plugin: it shows up neither in static analysis nor
 * in unit tests, and often not at boot either.
 */
final class ServiceWiringTest extends KernelTestCase
{
    /**
     * @return iterable<string, array{string, class-string}>
     */
    public static function services(): iterable
    {
        yield 'init catalog' => ['cyllene_digital_sylius_tarteaucitron.init.catalog', InitOptionCatalog::class];
        yield 'tracker registry' => ['cyllene_digital_sylius_tarteaucitron.tracker.registry', TrackerRegistry::class];
        yield 'script renderer' => ['cyllene_digital_sylius_tarteaucitron.tracker.script_renderer', TrackerScriptRenderer::class];
        yield 'consent provider' => ['cyllene_digital_sylius_tarteaucitron.provider.consent', ConsentConfigurationProvider::class];
        yield 'channel resolver' => ['cyllene_digital_sylius_tarteaucitron.admin.channel_resolver', AdminChannelResolver::class];
        yield 'language resolver' => ['cyllene_digital_sylius_tarteaucitron.locale.language_resolver', TarteaucitronLanguageResolver::class];
        yield 'configuration form' => ['cyllene_digital_sylius_tarteaucitron.form.type.configuration', TarteaucitronConfigurationType::class];
    }

    /**
     * @param class-string $expectedClass
     */
    #[DataProvider('services')]
    public function testServiceIsRegistered(string $id, string $expectedClass): void
    {
        self::bootKernel();

        self::assertInstanceOf($expectedClass, self::getContainer()->get($id));
    }

    /** Catches a tracker added under src/Tracker/ but missed by the DI prototype. */
    public function testEveryBuiltInTrackerIsTagged(): void
    {
        self::bootKernel();

        /** @var TrackerRegistry $registry */
        $registry = self::getContainer()->get('cyllene_digital_sylius_tarteaucitron.tracker.registry');

        $registered = array_map(
            static fn (TrackerDefinitionInterface $tracker): string => $tracker::class,
            array_filter($registry->all(), static fn (TrackerDefinitionInterface $tracker): bool => !$tracker instanceof ConfiguredTracker),
        );
        sort($registered);

        self::assertSame(TrackerTestKit::trackerClassesOnDisk(), $registered);
    }

    /** The test application declares smartsupp under `trackers:` (tests/TestApplication/config/config.yaml). */
    public function testConfiguredTrackerReachesTheRegistry(): void
    {
        self::bootKernel();

        /** @var TrackerRegistry $registry */
        $registry = self::getContainer()->get('cyllene_digital_sylius_tarteaucitron.tracker.registry');
        $smartsupp = $registry->get('smartsupp');

        self::assertInstanceOf(ConfiguredTracker::class, $smartsupp);
        self::assertSame('Smartsupp', $smartsupp->getLabel());
        self::assertSame('smartsuppKey', $smartsupp->getParameters()[0]->userKey);
    }

    /** Catches a missing kernel.reset tag on the channel-scoped consent cache (M-1). */
    public function testConsentProviderIsResettable(): void
    {
        self::bootKernel();

        $provider = self::getContainer()->get('cyllene_digital_sylius_tarteaucitron.provider.consent');
        self::assertInstanceOf(ResetInterface::class, $provider);

        $resetter = self::getContainer()->get('services_resetter');
        $reflection = new \ReflectionObject($resetter);
        $property = $reflection->getProperty('resetMethods');
        /** @var array<string, mixed> $resetMethods */
        $resetMethods = $property->getValue($resetter);

        self::assertArrayHasKey(
            'cyllene_digital_sylius_tarteaucitron.provider.consent',
            $resetMethods,
            'ConsentConfigurationProvider must carry the kernel.reset tag.',
        );
    }

    public function testAdminConfigurationRouteIsRegistered(): void
    {
        self::bootKernel();

        /** @var RouterInterface $router */
        $router = self::getContainer()->get('router');
        $route = $router->getRouteCollection()->get('cyllene_digital_sylius_tarteaucitron_admin_configuration');

        self::assertNotNull($route);
    }

    public function testBundleConfigurationParametersAreRegistered(): void
    {
        self::bootKernel();

        self::assertInstanceOf(
            StaticScriptNonceProvider::class,
            self::getContainer()->get('cyllene_digital_sylius_tarteaucitron.csp.script_nonce_provider'),
        );
        self::assertSame(
            ['nb' => 'no'],
            self::getContainer()->getParameter('cyllene_digital_sylius_tarteaucitron.locale_aliases'),
        );
    }
}

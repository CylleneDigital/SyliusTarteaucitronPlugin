<?php

declare(strict_types=1);

namespace Tests\CylleneDigital\SyliusTarteaucitronPlugin\Unit\DependencyInjection;

use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\ConfiguredTracker;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\TrackerCategory;
use CylleneDigital\SyliusTarteaucitronPlugin\Csp\NelmioScriptNonceProvider;
use CylleneDigital\SyliusTarteaucitronPlugin\Csp\StaticScriptNonceProvider;
use CylleneDigital\SyliusTarteaucitronPlugin\DependencyInjection\Compiler\UniqueTrackerTypePass;
use CylleneDigital\SyliusTarteaucitronPlugin\DependencyInjection\CylleneDigitalSyliusTarteaucitronExtension;
use CylleneDigital\SyliusTarteaucitronPlugin\Tracker\Video\YoutubeTracker;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\Argument\IteratorArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Tests\CylleneDigital\SyliusTarteaucitronPlugin\Unit\DependencyInjection\Fixture\KeyedByArgumentTracker;

final class BundleConfigurationTest extends TestCase
{
    public function testDeclaredVendorServiceBecomesATaggedTracker(): void
    {
        $container = $this->load([
            'smartsupp' => [
                'category' => 'support',
                'label' => 'Smartsupp',
                'parameters' => ['smartsuppKey' => ['placeholder' => 'abc']],
            ],
        ]);

        $id = 'cyllene_digital_sylius_tarteaucitron.tracker.configured.smartsupp';
        self::assertTrue($container->getDefinition($id)->hasTag('cyllene_digital_sylius_tarteaucitron.tracker'));

        /** @var array{string, string, bool, list<array{user_key: string, required: bool, placeholder: string, label: string|null}>, string|null} $arguments */
        $arguments = $container->getDefinition($id)->getArguments();
        $tracker = new ConfiguredTracker(...$arguments);
        self::assertSame('smartsupp', $tracker->getType());
        self::assertSame(TrackerCategory::Support, $tracker->getCategory());
        self::assertSame('Smartsupp', $tracker->getLabel());
        self::assertFalse($tracker->isEmbed());

        [$parameter] = $tracker->getParameters();
        self::assertSame('smartsuppKey', $parameter->key);
        self::assertSame('smartsuppKey', $parameter->userKey);
        self::assertTrue($parameter->required);
        self::assertSame('abc', $parameter->placeholder);
        self::assertFalse($tracker->areRequiredParametersFilled(['smartsuppKey' => '']));
        self::assertTrue($tracker->areRequiredParametersFilled(['smartsuppKey' => 'k']));
    }

    public function testUnknownJobKeyFailsAtContainerBuild(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('"smartsup" is not a service of the vendored tarteaucitron.js');

        $this->load(['smartsup' => ['category' => 'support']]);
    }

    public function testUserKeyTheServiceNeverReadsFailsAtContainerBuild(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('never reads tarteaucitron.user.smartsuppId (it reads: smartsuppKey)');

        $this->load(['smartsupp' => ['category' => 'support', 'parameters' => ['smartsuppId' => []]]]);
    }

    public function testUnknownCategoryIsRejectedByTheConfigurationTree(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $this->load(['smartsupp' => ['category' => 'chat']]);
    }

    public function testIntegrationOptionsBecomeTheInitPayloadParameter(): void
    {
        $container = new ContainerBuilder();
        (new CylleneDigitalSyliusTarteaucitronExtension())->load([[
            'integration' => ['adblocker' => true, 'custom_closer_id' => 'manage-cookies'],
        ]], $container);

        self::assertSame(
            ['useExternalCss' => false, 'mandatoryCta' => false, 'useExternalJs' => false, 'serverSide' => false, 'adblocker' => true, 'hashtag' => '#tarteaucitron', 'customCloserId' => 'manage-cookies'],
            $container->getParameter('cyllene_digital_sylius_tarteaucitron.integration_init'),
        );
    }

    public function testHashtagMustBeAUrlFragment(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('The hashtag must be a URL fragment');

        (new CylleneDigitalSyliusTarteaucitronExtension())->load([['integration' => ['hashtag' => 'tarteaucitron']]], new ContainerBuilder());
    }

    public function testLocaleAliasesAcceptBothLocaleNotations(): void
    {
        $container = new ContainerBuilder();
        (new CylleneDigitalSyliusTarteaucitronExtension())->load([[
            'locale_aliases' => ['nb' => 'no', 'nn-NO' => 'NO', 'pt_BR' => 'pt'],
        ]], $container);

        self::assertSame(
            ['nb' => 'no', 'nn-no' => 'no', 'pt-br' => 'pt'],
            $container->getParameter('cyllene_digital_sylius_tarteaucitron.locale_aliases'),
        );
    }

    public function testATrackersEntryCannotRedeclareABuiltInJobKey(): void
    {
        $container = $this->load(['youtube' => ['category' => 'video', 'embed' => true]]);

        $this->expectException(\Symfony\Component\DependencyInjection\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('Two trackers use the tarteaucitron job key "youtube"');

        (new UniqueTrackerTypePass())->process($container);
    }

    public function testRegistryLocatesTheTrackersWhoseJobKeyIsKnownAtCompileTime(): void
    {
        $container = $this->load(['smartsupp' => ['category' => 'support']]);
        self::assertTrue($container->findDefinition(YoutubeTracker::class)->hasTag('cyllene_digital_sylius_tarteaucitron.tracker'));

        (new UniqueTrackerTypePass())->process($container);

        $keys = $this->registryLocatorKeys($container);
        self::assertContains('youtube', $keys);
        self::assertContains('smartsupp', $keys);
        self::assertSame([], $this->registryUnkeyedIds($container));
    }

    public function testATrackerKeyedByItsDefinitionIsLeftUnkeyed(): void
    {
        $container = $this->load([]);
        $container->register('app.tracker.keyed_by_argument', KeyedByArgumentTracker::class)
            ->setArguments(['acme_from_definition'])
            ->addTag('cyllene_digital_sylius_tarteaucitron.tracker');
        $container->register('app.tracker.parent', KeyedByArgumentTracker::class)
            ->setAbstract(true)
            ->addTag('cyllene_digital_sylius_tarteaucitron.tracker');

        (new UniqueTrackerTypePass())->process($container);

        self::assertNotContains('acme_default', $this->registryLocatorKeys($container), 'A bare `new` would read the default key.');
        self::assertSame(['app.tracker.keyed_by_argument'], $this->registryUnkeyedIds($container), 'The abstract parent is skipped.');
    }

    /**
     * @return list<string>
     */
    private function registryLocatorKeys(ContainerBuilder $container): array
    {
        $locator = $container->getDefinition('cyllene_digital_sylius_tarteaucitron.tracker.registry')->getArgument(0);
        \assert($locator instanceof Reference);
        $services = $container->getDefinition((string) $locator)->getArgument(0);
        \assert(is_array($services));

        return array_map('strval', array_keys($services));
    }

    /**
     * @return list<string>
     */
    private function registryUnkeyedIds(ContainerBuilder $container): array
    {
        $unkeyed = $container->getDefinition('cyllene_digital_sylius_tarteaucitron.tracker.registry')->getArgument(1);
        \assert($unkeyed instanceof IteratorArgument);

        $ids = [];
        foreach ($unkeyed->getValues() as $reference) {
            \assert($reference instanceof Reference);
            $ids[] = (string) $reference;
        }

        return $ids;
    }

    public function testFixedScriptNonce(): void
    {
        $container = new ContainerBuilder();
        (new CylleneDigitalSyliusTarteaucitronExtension())->load([['script_nonce' => 'abc']], $container);

        $definition = $container->getDefinition('cyllene_digital_sylius_tarteaucitron.csp.script_nonce_provider');
        self::assertSame(StaticScriptNonceProvider::class, $definition->getClass());
        self::assertSame(['abc'], $definition->getArguments());
    }

    public function testNelmioScriptNonceProviderUsesTheCspListener(): void
    {
        $container = new ContainerBuilder();
        (new CylleneDigitalSyliusTarteaucitronExtension())->load([['script_nonce_provider' => 'nelmio']], $container);

        $definition = $container->getDefinition('cyllene_digital_sylius_tarteaucitron.csp.script_nonce_provider');
        self::assertSame(NelmioScriptNonceProvider::class, $definition->getClass());
        $listener = $definition->getArgument(0);
        self::assertInstanceOf(Reference::class, $listener);
        self::assertSame('nelmio_security.csp_listener', (string) $listener);
    }

    public function testCustomScriptNonceProviderIsAnAlias(): void
    {
        $container = new ContainerBuilder();
        (new CylleneDigitalSyliusTarteaucitronExtension())->load([['script_nonce_provider' => 'app.csp_nonce']], $container);

        self::assertSame('app.csp_nonce', (string) $container->getAlias('cyllene_digital_sylius_tarteaucitron.csp.script_nonce_provider'));
    }

    public function testFixedAndPerResponseNonceAreExclusive(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Set either script_nonce (fixed) or script_nonce_provider');

        (new CylleneDigitalSyliusTarteaucitronExtension())->load([['script_nonce' => 'abc', 'script_nonce_provider' => 'nelmio']], new ContainerBuilder());
    }

    /**
     * @param array<string, mixed> $trackers
     */
    private function load(array $trackers): ContainerBuilder
    {
        $container = new ContainerBuilder();
        (new CylleneDigitalSyliusTarteaucitronExtension())->load([['trackers' => $trackers]], $container);

        return $container;
    }
}

<?php

declare(strict_types=1);

namespace CylleneDigital\SyliusTarteaucitronPlugin\Tests\Unit\Sylius\Provider;

use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Init\InitOptionCatalog;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Init\InitOptionsMapper;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\TrackerRuntimeState;
use CylleneDigital\SyliusTarteaucitronPlugin\Entity\TarteaucitronConfiguration;
use CylleneDigital\SyliusTarteaucitronPlugin\Entity\TarteaucitronService;
use CylleneDigital\SyliusTarteaucitronPlugin\Repository\TarteaucitronConfigurationRepositoryInterface;
use CylleneDigital\SyliusTarteaucitronPlugin\Sylius\Provider\ConsentConfigurationProvider;
use CylleneDigital\SyliusTarteaucitronPlugin\Sylius\Provider\ResolvedConsent;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Channel\Context\ChannelNotFoundException;
use Sylius\Component\Channel\Model\ChannelInterface;

final class ConsentConfigurationProviderTest extends TestCase
{
    public function testReturnsUnconfiguredWhenChannelHasNoRow(): void
    {
        if (!interface_exists(ChannelInterface::class)) {
            self::markTestSkipped('sylius/sylius is not installed.');
        }

        $channel = $this->createMock(ChannelInterface::class);
        $channel->method('getId')->willReturn(1);

        $repository = $this->createMock(TarteaucitronConfigurationRepositoryInterface::class);
        $repository->method('findOneByChannel')->with($channel)->willReturn(null);

        $provider = $this->provider($repository);
        $resolved = $provider->resolve($channel);

        self::assertFalse($resolved->enabled);
        self::assertSame([], $resolved->init);
        self::assertSame([], $resolved->services);
        self::assertSame(TarteaucitronConfiguration::DEFAULT_CONSENT_LIFETIME_DAYS, $resolved->consentLifetimeDays);
    }

    public function testDatabaseWinsAsABlock(): void
    {
        if (!interface_exists(ChannelInterface::class)) {
            self::markTestSkipped('sylius/sylius is not installed.');
        }

        $channel = $this->createMock(ChannelInterface::class);
        $channel->method('getId')->willReturn(2);

        $configuration = new TarteaucitronConfiguration();
        $configuration->setEnabled(true);
        $configuration->setInitOptions(['privacy_url' => 'https://db.test/privacy', 'high_privacy' => false]);
        $configuration->setConsentLifetimeDays(90);
        $service = new TarteaucitronService();
        $service->setType('gtag');
        $service->setEnabled(false);
        $service->setParameters(['gtag_ua' => 'G-DB']);
        $configuration->addService($service);

        $repository = $this->createMock(TarteaucitronConfigurationRepositoryInterface::class);
        $repository->method('findOneByChannel')->with($channel)->willReturn($configuration);

        $resolved = $this->provider($repository)->resolve($channel);

        $gtag = $this->serviceByType($resolved, 'gtag');
        self::assertTrue($resolved->enabled);
        self::assertSame('https://db.test/privacy', $resolved->init['privacyUrl']);
        self::assertFalse($resolved->init['highPrivacy']);
        self::assertFalse($gtag->enabled);
        self::assertSame('G-DB', $gtag->parameters['gtag_ua']);
        self::assertSame(90, $resolved->consentLifetimeDays);
    }

    public function testValuesWrittenOutsideTheBackOfficeCannotBreakTheShop(): void
    {
        if (!interface_exists(ChannelInterface::class)) {
            self::markTestSkipped('sylius/sylius is not installed.');
        }

        $channel = $this->createMock(ChannelInterface::class);
        $channel->method('getId')->willReturn(4);

        $configuration = new TarteaucitronConfiguration();
        $configuration->setEnabled(true);
        $configuration->setConsentLifetimeDays(900);
        $service = new TarteaucitronService();
        $service->setType('gtag');
        $service->setEnabled(true);
        /** @phpstan-ignore argument.type (simulates a JSON value imported outside the form) */
        $service->setParameters(['gtag_ua' => 12345, 'gtag_custom_domain' => 'tag.example.com']);
        $configuration->addService($service);

        $repository = $this->createMock(TarteaucitronConfigurationRepositoryInterface::class);
        $repository->method('findOneByChannel')->with($channel)->willReturn($configuration);

        $resolved = $this->provider($repository)->resolve($channel);

        self::assertSame(TarteaucitronConfiguration::MAX_CONSENT_LIFETIME_DAYS, $resolved->consentLifetimeDays);
        self::assertSame(['gtag_custom_domain' => 'tag.example.com'], $this->serviceByType($resolved, 'gtag')->parameters);
    }

    public function testIntegrationOptionsFromBundleConfigurationJoinTheInitPayload(): void
    {
        if (!interface_exists(ChannelInterface::class)) {
            self::markTestSkipped('sylius/sylius is not installed.');
        }

        $channel = $this->createMock(ChannelInterface::class);
        $channel->method('getId')->willReturn(3);

        $configuration = new TarteaucitronConfiguration();
        $configuration->setEnabled(true);
        $configuration->setInitOptions(['use_external_css' => true, 'hashtag' => '#stored']);

        $repository = $this->createMock(TarteaucitronConfigurationRepositoryInterface::class);
        $repository->method('findOneByChannel')->with($channel)->willReturn($configuration);

        $init = $this->provider($repository, ['adblocker' => true, 'hashtag' => '#cookies'])->resolve($channel)->init;

        self::assertTrue($init['adblocker']);
        self::assertSame('#cookies', $init['hashtag']);
        self::assertArrayNotHasKey('useExternalCss', $init, 'A value left in init_options by an older version is ignored.');
    }

    /**
     * @param array<string, mixed> $integrationInit
     */
    private function provider(
        TarteaucitronConfigurationRepositoryInterface $repository,
        array $integrationInit = [],
    ): ConsentConfigurationProvider {
        $catalog = new InitOptionCatalog();
        $channelContext = $this->createMock(ChannelContextInterface::class);
        $channelContext->method('getChannel')->willThrowException(new ChannelNotFoundException());

        return new ConsentConfigurationProvider(
            $repository,
            $channelContext,
            $catalog,
            new InitOptionsMapper($catalog),
            $integrationInit,
        );
    }

    private function serviceByType(ResolvedConsent $resolved, string $type): TrackerRuntimeState
    {
        foreach ($resolved->services as $service) {
            if ($service->type === $type) {
                return $service;
            }
        }

        throw new \RuntimeException(sprintf('Expected runtime state for "%s".', $type));
    }
}

<?php

declare(strict_types=1);

namespace Tests\CylleneDigital\SyliusTarteaucitronPlugin\Unit\Sylius\Provider;

use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Init\InitOptionCatalog;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Init\InitOptionsMapper;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\TrackerRuntimeState;
use CylleneDigital\SyliusTarteaucitronPlugin\Entity\TarteaucitronConfiguration;
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
        $channel = $this->createMock(ChannelInterface::class);
        $channel->method('getId')->willReturn(1);

        $repository = $this->createMock(TarteaucitronConfigurationRepositoryInterface::class);
        $repository->method('findShopConsentByChannel')->with($channel)->willReturn(null);

        $provider = $this->provider($repository);
        $resolved = $provider->resolve($channel);

        self::assertFalse($resolved->enabled);
        self::assertSame([], $resolved->init);
        self::assertSame([], $resolved->services);
        self::assertSame(TarteaucitronConfiguration::DEFAULT_CONSENT_LIFETIME_DAYS, $resolved->consentLifetimeDays);
    }

    public function testDatabaseWinsAsABlock(): void
    {
        $channel = $this->createMock(ChannelInterface::class);
        $channel->method('getId')->willReturn(2);

        $repository = $this->createMock(TarteaucitronConfigurationRepositoryInterface::class);
        $repository->method('findShopConsentByChannel')->with($channel)->willReturn($this->row(
            initOptions: ['privacy_url' => 'https://db.test/privacy', 'high_privacy' => false],
            consentLifetimeDays: 90,
            services: [['type' => 'gtag', 'parameters' => ['gtag_ua' => 'G-DB']]],
        ));

        $resolved = $this->provider($repository)->resolve($channel);

        $gtag = $this->serviceByType($resolved, 'gtag');
        self::assertTrue($resolved->enabled);
        self::assertSame('https://db.test/privacy', $resolved->init['privacyUrl']);
        self::assertFalse($resolved->init['highPrivacy']);
        self::assertSame('G-DB', $gtag->parameters['gtag_ua']);
        self::assertSame(90, $resolved->consentLifetimeDays);
    }

    public function testValuesWrittenOutsideTheBackOfficeCannotBreakTheShop(): void
    {
        $channel = $this->createMock(ChannelInterface::class);
        $channel->method('getId')->willReturn(4);

        $repository = $this->createMock(TarteaucitronConfigurationRepositoryInterface::class);
        $repository->method('findShopConsentByChannel')->with($channel)->willReturn($this->row(
            consentLifetimeDays: 900,
            services: [['type' => 'gtag', 'parameters' => ['gtag_ua' => 12345, 'gtag_custom_domain' => 'tag.example.com', 0 => 'list']]],
        ));

        $resolved = $this->provider($repository)->resolve($channel);

        self::assertSame(TarteaucitronConfiguration::MAX_CONSENT_LIFETIME_DAYS, $resolved->consentLifetimeDays);
        self::assertSame(['gtag_custom_domain' => 'tag.example.com'], $this->serviceByType($resolved, 'gtag')->parameters);
    }

    public function testEachChannelIsReadOnceUntilReset(): void
    {
        $channel = $this->createMock(ChannelInterface::class);
        $channel->method('getId')->willReturn(5);

        $repository = $this->createMock(TarteaucitronConfigurationRepositoryInterface::class);
        $repository->expects(self::exactly(2))->method('findShopConsentByChannel')->with($channel)->willReturn($this->row());
        $provider = $this->provider($repository);

        $first = $provider->resolve($channel);
        self::assertSame($first, $provider->resolve($channel), 'Second call within the request: cached.');

        $provider->reset();
        $provider->resolve($channel);
    }

    public function testCurrentChannelComesFromTheChannelContext(): void
    {
        $channel = $this->createMock(ChannelInterface::class);
        $channel->method('getId')->willReturn(6);

        $repository = $this->createMock(TarteaucitronConfigurationRepositoryInterface::class);
        $repository->expects(self::once())->method('findShopConsentByChannel')->with($channel)->willReturn($this->row());

        self::assertTrue($this->provider($repository, currentChannel: $channel)->resolve()->enabled);
    }

    public function testNoCurrentChannelQueriesNothing(): void
    {
        $repository = $this->createMock(TarteaucitronConfigurationRepositoryInterface::class);
        $repository->expects(self::never())->method('findShopConsentByChannel');

        $resolved = $this->provider($repository)->resolve();

        self::assertFalse($resolved->enabled);
        self::assertSame([], $resolved->services);
    }

    public function testIntegrationOptionsFromBundleConfigurationJoinTheInitPayload(): void
    {
        $channel = $this->createMock(ChannelInterface::class);
        $channel->method('getId')->willReturn(3);

        $repository = $this->createMock(TarteaucitronConfigurationRepositoryInterface::class);
        $repository->method('findShopConsentByChannel')->with($channel)->willReturn($this->row(
            initOptions: ['use_external_css' => true, 'hashtag' => '#stored'],
        ));

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
        ?ChannelInterface $currentChannel = null,
    ): ConsentConfigurationProvider {
        $catalog = new InitOptionCatalog();
        $channelContext = $this->createMock(ChannelContextInterface::class);
        if (null === $currentChannel) {
            $channelContext->method('getChannel')->willThrowException(new ChannelNotFoundException());
        } else {
            $channelContext->method('getChannel')->willReturn($currentChannel);
        }

        return new ConsentConfigurationProvider(
            $repository,
            $channelContext,
            $catalog,
            new InitOptionsMapper($catalog),
            $integrationInit,
        );
    }

    /**
     * @param array<string, mixed>                                 $initOptions
     * @param list<array{type: string, parameters: array<mixed>}> $services
     *
     * @return array{enabled: bool, initOptions: array<string, mixed>, localizedOptions: array<mixed>, consentLifetimeDays: int, services: list<array{type: string, parameters: array<mixed>}>}
     */
    private function row(array $initOptions = [], int $consentLifetimeDays = 180, array $services = []): array
    {
        return [
            'enabled' => true,
            'initOptions' => $initOptions,
            'localizedOptions' => [],
            'consentLifetimeDays' => $consentLifetimeDays,
            'services' => $services,
        ];
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

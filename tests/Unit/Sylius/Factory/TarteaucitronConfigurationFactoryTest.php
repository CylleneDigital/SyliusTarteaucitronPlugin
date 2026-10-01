<?php

declare(strict_types=1);

namespace CylleneDigital\SyliusTarteaucitronPlugin\Tests\Unit\Sylius\Factory;

use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Init\InitOptionCatalog;
use CylleneDigital\SyliusTarteaucitronPlugin\Entity\TarteaucitronService;
use CylleneDigital\SyliusTarteaucitronPlugin\Sylius\Factory\TarteaucitronConfigurationFactory;
use CylleneDigital\SyliusTarteaucitronPlugin\Sylius\Provider\CatalogConsentDefaults;
use CylleneDigital\SyliusTarteaucitronPlugin\Sylius\Synchronizer\ServiceCatalogSynchronizer;
use CylleneDigital\SyliusTarteaucitronPlugin\Tests\Unit\Tracker\TrackerTestKit;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Channel\Model\ChannelInterface;

final class TarteaucitronConfigurationFactoryTest extends TestCase
{
    public function testCreateForChannelUsesCatalogueDefaultsAndSeedsServices(): void
    {
        if (!interface_exists(ChannelInterface::class)) {
            self::markTestSkipped('sylius/sylius is not installed.');
        }

        $channel = $this->createMock(ChannelInterface::class);
        $factory = new TarteaucitronConfigurationFactory(
            new CatalogConsentDefaults(new InitOptionCatalog()),
            new ServiceCatalogSynchronizer(TrackerTestKit::registry()),
        );

        $configuration = $factory->createForChannel($channel);

        self::assertSame($channel, $configuration->getChannel());
        self::assertFalse($configuration->isEnabled());
        self::assertTrue($configuration->getInitOptions()['high_privacy']);
        self::assertSame('', $configuration->getInitOptions()['privacy_url']);
        $gtag = $configuration->getServiceByType('gtag');
        self::assertInstanceOf(TarteaucitronService::class, $gtag);
        self::assertFalse($gtag->isEnabled());
        self::assertSame('', $gtag->getParameter('gtag_ua'));
        $youtube = $configuration->getServiceByType('youtube');
        self::assertInstanceOf(TarteaucitronService::class, $youtube);
        self::assertFalse($youtube->isEnabled());
    }
}

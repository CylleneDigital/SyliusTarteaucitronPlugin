<?php

declare(strict_types=1);

namespace Tests\CylleneDigital\SyliusTarteaucitronPlugin\Unit\Sylius\Factory;

use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Init\InitOptionCatalog;
use CylleneDigital\SyliusTarteaucitronPlugin\Entity\TarteaucitronService;
use CylleneDigital\SyliusTarteaucitronPlugin\Sylius\Factory\TarteaucitronConfigurationFactory;
use CylleneDigital\SyliusTarteaucitronPlugin\Sylius\Synchronizer\ServiceCatalogSynchronizer;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Channel\Model\ChannelInterface;
use Tests\CylleneDigital\SyliusTarteaucitronPlugin\Unit\Tracker\TrackerTestKit;

final class TarteaucitronConfigurationFactoryTest extends TestCase
{
    public function testCreateForChannelUsesCatalogueDefaultsAndSeedsServices(): void
    {
        $channel = $this->createMock(ChannelInterface::class);
        $factory = new TarteaucitronConfigurationFactory(
            new InitOptionCatalog(),
            new ServiceCatalogSynchronizer(TrackerTestKit::registry()),
        );

        $configuration = $factory->createForChannel($channel);

        self::assertSame($channel, $configuration->getChannel());
        self::assertFalse($configuration->isEnabled());
        $init = $configuration->getInitOptions();
        self::assertTrue($init['high_privacy']);
        self::assertTrue($init['google_consent_mode']);
        self::assertTrue($init['piano_consent_mode']);
        self::assertFalse($init['piano_consent_mode_essential']);
        self::assertTrue($init['piwik_consent_mode']);
        self::assertSame('wait', $init['service_default_state']);
        self::assertSame('', $init['privacy_url']);
        $gtag = $configuration->getServiceByType('gtag');
        self::assertInstanceOf(TarteaucitronService::class, $gtag);
        self::assertFalse($gtag->isEnabled());
        self::assertSame('', $gtag->getParameter('gtag_ua'));
        $youtube = $configuration->getServiceByType('youtube');
        self::assertInstanceOf(TarteaucitronService::class, $youtube);
        self::assertFalse($youtube->isEnabled());
    }
}

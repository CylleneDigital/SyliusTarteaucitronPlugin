<?php

declare(strict_types=1);

namespace CylleneDigital\SyliusTarteaucitronPlugin\Tests\Unit\Sylius\Synchronizer;

use CylleneDigital\SyliusTarteaucitronPlugin\Entity\TarteaucitronConfiguration;
use CylleneDigital\SyliusTarteaucitronPlugin\Entity\TarteaucitronService;
use CylleneDigital\SyliusTarteaucitronPlugin\Sylius\Synchronizer\ServiceCatalogSynchronizer;
use CylleneDigital\SyliusTarteaucitronPlugin\Tests\Unit\Tracker\TrackerTestKit;
use PHPUnit\Framework\TestCase;

final class ServiceCatalogSynchronizerTest extends TestCase
{
    public function testSeedsMissingServicesOnce(): void
    {
        if (!interface_exists(\Sylius\Component\Channel\Model\ChannelInterface::class)) {
            self::markTestSkipped('sylius/sylius is not installed.');
        }

        $registry = TrackerTestKit::registry();
        $synchronizer = new ServiceCatalogSynchronizer($registry);
        $configuration = new TarteaucitronConfiguration();

        $synchronizer->ensureSeeded($configuration);

        $gtag = $configuration->getServiceByType('gtag');
        self::assertInstanceOf(TarteaucitronService::class, $gtag);
        self::assertFalse($gtag->isEnabled());
        self::assertSame('', $gtag->getParameter('gtag_ua'));

        $count = $configuration->getServices()->count();
        $synchronizer->ensureSeeded($configuration);
        self::assertSame($count, $configuration->getServices()->count());
        self::assertFalse($gtag->isEnabled());
        self::assertSame('', $gtag->getParameter('gtag_ua'));
    }
}

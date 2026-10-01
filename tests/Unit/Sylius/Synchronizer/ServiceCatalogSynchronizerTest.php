<?php

declare(strict_types=1);

namespace Tests\CylleneDigital\SyliusTarteaucitronPlugin\Unit\Sylius\Synchronizer;

use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\AbstractTrackerDefinition;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\TrackerCategory;
use CylleneDigital\SyliusTarteaucitronPlugin\Entity\TarteaucitronConfiguration;
use CylleneDigital\SyliusTarteaucitronPlugin\Entity\TarteaucitronService;
use CylleneDigital\SyliusTarteaucitronPlugin\Sylius\Synchronizer\ServiceCatalogSynchronizer;
use PHPUnit\Framework\TestCase;
use Tests\CylleneDigital\SyliusTarteaucitronPlugin\Unit\Tracker\TrackerTestKit;

final class ServiceCatalogSynchronizerTest extends TestCase
{
    public function testSeedsMissingServicesOnce(): void
    {
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

    public function testConfiguredServicesAndRowsOfRemovedTrackersAreLeftUntouched(): void
    {
        $configuration = new TarteaucitronConfiguration();
        $gtag = new TarteaucitronService();
        $gtag->setType('gtag');
        $gtag->setEnabled(true);
        $gtag->setParameters(['gtag_ua' => 'G-KEEP']);
        $configuration->addService($gtag);
        $removed = new TarteaucitronService();
        $removed->setType('acme_removed');
        $configuration->addService($removed);

        (new ServiceCatalogSynchronizer(TrackerTestKit::registry()))->ensureSeeded($configuration);

        self::assertSame($gtag, $configuration->getServiceByType('gtag'));
        self::assertTrue($gtag->isEnabled());
        self::assertSame('G-KEEP', $gtag->getParameter('gtag_ua'));
        self::assertSame($removed, $configuration->getServiceByType('acme_removed'));
    }

    public function testATrackerNotSeededByDefaultGetsNoRow(): void
    {
        $unseeded = new class() extends AbstractTrackerDefinition {
            public function getType(): string
            {
                return 'acme_unseeded';
            }

            public function getCategory(): TrackerCategory
            {
                return TrackerCategory::Other;
            }

            public function getParameters(): array
            {
                return [];
            }

            public function isSeededByDefault(): bool
            {
                return false;
            }
        };
        $configuration = new TarteaucitronConfiguration();

        (new ServiceCatalogSynchronizer(TrackerTestKit::registry([$unseeded])))->ensureSeeded($configuration);

        self::assertNull($configuration->getServiceByType('acme_unseeded'));
    }
}

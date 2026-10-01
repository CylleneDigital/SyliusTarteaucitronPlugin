<?php

declare(strict_types=1);

namespace CylleneDigital\SyliusTarteaucitronPlugin\Tests\Unit\Consent\Tracker;

use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\AbstractTrackerDefinition;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\ConsentMode;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\TrackerCategory;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\TrackerDefinitionInterface;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\TrackerParameter;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\TrackerRegistry;
use CylleneDigital\SyliusTarteaucitronPlugin\Tests\Unit\Tracker\TrackerTestKit;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class TrackerRegistryTest extends TestCase
{
    public function testDuplicateTypeThrows(): void
    {
        $definition = TrackerTestKit::get('gtag');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Duplicate tracker definition for type "gtag".');

        new TrackerRegistry([$definition, $definition]);
    }

    public function testUnknownTypeThrowsOnGet(): void
    {
        $registry = TrackerTestKit::registry();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown tracker type "not-a-tracker".');

        $registry->get('not-a-tracker');
    }

    public function testCategoryOrderIsStable(): void
    {
        $registry = TrackerTestKit::registry();
        $grouped = $registry->groupByCategory();

        self::assertSame(['analytic', 'ads', 'api', 'video', 'social', 'support', 'other'], array_keys($grouped));
        self::assertContains('gtag', array_map(
            static fn (TrackerDefinitionInterface $definition): string => $definition->getType(),
            $grouped['analytic'],
        ));
    }

    public function testConsentModeLookup(): void
    {
        $registry = TrackerTestKit::registry();
        $google = array_map(
            static fn (TrackerDefinitionInterface $definition): string => $definition->getType(),
            $registry->byConsentMode(ConsentMode::Google),
        );

        self::assertContains('gtag', $google);
        self::assertContains('googleads', $google);
        self::assertNotContains('googletagmanager', $google);
    }

    public function testCustomDefinitionCanBeRegistered(): void
    {
        $custom = new class() extends AbstractTrackerDefinition {
            public function getType(): string
            {
                return 'acme_custom';
            }

            public function getCategory(): TrackerCategory
            {
                return TrackerCategory::Other;
            }

            public function getParameters(): array
            {
                return [new TrackerParameter('acme_id', 'acmeId')];
            }
        };

        $registry = new TrackerRegistry([...TrackerTestKit::all(), $custom]);

        self::assertTrue($registry->has('acme_custom'));
        self::assertSame('acme_custom', $registry->get('acme_custom')->getType());
    }
}

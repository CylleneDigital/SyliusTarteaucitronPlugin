<?php

declare(strict_types=1);

namespace Tests\CylleneDigital\SyliusTarteaucitronPlugin\Unit\Consent\Tracker;

use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\AbstractTrackerDefinition;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\ConsentMode;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\TrackerCategory;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\TrackerDefinitionInterface;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\TrackerParameter;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\TrackerRegistry;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Tests\CylleneDigital\SyliusTarteaucitronPlugin\Unit\Tracker\TrackerTestKit;

final class TrackerRegistryTest extends TestCase
{
    public function testUnknownTypeIsNull(): void
    {
        self::assertNull(TrackerTestKit::registry()->getOrNull('not-a-tracker'));
    }

    public function testOnlyTheRequestedTrackerIsBuilt(): void
    {
        $built = [];
        $factory = static function (string $type) use (&$built): \Closure {
            return static function () use ($type, &$built): TrackerDefinitionInterface {
                $built[] = $type;

                return TrackerTestKit::get($type);
            };
        };
        $registry = new TrackerRegistry(new ServiceLocator(['gtag' => $factory('gtag'), 'youtube' => $factory('youtube'), 'hotjar' => $factory('hotjar')]));

        self::assertSame('youtube', $registry->getOrNull('youtube')?->getType());
        self::assertSame(['youtube'], $built);
    }

    public function testTrackerWhoseKeyIsOnlyKnownOnceBuiltIsFound(): void
    {
        $registry = new TrackerRegistry(new ServiceLocator([]), [$this->customTracker()]);

        self::assertSame('acme_custom', $registry->getOrNull('acme_custom')?->getType());
        self::assertCount(1, $registry->seededByDefault());
    }

    public function testUnkeyedTrackerReusingAKnownJobKeyThrows(): void
    {
        $registry = new TrackerRegistry(
            new ServiceLocator(['gtag' => static fn (): TrackerDefinitionInterface => TrackerTestKit::get('gtag')]),
            [TrackerTestKit::get('gtag')],
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Duplicate tracker definition for type "gtag".');

        $registry->getOrNull('unknown');
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
        $custom = $this->customTracker();

        $registry = TrackerTestKit::registry([...TrackerTestKit::all(), $custom]);

        self::assertSame($custom, $registry->getOrNull('acme_custom'));
    }

    private function customTracker(): TrackerDefinitionInterface
    {
        return new class() extends AbstractTrackerDefinition {
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
    }
}

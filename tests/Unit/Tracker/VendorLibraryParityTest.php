<?php

declare(strict_types=1);

namespace Tests\CylleneDigital\SyliusTarteaucitronPlugin\Unit\Tracker;

use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\TrackerDefinitionInterface;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\VendorServiceCatalog;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Built-in job keys and string userKeys must exist in tarteaucitron.services.js.
 */
final class VendorLibraryParityTest extends TestCase
{
    /** @var array<string, list<string>> */
    private const ACKNOWLEDGED_GAPS = [
        'atinternet' => ['atMore', 'atNoFallback', 'atinternetAlreadyLoaded', 'atinternetSendData'],
        'bingads' => ['bingadsMore', 'bingadsStoreCookies'],
        'criteoonetag' => ['criteoonetagMore'],
        'facebookpixel' => ['facebookpixelMore'],
        'googleads' => ['googleadsMore'],
        'gtag' => ['gtagCrossdomain', 'gtagMore'],
        'matomo' => ['matomoMore'],
        'recaptcha' => ['recaptchaOnLoad'],
        'snapchat' => ['snapchatMore'],
        'tiktok' => ['tiktokMore'],
    ];

    public function testEveryBuiltInJobKeyExistsInVendorLibrary(): void
    {
        $vendor = self::vendorUserKeysByService();

        foreach (TrackerTestKit::all() as $tracker) {
            self::assertArrayHasKey(
                $tracker->getType(),
                $vendor,
                sprintf('Job key "%s" is missing from tarteaucitron.services.js.', $tracker->getType()),
            );
        }
    }

    #[DataProvider('trackers')]
    public function testEveryDeclaredUserKeyIsReadByItsService(TrackerDefinitionInterface $tracker): void
    {
        $blockKeys = self::vendorUserKeysByService()[$tracker->getType()] ?? [];
        $parameters = $tracker->getParameters();
        if ([] === $parameters) {
            $this->expectNotToPerformAssertions();

            return;
        }

        foreach ($parameters as $parameter) {
            self::assertContains(
                $parameter->userKey,
                $blockKeys,
                sprintf(
                    'tarteaucitron.user.%s is not read by service "%s".',
                    $parameter->userKey,
                    $tracker->getType(),
                ),
            );
        }
    }

    #[DataProvider('trackers')]
    public function testVendorKeysMissingFromTheBackOfficeAreAcknowledged(TrackerDefinitionInterface $tracker): void
    {
        $vendorKeys = self::vendorUserKeysByService()[$tracker->getType()] ?? [];
        $declared = array_map(
            static fn ($parameter): string => $parameter->userKey,
            $tracker->getParameters(),
        );
        $extra = array_values(array_diff($vendorKeys, $declared));
        sort($extra);

        $acknowledged = self::ACKNOWLEDGED_GAPS[$tracker->getType()] ?? [];
        sort($acknowledged);

        self::assertSame(
            $acknowledged,
            $extra,
            sprintf('Unexpected vendor user keys for "%s". Update ACKNOWLEDGED_GAPS or the tracker.', $tracker->getType()),
        );
    }

    /**
     * @return iterable<string, array{TrackerDefinitionInterface}>
     */
    public static function trackers(): iterable
    {
        foreach (TrackerTestKit::all() as $tracker) {
            yield $tracker->getType() => [$tracker];
        }
    }

    /**
     * @return array<string, list<string>>
     */
    private static function vendorUserKeysByService(): array
    {
        return (new VendorServiceCatalog())->all();
    }
}

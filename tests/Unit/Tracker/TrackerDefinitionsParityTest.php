<?php

declare(strict_types=1);

namespace Tests\CylleneDigital\SyliusTarteaucitronPlugin\Unit\Tracker;

use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\ConsentMode;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\TrackerDefinitionInterface;
use CylleneDigital\SyliusTarteaucitronPlugin\Tracker\TrackerAdminHints;
use PHPUnit\Framework\TestCase;

final class TrackerDefinitionsParityTest extends TestCase
{
    public function testBuiltInCountAndUniqueness(): void
    {
        $definitions = TrackerTestKit::all();
        $types = array_map(
            static fn (TrackerDefinitionInterface $definition): string => $definition->getType(),
            $definitions,
        );

        // The discovery skips a *Tracker.php class that is not a TrackerDefinitionInterface: none may.
        $files = new \RegexIterator(
            new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(\dirname(__DIR__, 3) . '/src/Tracker', \FilesystemIterator::SKIP_DOTS)),
            '/Tracker\.php$/',
        );
        self::assertCount(iterator_count($files), $definitions);
        self::assertSame($types, array_values(array_unique($types)));
        self::assertContains('gtag', $types);
        self::assertContains('youtube', $types);
    }

    public function testGtagParity(): void
    {
        $gtag = TrackerTestKit::get('gtag');

        self::assertSame(ConsentMode::Google, $gtag->getConsentMode());
        self::assertSame(TrackerAdminHints::GOOGLE_NATIVE, $gtag->getAdminHintTranslationKey());
        self::assertFalse($gtag->isEmbed());
        self::assertTrue($gtag->isSeededByDefault());
        self::assertCount(2, $gtag->getParameters());
        self::assertSame('gtag_ua', $gtag->getParameters()[0]->key);
        self::assertSame('gtagUa', $gtag->getParameters()[0]->userKey);
        self::assertSame('gtagCustomDomain', $gtag->getParameters()[1]->userKey);
        self::assertFalse($gtag->getParameters()[1]->required);
    }

    public function testGoogleTagManagerParity(): void
    {
        $gtm = TrackerTestKit::get('googletagmanager');

        self::assertSame(ConsentMode::Gtm, $gtm->getConsentMode());
        self::assertSame(TrackerAdminHints::GTM, $gtm->getAdminHintTranslationKey());
        self::assertSame('googletagmanager_id', $gtm->getParameters()[0]->key);
        self::assertSame('googletagmanagerId', $gtm->getParameters()[0]->userKey);
    }

    public function testYoutubeEmbedParity(): void
    {
        $youtube = TrackerTestKit::get('youtube');

        self::assertTrue($youtube->isEmbed());
        self::assertNull($youtube->getConsentMode());
        self::assertSame([], $youtube->getParameters());
    }

    public function testHubspotOptionalParameterParity(): void
    {
        $hubspot = TrackerTestKit::get('hubspot');
        $parameters = $hubspot->getParameters();

        self::assertCount(2, $parameters);
        self::assertSame('hubspot_id', $parameters[0]->key);
        self::assertTrue($parameters[0]->required);
        self::assertSame('hubspot_business_unit_id', $parameters[1]->key);
        self::assertFalse($parameters[1]->required);
        self::assertSame('hubspotBusinessUnitId', $parameters[1]->userKey);
    }

    public function testClarityBingConsentParity(): void
    {
        $clarity = TrackerTestKit::get('clarity');

        self::assertSame(ConsentMode::Bing, $clarity->getConsentMode());
        self::assertSame(TrackerAdminHints::BING_NATIVE, $clarity->getAdminHintTranslationKey());
        self::assertSame('clarity', $clarity->getParameters()[0]->userKey);
    }
}

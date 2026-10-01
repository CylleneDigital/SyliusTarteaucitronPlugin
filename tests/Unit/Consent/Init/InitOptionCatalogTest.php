<?php

declare(strict_types=1);

namespace Tests\CylleneDigital\SyliusTarteaucitronPlugin\Unit\Consent\Init;

use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Init\InitOption;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Init\InitOptionCatalog;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Init\InitOptionSection;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Localized\LocalizedOptions;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\ConsentMode;
use PHPUnit\Framework\TestCase;

final class InitOptionCatalogTest extends TestCase
{
    public function testEverySectionHasOptionsAndTheyCoverTheCatalog(): void
    {
        $catalog = new InitOptionCatalog();
        $keys = [];

        foreach (InitOptionSection::cases() as $section) {
            $options = $catalog->bySection($section);
            self::assertNotEmpty($options, $section->value);

            foreach ($options as $option) {
                $keys[] = $option->key;
            }
        }

        self::assertCount(count($catalog->all()), $keys);
    }

    public function testOptionsLandInTheirIntendedSection(): void
    {
        $catalog = new InitOptionCatalog();

        self::assertSame(InitOptionSection::Essential, self::option($catalog, 'privacy_url')?->section);
        self::assertSame(InitOptionSection::Compliance, self::option($catalog, 'high_privacy')?->section);
        self::assertSame(InitOptionSection::Compliance, self::option($catalog, 'service_default_state')?->section);
        self::assertSame(InitOptionSection::ConsentMode, self::option($catalog, 'google_consent_mode')?->section);
        self::assertSame(InitOptionSection::ConsentMode, self::option($catalog, 'piwik_consent_mode')?->section);
        self::assertSame(InitOptionSection::Display, self::option($catalog, 'orientation')?->section);
        self::assertNull(self::option($catalog, 'use_external_css'), 'Integration options live in bundle configuration.');
        self::assertSame(InitOptionSection::Advanced, self::option($catalog, 'cookie_name')?->section);
    }

    public function testRelatedConsentModeIsTiedToInitFlags(): void
    {
        $catalog = new InitOptionCatalog();

        self::assertSame(ConsentMode::Google, self::option($catalog, 'google_consent_mode')?->relatedConsentMode);
        self::assertSame(ConsentMode::Bing, self::option($catalog, 'bing_consent_mode')?->relatedConsentMode);
        self::assertNull(self::option($catalog, 'soft_consent_mode')?->relatedConsentMode);
    }

    public function testCriticalJsKeysStayVendorSpecific(): void
    {
        $catalog = new InitOptionCatalog();

        self::assertSame('DenyAllCta', self::option($catalog, 'deny_all_cta')?->jsKey);
        self::assertSame('AcceptAllCta', self::option($catalog, 'accept_all_cta')?->jsKey);
        self::assertSame('cookieslist', self::option($catalog, 'cookies_list')?->jsKey);
        self::assertSame('handleBrowserDNTRequest', self::option($catalog, 'handle_browser_dnt_request')?->jsKey);
    }

    public function testUrlFlagIsSetOnVendorLinkOptions(): void
    {
        $catalog = new InitOptionCatalog();

        self::assertTrue(self::option($catalog, 'privacy_url')?->url);
        self::assertTrue(self::option($catalog, 'readmore_link')?->url);
        self::assertTrue(self::option($catalog, 'icon_src')?->url);
        self::assertFalse(self::option($catalog, 'cookie_name')?->url);
    }

    public function testOnlyTheIconTakesARasterDataUri(): void
    {
        $catalog = new InitOptionCatalog();
        $raster = array_values(array_map(
            static fn (InitOption $option): string => $option->key,
            array_filter($catalog->all(), static fn (InitOption $option): bool => $option->allowRasterDataUri),
        ));

        self::assertSame(['icon_src'], $raster);
    }

    public function testLocalizedLinksUseTheCatalogueJsKeys(): void
    {
        $catalog = new InitOptionCatalog();

        foreach (LocalizedOptions::LINKS as $key => $jsKey) {
            $option = self::option($catalog, $key);
            self::assertNotNull($option, $key);
            self::assertTrue($option->url, $key);
            self::assertSame($option->jsKey, $jsKey, sprintf('LocalizedOptions::LINKS["%s"] diverges from the catalogue.', $key));
        }
    }

    private static function option(InitOptionCatalog $catalog, string $key): ?InitOption
    {
        foreach ($catalog->all() as $option) {
            if ($option->key === $key) {
                return $option;
            }
        }

        return null;
    }
}

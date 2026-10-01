<?php

declare(strict_types=1);

namespace CylleneDigital\SyliusTarteaucitronPlugin\Tests\Unit\Consent\Init;

use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Init\InitOptionCatalog;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Init\InitOptionSection;
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

        self::assertSame(InitOptionSection::Essential, $catalog->get('privacy_url')?->section);
        self::assertSame(InitOptionSection::Compliance, $catalog->get('high_privacy')?->section);
        self::assertSame(InitOptionSection::Compliance, $catalog->get('service_default_state')?->section);
        self::assertSame(InitOptionSection::ConsentMode, $catalog->get('google_consent_mode')?->section);
        self::assertSame(InitOptionSection::ConsentMode, $catalog->get('piwik_consent_mode')?->section);
        self::assertSame(InitOptionSection::Display, $catalog->get('orientation')?->section);
        self::assertNull($catalog->get('use_external_css'), 'Integration options live in bundle configuration.');
        self::assertSame(InitOptionSection::Advanced, $catalog->get('cookie_name')?->section);
    }

    public function testRelatedConsentModeIsTiedToInitFlags(): void
    {
        $catalog = new InitOptionCatalog();

        self::assertSame(ConsentMode::Google, $catalog->get('google_consent_mode')?->relatedConsentMode);
        self::assertSame(ConsentMode::Bing, $catalog->get('bing_consent_mode')?->relatedConsentMode);
        self::assertNull($catalog->get('soft_consent_mode')?->relatedConsentMode);
    }

    public function testCriticalJsKeysStayVendorSpecific(): void
    {
        $catalog = new InitOptionCatalog();

        self::assertSame('DenyAllCta', $catalog->get('deny_all_cta')?->jsKey);
        self::assertSame('AcceptAllCta', $catalog->get('accept_all_cta')?->jsKey);
        self::assertSame('cookieslist', $catalog->get('cookies_list')?->jsKey);
        self::assertSame('handleBrowserDNTRequest', $catalog->get('handle_browser_dnt_request')?->jsKey);
    }

    public function testUrlFlagIsSetOnVendorLinkOptions(): void
    {
        $catalog = new InitOptionCatalog();

        self::assertTrue($catalog->get('privacy_url')?->url);
        self::assertTrue($catalog->get('readmore_link')?->url);
        self::assertTrue($catalog->get('icon_src')?->url);
        self::assertFalse($catalog->get('cookie_name')?->url);
    }
}

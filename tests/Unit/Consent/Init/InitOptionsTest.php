<?php

declare(strict_types=1);

namespace CylleneDigital\SyliusTarteaucitronPlugin\Tests\Unit\Consent\Init;

use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Init\InitOptionCatalog;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Init\InitOptions;
use PHPUnit\Framework\TestCase;

final class InitOptionsTest extends TestCase
{
    public function testEmptyArrayUsesCatalogueDefaults(): void
    {
        $catalog = new InitOptionCatalog();
        $options = InitOptions::fromArray([], $catalog);

        self::assertTrue($options->get('high_privacy'));
        self::assertSame('wait', $options->get('service_default_state'));
        self::assertSame('tarteaucitron', $options->get('cookie_name'));
        self::assertSame('', $options->get('privacy_url'));
    }

    public function testPartialOverlayKeepsMissingKeysAtDefault(): void
    {
        $catalog = new InitOptionCatalog();
        $options = InitOptions::fromArray([
            'high_privacy' => false,
            'unknown_future_key' => 'ignored',
        ], $catalog);

        self::assertFalse($options->get('high_privacy'));
        self::assertTrue($options->get('google_consent_mode'));
        self::assertNull($options->get('unknown_future_key'));
    }

    public function testInvalidChoiceFallsBackToDefault(): void
    {
        $catalog = new InitOptionCatalog();
        $options = InitOptions::fromArray(['orientation' => 'sideways'], $catalog);

        self::assertSame('middle', $options->get('orientation'));
    }

    public function testToArrayContainsEveryCatalogueKey(): void
    {
        $catalog = new InitOptionCatalog();
        $values = InitOptions::defaults($catalog)->toArray();

        foreach ($catalog->all() as $option) {
            self::assertArrayHasKey($option->key, $values);
        }
    }

    public function testJavascriptUrlsAreBlanked(): void
    {
        $catalog = new InitOptionCatalog();
        $options = InitOptions::fromArray([
            'privacy_url' => 'javascript:alert(1)',
            'readmore_link' => 'javascript:alert(1)',
            'icon_src' => 'javascript:alert(1)',
        ], $catalog);

        self::assertSame('', $options->get('privacy_url'));
        self::assertSame('', $options->get('readmore_link'));
        self::assertSame('', $options->get('icon_src'));
    }

    public function testHttpUrlsAndRelativePathsAreKept(): void
    {
        $catalog = new InitOptionCatalog();
        $options = InitOptions::fromArray([
            'privacy_url' => 'https://example.com/privacy',
            'readmore_link' => '/privacy-policy',
        ], $catalog);

        self::assertSame('https://example.com/privacy', $options->get('privacy_url'));
        self::assertSame('/privacy-policy', $options->get('readmore_link'));
    }

    public function testIconSrcAllowsImageDataUriAndRejectsHtmlDataUri(): void
    {
        $catalog = new InitOptionCatalog();
        $kept = InitOptions::fromArray([
            'icon_src' => 'data:image/png;base64,aaa',
        ], $catalog);
        $rejected = InitOptions::fromArray([
            'icon_src' => 'data:text/html,<script>alert(1)</script>',
        ], $catalog);

        self::assertSame('data:image/png;base64,aaa', $kept->get('icon_src'));
        self::assertSame('', $rejected->get('icon_src'));
    }

    public function testIconSrcRejectsSvgDataUri(): void
    {
        $catalog = new InitOptionCatalog();
        $rejected = InitOptions::fromArray([
            'icon_src' => 'data:image/svg+xml,<svg onload=alert(1)></svg>',
        ], $catalog);

        self::assertSame('', $rejected->get('icon_src'));
    }

    public function testProtocolRelativeUrlsAreBlanked(): void
    {
        $catalog = new InitOptionCatalog();
        $options = InitOptions::fromArray([
            'privacy_url' => '//evil.example/phish',
        ], $catalog);

        self::assertSame('', $options->get('privacy_url'));
    }

    public function testCookieNameAndDomainFallBackWhenTheyWouldBreakTheCookie(): void
    {
        $options = InitOptions::fromArray(
            ['cookie_name' => 'x;y=1', 'cookie_domain' => 'evil; path=/'],
            new InitOptionCatalog(),
        );

        self::assertSame('tarteaucitron', $options->get('cookie_name'));
        self::assertSame('', $options->get('cookie_domain'));
        self::assertSame(
            '.example.com',
            InitOptions::fromArray(['cookie_domain' => '.example.com'], new InitOptionCatalog())->get('cookie_domain'),
        );
    }
}

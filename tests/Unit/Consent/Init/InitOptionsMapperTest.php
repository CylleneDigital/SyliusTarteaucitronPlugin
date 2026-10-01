<?php

declare(strict_types=1);

namespace CylleneDigital\SyliusTarteaucitronPlugin\Tests\Unit\Consent\Init;

use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Init\InitOptionCatalog;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Init\InitOptions;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Init\InitOptionsMapper;
use PHPUnit\Framework\TestCase;

final class InitOptionsMapperTest extends TestCase
{
    public function testVendorJsKeysAreExact(): void
    {
        $catalog = new InitOptionCatalog();
        $mapper = new InitOptionsMapper($catalog);
        $init = $mapper->toTarteaucitronInit(InitOptions::defaults($catalog));

        self::assertArrayHasKey('DenyAllCta', $init);
        self::assertArrayHasKey('AcceptAllCta', $init);
        self::assertArrayHasKey('cookieslist', $init);
        self::assertArrayHasKey('handleBrowserDNTRequest', $init);
        self::assertArrayHasKey('privacyUrl', $init);
        self::assertArrayHasKey('pianoConsentMode', $init);
        self::assertArrayHasKey('pianoConsentModeEssential', $init);
        self::assertArrayHasKey('piwikConsentMode', $init);
        self::assertArrayNotHasKey('denyAllCta', $init);
        self::assertArrayNotHasKey('cookiesList', $init);
    }

    public function testEmptyOptionalFieldsAreOmitted(): void
    {
        $catalog = new InitOptionCatalog();
        $mapper = new InitOptionsMapper($catalog);
        $init = $mapper->toTarteaucitronInit(InitOptions::defaults($catalog));

        self::assertArrayNotHasKey('iconSrc', $init);
        self::assertArrayNotHasKey('cookieDomain', $init);
        self::assertArrayNotHasKey('customCloserId', $init);
        self::assertArrayHasKey('privacyUrl', $init);
        self::assertSame('', $init['privacyUrl']);
    }

    public function testOptionalFieldIsIncludedWhenSet(): void
    {
        $catalog = new InitOptionCatalog();
        $mapper = new InitOptionsMapper($catalog);
        $options = InitOptions::fromArray(['cookie_domain' => '.example.com'], $catalog);
        $init = $mapper->toTarteaucitronInit($options);

        self::assertSame('.example.com', $init['cookieDomain']);
    }
}

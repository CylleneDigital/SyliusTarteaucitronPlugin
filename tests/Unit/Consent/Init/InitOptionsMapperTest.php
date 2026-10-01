<?php

declare(strict_types=1);

namespace Tests\CylleneDigital\SyliusTarteaucitronPlugin\Unit\Consent\Init;

use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Init\InitOptionCatalog;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Init\InitOptions;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Init\InitOptionsMapper;
use PHPUnit\Framework\Attributes\DataProvider;
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

    /**
     * @return iterable<string, array{string, bool|string}>
     */
    public static function serviceDefaultStates(): iterable
    {
        yield 'accepted' => ['true', true];
        yield 'denied' => ['false', false];
        yield 'waiting' => ['wait', 'wait'];
    }

    #[DataProvider('serviceDefaultStates')]
    public function testServiceDefaultStateIsTheValueTheLibraryCompares(string $stored, bool|string $expected): void
    {
        $catalog = new InitOptionCatalog();
        $mapper = new InitOptionsMapper($catalog);
        $init = $mapper->toTarteaucitronInit(InitOptions::fromArray(['service_default_state' => $stored], $catalog));

        self::assertSame($expected, $init['serviceDefaultState']);
    }
}

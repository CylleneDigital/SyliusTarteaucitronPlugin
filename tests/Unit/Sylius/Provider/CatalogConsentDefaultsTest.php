<?php

declare(strict_types=1);

namespace CylleneDigital\SyliusTarteaucitronPlugin\Tests\Unit\Sylius\Provider;

use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Init\InitOptionCatalog;
use CylleneDigital\SyliusTarteaucitronPlugin\Sylius\Provider\CatalogConsentDefaults;
use PHPUnit\Framework\TestCase;

final class CatalogConsentDefaultsTest extends TestCase
{
    public function testDefaultInitOptionsUseCatalogueDefaults(): void
    {
        $defaults = new CatalogConsentDefaults(new InitOptionCatalog());

        $init = $defaults->defaultInitOptions();

        self::assertTrue($init['high_privacy']);
        self::assertTrue($init['google_consent_mode']);
        self::assertTrue($init['piano_consent_mode']);
        self::assertFalse($init['piano_consent_mode_essential']);
        self::assertTrue($init['piwik_consent_mode']);
        self::assertSame('wait', $init['service_default_state']);
        self::assertSame('', $init['privacy_url']);
    }
}

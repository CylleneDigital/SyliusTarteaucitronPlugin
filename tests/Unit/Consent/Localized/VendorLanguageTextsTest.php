<?php

declare(strict_types=1);

namespace Tests\CylleneDigital\SyliusTarteaucitronPlugin\Unit\Consent\Localized;

use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Localized\LocalizedOptions;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Localized\VendorLanguageTexts;
use PHPUnit\Framework\TestCase;

final class VendorLanguageTextsTest extends TestCase
{
    public function testReadsTopLevelTextsOfTheVendoredLanguageFile(): void
    {
        $texts = new VendorLanguageTexts();

        self::assertSame('Tout accepter', $texts->get('fr', 'acceptAll'));
        self::assertSame('Deny all cookies', $texts->get('en', 'denyAll'));
    }

    public function testEveryCuratedTextExistsInEveryVendoredLanguage(): void
    {
        $texts = new VendorLanguageTexts();
        $missing = [];

        foreach (glob(dirname(__DIR__, 4) . '/public/tarteaucitron/lang/tarteaucitron.*.js') ?: [] as $file) {
            if (1 !== preg_match('/tarteaucitron\.([a-z-]+)\.js$/', $file, $matches)) {
                continue;
            }
            foreach (LocalizedOptions::TEXTS as $langKey) {
                if (null === $texts->get($matches[1], $langKey)) {
                    $missing[] = $matches[1] . '.' . $langKey;
                }
            }
        }

        self::assertSame([], $missing);
    }

    public function testUnknownOrUnsafeLanguageReturnsNull(): void
    {
        $texts = new VendorLanguageTexts();

        self::assertNull($texts->get('xx', 'acceptAll'));
        self::assertNull($texts->get('../../../etc/passwd', 'acceptAll'));
        self::assertNull($texts->get('fr', 'noSuchKey'));
    }
}

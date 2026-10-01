<?php

declare(strict_types=1);

namespace CylleneDigital\SyliusTarteaucitronPlugin\Tests\Unit\Consent\Localized;

use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Localized\LocalizedOptions;
use PHPUnit\Framework\TestCase;

final class LocalizedOptionsTest extends TestCase
{
    public function testKeepsSafeLinksAndTextsPerLocale(): void
    {
        self::assertSame(
            ['fr_FR' => ['privacy_url' => '/fr/confidentialite', 'accept_all' => 'J’accepte']],
            LocalizedOptions::normalize([
                'fr_FR' => ['privacy_url' => ' /fr/confidentialite ', 'accept_all' => 'J’accepte', 'deny_all' => '  '],
                'en_US' => ['deny_all' => ''],
            ]),
        );
    }

    public function testDropsWhatCouldBreakOutOfTheLibraryMarkup(): void
    {
        self::assertSame([], LocalizedOptions::normalize([
            'fr_FR' => [
                'privacy_url' => 'javascript:alert(1)',
                'readmore_link' => '//evil.test/path',
                'accept_all' => 'OK" onmouseover="alert(1)',
                'deny_all' => '<img src=x onerror=alert(1)>',
                'close' => str_repeat('a', LocalizedOptions::TEXT_MAX_LENGTH + 1),
                'unknown_key' => 'value',
            ],
        ]));
    }

    public function testIgnoresMalformedData(): void
    {
        self::assertSame([], LocalizedOptions::normalize('not an array'));
        self::assertSame([], LocalizedOptions::normalize([0 => ['accept_all' => 'x'], 'fr_FR' => 'x']));
        self::assertSame([], LocalizedOptions::normalize(['fr_FR' => ['accept_all' => 42]]));
    }
}

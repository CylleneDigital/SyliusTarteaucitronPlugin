<?php

declare(strict_types=1);

namespace CylleneDigital\SyliusTarteaucitronPlugin\Tests\Unit\Consent;

use CylleneDigital\SyliusTarteaucitronPlugin\Consent\InlineJson;
use PHPUnit\Framework\TestCase;

final class InlineJsonTest extends TestCase
{
    public function testEncodesScriptBreakout(): void
    {
        $json = InlineJson::encode('</script><script>alert(1)');

        self::assertStringNotContainsString('</script>', $json);
        self::assertStringContainsString('\u003C/script\u003E', $json);
    }

    public function testEncodesOfficialInitObject(): void
    {
        $json = InlineJson::encode(['cookieName' => 'tarteaucitron', 'highPrivacy' => true]);

        self::assertStringContainsString('"cookieName":"tarteaucitron"', $json);
        self::assertStringContainsString('"highPrivacy":true', $json);
    }

    public function testQuotesAreEscapedSoTheFilterIsInertInAnAttribute(): void
    {
        $json = InlineJson::encode(['x' => 'a" onclick="b\'c']);

        self::assertStringNotContainsString('"b', $json);
        self::assertStringNotContainsString("'", $json);
        self::assertSame(['x' => 'a" onclick="b\'c'], json_decode($json, true));
    }
}

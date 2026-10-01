<?php

declare(strict_types=1);

namespace Tests\CylleneDigital\SyliusTarteaucitronPlugin\Unit\Consent\Tracker;

use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\TrackerParameter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class TrackerParameterTest extends TestCase
{
    public function testPlainIdentifierIsAccepted(): void
    {
        self::assertSame('gtagUa', (new TrackerParameter('gtag_ua', 'gtagUa'))->userKey);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function unsafeUserKeys(): iterable
    {
        yield 'statement break' => ['id; alert(1); //'];
        yield 'property access' => ['a.b'];
        yield 'leading digit' => ['1id'];
        yield 'trailing newline' => ["id\n"];
        yield 'empty' => [''];
    }

    #[DataProvider('unsafeUserKeys')]
    public function testUserKeyThatIsNotAnIdentifierIsRefused(string $userKey): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new TrackerParameter('acme_id', $userKey);
    }
}

<?php

declare(strict_types=1);

namespace CylleneDigital\SyliusTarteaucitronPlugin\Tests\Unit\Csp;

use CylleneDigital\SyliusTarteaucitronPlugin\Csp\NelmioScriptNonceProvider;
use PHPUnit\Framework\TestCase;

final class NelmioScriptNonceProviderTest extends TestCase
{
    public function testAsksTheListenerForTheScriptNonceOfTheResponse(): void
    {
        $listener = new class() {
            /** @var list<string> */
            public array $usages = [];

            public function getNonce(string $usage): string
            {
                $this->usages[] = $usage;

                return 'n0nce';
            }
        };

        self::assertSame('n0nce', (new NelmioScriptNonceProvider($listener))->getScriptNonce());
        self::assertSame(['script'], $listener->usages);
    }
}

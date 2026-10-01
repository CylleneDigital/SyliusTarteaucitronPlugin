<?php

declare(strict_types=1);

namespace CylleneDigital\SyliusTarteaucitronPlugin\Tests\Unit\Consent\Init;

use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Init\InitOption;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Init\InitOptionCatalog;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Init\IntegrationOptions;
use PHPUnit\Framework\TestCase;

/**
 * Every tarteaucitron.init() option the vendored library reads must be exposed (back office or
 * `integration:` bundle configuration) or listed as a deliberate gap: a new upstream option then
 * fails CI on the library bump instead of passing unnoticed.
 */
final class VendorInitOptionsParityTest extends TestCase
{
    /** @var list<string> jsKeys read by the library and deliberately not exposed */
    private const ACKNOWLEDGED_GAPS = [];

    public function testEveryOptionReadByTheLibraryIsExposedOrAcknowledged(): void
    {
        $missing = array_diff(self::vendorParameters(), self::catalogJsKeys(), self::ACKNOWLEDGED_GAPS);

        self::assertSame(
            [],
            array_values($missing),
            'tarteaucitron.js reads init options the plugin does not expose. Add them to InitOptionCatalog, IntegrationOptions or ACKNOWLEDGED_GAPS.',
        );
    }

    public function testEveryExposedOptionIsReadByTheLibrary(): void
    {
        $unread = array_diff(self::catalogJsKeys(), self::vendorParameters());

        self::assertSame(
            [],
            array_values($unread),
            'The plugin exposes init options tarteaucitron.js never reads.',
        );
    }

    public function testAcknowledgedGapsAreStillGaps(): void
    {
        $stale = array_diff(
            self::ACKNOWLEDGED_GAPS,
            array_diff(self::vendorParameters(), self::catalogJsKeys()),
        );

        self::assertSame([], array_values($stale), 'Remove these entries from ACKNOWLEDGED_GAPS.');
    }

    /** @return list<string> */
    private static function catalogJsKeys(): array
    {
        return [
            ...array_map(static fn (InitOption $option): string => $option->jsKey, (new InitOptionCatalog())->all()),
            ...array_values(IntegrationOptions::JS_KEYS),
        ];
    }

    /** @return list<string> */
    private static function vendorParameters(): array
    {
        $contents = (string) file_get_contents(dirname(__DIR__, 4) . '/public/tarteaucitron/tarteaucitron.js');

        // The negative lookahead drops method calls such as parameters.hasOwnProperty(…).
        preg_match_all('/tarteaucitron\.parameters\.([A-Za-z0-9_]+)\b(?!\s*\()/', $contents, $matches);

        $parameters = array_values(array_unique($matches[1]));
        sort($parameters);

        return $parameters;
    }
}

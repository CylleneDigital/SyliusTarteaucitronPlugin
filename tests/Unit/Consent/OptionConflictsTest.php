<?php

declare(strict_types=1);

namespace Tests\CylleneDigital\SyliusTarteaucitronPlugin\Unit\Consent;

use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Init\InitOptionCatalog;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Init\InitOptions;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\OptionConflicts;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class OptionConflictsTest extends TestCase
{
    public function testCatalogDefaultsRaiseNothing(): void
    {
        self::assertSame([], $this->fields([], []));
    }

    /**
     * @param array<string, mixed> $options
     * @param array<string, mixed> $integrationInit
     * @param list<string>         $expected
     */
    #[DataProvider('settings')]
    public function testConflictIsReported(array $options, array $integrationInit, array $expected): void
    {
        self::assertSame($expected, $this->fields($options, $integrationInit));
    }

    /** @return iterable<string, array{array<string, mixed>, array<string, mixed>, list<string>}> */
    public static function settings(): iterable
    {
        yield 'grouping with the ad-blocker detection' => [['group_services' => true], ['adblocker' => true], ['group_services']];
        yield 'grouping without the ad-blocker detection' => [['group_services' => true], ['adblocker' => false], []];
        yield 'ad-blocker detection without grouping' => [['group_services' => false], ['adblocker' => true], []];
        yield 'compact banner and icon' => [['show_alert_small' => true, 'show_icon' => true], [], ['show_alert_small']];
        yield 'compact banner alone' => [['show_alert_small' => true, 'show_icon' => false], [], []];
    }

    /**
     * @param array<string, mixed> $options
     * @param array<string, mixed> $integrationInit
     *
     * @return list<string>
     */
    private function fields(array $options, array $integrationInit): array
    {
        $conflicts = (new OptionConflicts($integrationInit))->check(InitOptions::fromArray($options, new InitOptionCatalog()));

        return array_map(static fn (array $conflict): string => $conflict['field'], $conflicts);
    }
}

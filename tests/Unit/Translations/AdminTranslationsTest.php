<?php

declare(strict_types=1);

namespace CylleneDigital\SyliusTarteaucitronPlugin\Tests\Unit\Translations;

use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Init\InitOptionCatalog;
use CylleneDigital\SyliusTarteaucitronPlugin\Tests\Unit\Tracker\TrackerTestKit;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * The back office renders a label and a help text for every init option, and a label for every
 * built-in service: a missing key shows up as a raw translation id on the admin screen.
 */
final class AdminTranslationsTest extends TestCase
{
    public function testLocalesAreSymmetric(): void
    {
        self::assertSame(self::keys('en'), self::keys('fr'));
    }

    #[DataProvider('locales')]
    public function testEveryInitOptionHasALabelAHelpAndChoiceLabels(string $locale): void
    {
        $keys = self::keys($locale);
        $missing = [];

        foreach ((new InitOptionCatalog())->all() as $option) {
            $expected = [$option->getLabel(), $option->getHelp(), ...array_keys($option->choices)];
            foreach ($expected as $key) {
                if (!in_array($key, $keys, true)) {
                    $missing[] = $key;
                }
            }
        }

        self::assertSame([], $missing);
    }

    #[DataProvider('locales')]
    public function testEveryBuiltInTrackerHasALabel(string $locale): void
    {
        $keys = self::keys($locale);
        $missing = [];

        foreach (TrackerTestKit::all() as $tracker) {
            $key = 'cyllene_digital_sylius_tarteaucitron.ui.service_' . $tracker->getType();
            if (!in_array($key, $keys, true)) {
                $missing[] = $key;
            }
        }

        self::assertSame([], $missing);
    }

    /** @return iterable<string, array{string}> */
    public static function locales(): iterable
    {
        yield 'en' => ['en'];
        yield 'fr' => ['fr'];
    }

    /** @return list<string> */
    private static function keys(string $locale): array
    {
        $tree = Yaml::parseFile(dirname(__DIR__, 3) . '/translations/messages.' . $locale . '.yml');
        self::assertIsArray($tree);

        $keys = self::flatten($tree);
        sort($keys);

        return $keys;
    }

    /**
     * @param array<mixed> $tree
     *
     * @return list<string>
     */
    private static function flatten(array $tree, string $prefix = ''): array
    {
        $keys = [];
        foreach ($tree as $key => $value) {
            $path = $prefix . $key;
            if (is_array($value)) {
                $keys = [...$keys, ...self::flatten($value, $path . '.')];
            } else {
                $keys[] = $path;
            }
        }

        return $keys;
    }
}

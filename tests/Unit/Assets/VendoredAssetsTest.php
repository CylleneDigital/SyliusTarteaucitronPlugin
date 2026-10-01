<?php

declare(strict_types=1);

namespace Tests\CylleneDigital\SyliusTarteaucitronPlugin\Unit\Assets;

use PHPUnit\Framework\TestCase;

/**
 * The official install loads lang/, css/, services and advertising from
 * document.currentScript.src. Missing files break the banner (C-1, C-2).
 */
final class VendoredAssetsTest extends TestCase
{
    public function testVendoredVersionMatchesLibrary(): void
    {
        $dir = $this->assetsDir();
        $declared = trim((string) file_get_contents($dir . '/VERSION'));

        preg_match('/version:"([^"]+)"/', (string) file_get_contents($dir . '/tarteaucitron.min.js'), $matches);

        self::assertSame($declared, $matches[1] ?? null, 'VERSION and tarteaucitron.min.js have diverged.');
    }

    public function testProvenanceMatchesVendoredVersion(): void
    {
        $dir = $this->assetsDir();
        $declared = trim((string) file_get_contents($dir . '/VERSION'));

        preg_match('/^tag: v?(\S+)$/m', (string) file_get_contents($dir . '/SOURCE'), $matches);

        self::assertSame($declared, $matches[1] ?? null, 'SOURCE was not written by bin/update-tarteaucitron.sh for this VERSION.');
    }

    public function testEveryFileTheLibraryCanRequestIsShipped(): void
    {
        $dir = $this->assetsDir();

        self::assertFileExists($dir . '/tarteaucitron.services.min.js');
        self::assertFileExists($dir . '/advertising.min.js');
        self::assertFileExists($dir . '/css/tarteaucitron.min.css');

        foreach ($this->supportedLanguages() as $language) {
            self::assertFileExists($dir . '/lang/tarteaucitron.' . $language . '.min.js');
        }
    }

    /** @return list<string> */
    private function supportedLanguages(): array
    {
        $matched = preg_match(
            '/availableLanguages="([^"]+)"/',
            (string) file_get_contents($this->assetsDir() . '/tarteaucitron.min.js'),
            $matches,
        );
        self::assertSame(1, $matched, 'availableLanguages not found in tarteaucitron.min.js.');

        $languageList = $matches[1] ?? '';
        self::assertNotSame('', $languageList);

        return explode(',', $languageList);
    }

    private function assetsDir(): string
    {
        return dirname(__DIR__, 3) . '/public/tarteaucitron';
    }
}

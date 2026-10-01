<?php

declare(strict_types=1);

namespace Tests\CylleneDigital\SyliusTarteaucitronPlugin\Unit\Consent;

use CylleneDigital\SyliusTarteaucitronPlugin\Consent\VendorLibrary;
use PHPUnit\Framework\TestCase;

final class VendorLibraryTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . '/tac-library-' . bin2hex(random_bytes(4));
        mkdir($this->directory . '/css', 0777, true);
        file_put_contents($this->directory . '/VERSION', "1.35.0\n");
        file_put_contents($this->directory . '/tarteaucitron.min.js', 'var availableLanguages="ar,de,en";version:"1.35.0"');
        file_put_contents($this->directory . '/css/sylius-fix.css', 'a{}');
    }

    protected function tearDown(): void
    {
        foreach (['css/sylius-fix.css', 'tarteaucitron.min.js', 'VERSION'] as $file) {
            unlink($this->directory . '/' . $file);
        }
        rmdir($this->directory . '/css');
        rmdir($this->directory);
    }

    public function testReadsTheVersionAndTheLanguages(): void
    {
        self::assertSame('1.35.0', VendorLibrary::version($this->directory));
        self::assertSame(['ar', 'de', 'en'], VendorLibrary::availableLanguages($this->directory));
    }

    public function testFingerprintsFollowTheFileContent(): void
    {
        $first = VendorLibrary::fingerprints($this->directory);
        file_put_contents($this->directory . '/css/sylius-fix.css', 'b{}');
        $second = VendorLibrary::fingerprints($this->directory);

        self::assertSame(['VERSION', 'css/sylius-fix.css', 'tarteaucitron.min.js'], array_keys($first));
        self::assertMatchesRegularExpression('/^[0-9a-f]{12}$/', $first['css/sylius-fix.css']);
        self::assertNotSame($first['css/sylius-fix.css'], $second['css/sylius-fix.css']);
        self::assertSame($first['VERSION'], $second['VERSION']);
    }

    public function testShippedLibraryIsReadable(): void
    {
        self::assertContains('fr', VendorLibrary::availableLanguages(VendorLibrary::directory()));
        self::assertArrayHasKey('css/sylius-fix.css', VendorLibrary::fingerprints(VendorLibrary::directory()));
    }
}

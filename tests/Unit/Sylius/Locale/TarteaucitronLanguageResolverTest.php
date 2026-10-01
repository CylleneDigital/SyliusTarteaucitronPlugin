<?php

declare(strict_types=1);

namespace CylleneDigital\SyliusTarteaucitronPlugin\Tests\Unit\Sylius\Locale;

use CylleneDigital\SyliusTarteaucitronPlugin\Sylius\Locale\TarteaucitronLanguageResolver;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Locale\Context\LocaleContextInterface;
use Sylius\Component\Locale\Context\LocaleNotFoundException;

final class TarteaucitronLanguageResolverTest extends TestCase
{
    private string $libraryFile;

    protected function setUp(): void
    {
        if (!interface_exists(LocaleContextInterface::class)) {
            self::markTestSkipped('sylius/sylius is not installed.');
        }

        $file = tempnam(sys_get_temp_dir(), 'tac-lib-');
        self::assertNotFalse($file);
        $this->libraryFile = $file;
        file_put_contents(
            $this->libraryFile,
            'var availableLanguages="ar,de,en,es,fr,no,sv,zh";version:"1.34.0"',
        );
    }

    protected function tearDown(): void
    {
        if (isset($this->libraryFile) && is_file($this->libraryFile)) {
            unlink($this->libraryFile);
        }
    }

    public function testMapsFullLocaleToPrimarySubtag(): void
    {
        self::assertSame('es', $this->resolver('es_ES')->resolve());
        self::assertSame('de', $this->resolver('de')->resolve());
        self::assertSame('zh', $this->resolver('zh_CN')->resolve());
    }

    public function testUnknownLocaleFallsBackToEnglish(): void
    {
        self::assertSame('en', $this->resolver('xx')->resolve());
    }

    public function testNorwegianBokmalAliasMapsToVendorNo(): void
    {
        self::assertSame('no', $this->resolver('nb_NO')->resolve());
        self::assertSame('no', $this->resolver('nb')->resolve());
    }

    public function testProjectsAnyLocaleNotOnlyTheCurrentOne(): void
    {
        $resolver = $this->resolver('en_US');

        self::assertSame('es', $resolver->forLocale('es_ES'));
        self::assertSame('no', $resolver->forLocale('nb_NO'));
        self::assertSame('en', $resolver->forLocale('xx_XX'));
        self::assertSame('en_US', $resolver->currentLocaleCode());
    }

    public function testMissingLocaleFallsBackToEnglish(): void
    {
        $localeContext = $this->createMock(LocaleContextInterface::class);
        $localeContext->method('getLocaleCode')->willThrowException(new LocaleNotFoundException());

        $resolver = new TarteaucitronLanguageResolver($localeContext, $this->libraryFile);

        self::assertSame('en', $resolver->resolve());
    }

    public function testReadsAvailableLanguagesFromLibraryFile(): void
    {
        $languages = $this->resolver('en')->availableLanguages();

        self::assertSame(['ar', 'de', 'en', 'es', 'fr', 'no', 'sv', 'zh'], $languages);
    }

    private function resolver(string $locale): TarteaucitronLanguageResolver
    {
        $localeContext = $this->createMock(LocaleContextInterface::class);
        $localeContext->method('getLocaleCode')->willReturn($locale);

        return new TarteaucitronLanguageResolver($localeContext, $this->libraryFile);
    }
}

<?php

declare(strict_types=1);

namespace CylleneDigital\SyliusTarteaucitronPlugin\Tests\Unit\Twig;

use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\TrackerScriptRenderer;
use CylleneDigital\SyliusTarteaucitronPlugin\Csp\StaticScriptNonceProvider;
use CylleneDigital\SyliusTarteaucitronPlugin\Sylius\Locale\TarteaucitronLanguageResolver;
use CylleneDigital\SyliusTarteaucitronPlugin\Sylius\Provider\ConsentConfigurationProviderInterface;
use CylleneDigital\SyliusTarteaucitronPlugin\Sylius\Provider\ResolvedConsent;
use CylleneDigital\SyliusTarteaucitronPlugin\Tests\Unit\Tracker\TrackerTestKit;
use CylleneDigital\SyliusTarteaucitronPlugin\Twig\TarteaucitronRuntime;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Locale\Context\LocaleContextInterface;

final class TarteaucitronRuntimeTest extends TestCase
{
    public function testJsonFilterMatchesInlineEncoder(): void
    {
        $json = $this->runtime()->json(['cookieName' => '</script><script>alert(1)']);

        self::assertStringNotContainsString('</script>', $json);
        self::assertStringContainsString('\u003C/script\u003E', $json);
    }

    public function testScriptNonceEmptyIsOmitted(): void
    {
        self::assertNull($this->runtime('')->scriptNonce());
        self::assertNull($this->runtime()->scriptNonce());
        self::assertSame('abc123', $this->runtime('abc123')->scriptNonce());
    }

    public function testInitReturnsOfficialJsKeys(): void
    {
        $init = $this->runtime()->init();

        self::assertSame('tarteaucitron', $init['cookieName']);
        self::assertTrue($init['highPrivacy']);
    }

    public function testCurrentLocaleLinksOverrideTheChannelOnes(): void
    {
        $init = $this->runtime()->init();

        self::assertSame('/en/privacy', $init['privacyUrl']);
        self::assertArrayNotHasKey('readmoreLink', $init);
    }

    public function testCustomTextIsTheCurrentLocaleOneKeyedByVendorKey(): void
    {
        self::assertSame(['acceptAll' => 'Sure'], $this->runtime()->customText());
    }

    public function testConsentLifetimeComesFromTheResolvedConfiguration(): void
    {
        self::assertSame(120, $this->runtime()->consentLifetimeDays());
    }

    public function testAssetVersionFollowsTheFileContent(): void
    {
        $directory = sys_get_temp_dir() . '/tac-assets-' . bin2hex(random_bytes(4));
        mkdir($directory . '/css', 0777, true);
        file_put_contents($directory . '/css/sylius-fix.css', 'a{}');

        $first = $this->runtime(assetsDirectory: $directory)->assetVersion('css/sylius-fix.css');
        file_put_contents($directory . '/css/sylius-fix.css', 'b{}');
        $second = $this->runtime(assetsDirectory: $directory)->assetVersion('css/sylius-fix.css');

        self::assertMatchesRegularExpression('/^[0-9a-f]{12}$/', $first);
        self::assertNotSame($first, $second);

        unlink($directory . '/css/sylius-fix.css');
        rmdir($directory . '/css');
        rmdir($directory);
    }

    public function testAssetVersionOfTheShippedStylesheet(): void
    {
        self::assertMatchesRegularExpression('/^[0-9a-f]{12}$/', $this->runtime()->assetVersion('css/sylius-fix.css'));
    }

    public function testAssetVersionStaysInsideTheVendoredDirectory(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->runtime()->assetVersion('../../composer.json');
    }

    private function runtime(?string $nonce = null, ?string $assetsDirectory = null): TarteaucitronRuntime
    {
        if (!interface_exists(LocaleContextInterface::class)) {
            self::markTestSkipped('sylius/sylius is not installed.');
        }
        $provider = $this->createMock(ConsentConfigurationProviderInterface::class);
        $provider->method('resolve')->willReturn(new ResolvedConsent(
            true,
            ['cookieName' => 'tarteaucitron', 'highPrivacy' => true],
            [],
            120,
            ['en' => ['privacy_url' => '/en/privacy', 'accept_all' => 'Sure'], 'fr' => ['accept_all' => 'Oui']],
        ));

        $registry = TrackerTestKit::registry();
        $localeContext = $this->createMock(LocaleContextInterface::class);
        $localeContext->method('getLocaleCode')->willReturn('en');

        return new TarteaucitronRuntime(
            $provider,
            $registry,
            new TrackerScriptRenderer($registry),
            new TarteaucitronLanguageResolver($localeContext),
            new StaticScriptNonceProvider($nonce),
            $assetsDirectory,
        );
    }
}

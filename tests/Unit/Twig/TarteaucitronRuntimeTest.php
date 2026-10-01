<?php

declare(strict_types=1);

namespace Tests\CylleneDigital\SyliusTarteaucitronPlugin\Unit\Twig;

use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\TrackerScriptRenderer;
use CylleneDigital\SyliusTarteaucitronPlugin\Csp\StaticScriptNonceProvider;
use CylleneDigital\SyliusTarteaucitronPlugin\Sylius\Locale\TarteaucitronLanguageResolver;
use CylleneDigital\SyliusTarteaucitronPlugin\Sylius\Provider\ConsentConfigurationProviderInterface;
use CylleneDigital\SyliusTarteaucitronPlugin\Sylius\Provider\ResolvedConsent;
use CylleneDigital\SyliusTarteaucitronPlugin\Twig\TarteaucitronRuntime;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Locale\Context\LocaleContextInterface;
use Tests\CylleneDigital\SyliusTarteaucitronPlugin\Unit\Tracker\TrackerTestKit;

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
        self::assertSame('/channel/readmore', $init['readmoreLink'], 'A link the locale leaves empty keeps the channel one.');
    }

    public function testCustomTextIsTheCurrentLocaleOneKeyedByVendorKey(): void
    {
        self::assertSame(['acceptAll' => 'Sure'], $this->runtime()->customText());
    }

    public function testConsentLifetimeComesFromTheResolvedConfiguration(): void
    {
        self::assertSame(120, $this->runtime()->consentLifetimeDays());
    }

    public function testAssetVersionIsTheCompiledFingerprint(): void
    {
        self::assertSame('0123456789ab', $this->runtime()->assetVersion('css/sylius-fix.css'));
    }

    public function testAssetVersionRefusesAFileOutsideTheVendoredLibrary(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->runtime()->assetVersion('../../composer.json');
    }

    private function runtime(?string $nonce = null): TarteaucitronRuntime
    {
        $provider = $this->createMock(ConsentConfigurationProviderInterface::class);
        $provider->method('resolve')->willReturn(new ResolvedConsent(
            true,
            ['cookieName' => 'tarteaucitron', 'highPrivacy' => true, 'privacyUrl' => '/channel/privacy', 'readmoreLink' => '/channel/readmore'],
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
            new TarteaucitronLanguageResolver($localeContext, ['en', 'fr'], []),
            new StaticScriptNonceProvider($nonce),
            ['css/sylius-fix.css' => '0123456789ab'],
        );
    }
}

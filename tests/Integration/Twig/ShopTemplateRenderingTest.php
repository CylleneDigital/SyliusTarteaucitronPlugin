<?php

declare(strict_types=1);

namespace Tests\CylleneDigital\SyliusTarteaucitronPlugin\Integration\Twig;

use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\TrackerRuntimeState;
use CylleneDigital\SyliusTarteaucitronPlugin\Csp\StaticScriptNonceProvider;
use CylleneDigital\SyliusTarteaucitronPlugin\Sylius\Provider\ConsentConfigurationProviderInterface;
use CylleneDigital\SyliusTarteaucitronPlugin\Sylius\Provider\ResolvedConsent;
use Sylius\Component\Locale\Context\LocaleContextInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Twig\Environment;

/**
 * Renders the real shop template through the real Twig extension: locks the invariant that every
 * value written into JavaScript goes through `|tarteaucitron_json`, whatever the database holds.
 */
final class ShopTemplateRenderingTest extends KernelTestCase
{
    private const HOSTILE = '</script><script>alert(1)</script>"\'&<!--';

    /** How `</script>` must come out of `|tarteaucitron_json`. */
    private const ESCAPED_END_TAG = '\u003C/script\u003E';

    public function testNothingIsRenderedWhenDisabled(): void
    {
        self::assertSame('', trim($this->render(new ResolvedConsent(false, [], []))));
    }

    public function testHostileValuesCannotLeaveTheScripts(): void
    {
        $html = $this->render(new ResolvedConsent(
            true,
            ['privacyUrl' => self::HOSTILE, 'cookieName' => self::HOSTILE],
            [new TrackerRuntimeState('gtag', ['gtag_ua' => self::HOSTILE])],
            180,
            ['en_US' => ['accept_all' => self::HOSTILE]],
        ));

        self::assertSame(3, substr_count($html, '<script'), $html);
        self::assertSame(3, substr_count($html, '</script>'), $html);
        self::assertStringNotContainsString('<!--', $html);
        self::assertStringNotContainsString('alert(1)</', $html);
        self::assertStringContainsString('"privacyUrl":"' . self::ESCAPED_END_TAG, $html);
        self::assertStringContainsString('tarteaucitron.user.gtagUa = "' . self::ESCAPED_END_TAG, $html);
        self::assertStringContainsString('var tarteaucitronCustomText = {"acceptAll":"' . self::ESCAPED_END_TAG, $html);
    }

    public function testEnabledTrackerIsPushedWithItsParameters(): void
    {
        $html = $this->render(new ResolvedConsent(
            true,
            [],
            [
                new TrackerRuntimeState('gtag', ['gtag_ua' => 'G-TEST']),
                new TrackerRuntimeState('youtube'),
                new TrackerRuntimeState('hotjar', ['hotjar_id' => '']),
            ],
        ));

        self::assertStringContainsString('tarteaucitron.user.gtagUa = "G-TEST";', $html);
        self::assertStringContainsString('(tarteaucitron.job = tarteaucitron.job || []).push("gtag");', $html);
        self::assertStringContainsString('(tarteaucitron.job = tarteaucitron.job || []).push("youtube");', $html);
        self::assertStringNotContainsString('push("hotjar")', $html, 'A required parameter left empty keeps the service off.');
    }

    public function testNonceIsOnEveryScriptAndPreload(): void
    {
        $html = $this->render(new ResolvedConsent(true, [], []), 'n0nce');

        self::assertSame(3, substr_count($html, '<script nonce="n0nce"'), $html);
        self::assertSame(2, substr_count($html, '<link rel="preload" as="script" fetchpriority="low" href="'), $html);
        self::assertSame(2, preg_match_all('/<link rel="preload"[^>]* nonce="n0nce">/', $html), $html);
    }

    public function testNoNonceAttributeWithoutNonce(): void
    {
        self::assertStringNotContainsString('nonce=', $this->render(new ResolvedConsent(true, [], [])));
    }

    public function testNoPreloadWhenTheThemeShipsTheLibraryFiles(): void
    {
        $html = $this->render(new ResolvedConsent(true, ['useExternalJs' => true, 'useExternalCss' => true], []));

        self::assertStringNotContainsString('rel="preload"', $html);
        self::assertStringContainsString('"useExternalJs":true', $html);
    }

    public function testLibraryStylesheetIsPreloadedUnlessTheThemeShipsIt(): void
    {
        self::assertStringContainsString('as="style" fetchpriority="low" href="/bundles/cyllenedigitalsyliustarteaucitronplugin/tarteaucitron/css/tarteaucitron.min.css?v=', $this->render(new ResolvedConsent(true, [], [])));
        self::assertStringNotContainsString('as="style"', $this->render(new ResolvedConsent(true, ['useExternalCss' => true], [])));
    }

    private function render(ResolvedConsent $consent, ?string $nonce = null): string
    {
        self::bootKernel();
        $container = self::getContainer();

        $provider = $this->createMock(ConsentConfigurationProviderInterface::class);
        $provider->method('resolve')->willReturn($consent);
        $container->set('cyllene_digital_sylius_tarteaucitron.provider.consent', $provider);
        $container->set('cyllene_digital_sylius_tarteaucitron.csp.script_nonce_provider', new StaticScriptNonceProvider($nonce));

        $localeContext = $this->createMock(LocaleContextInterface::class);
        $localeContext->method('getLocaleCode')->willReturn('en_US');
        $container->set('sylius.context.locale', $localeContext);

        $twig = $container->get('twig');
        \assert($twig instanceof Environment);

        return $twig->render('@CylleneDigitalSyliusTarteaucitronPlugin/shop/tarteaucitron.html.twig');
    }
}

<?php

declare(strict_types=1);

namespace CylleneDigital\SyliusTarteaucitronPlugin\Tests\Behat\Page\Shop;

use Sylius\Behat\Page\SyliusPage;

final class HomepageWithConsentPage extends SyliusPage
{
    private const BANNER_TIMEOUT_MS = 10000;

    public function getRouteName(): string
    {
        return 'sylius_shop_homepage';
    }

    public function hasTarteaucitronInitScript(): bool
    {
        return str_contains($this->getDocument()->getContent(), 'tarteaucitron.init(');
    }

    public function getPluginStylesheetUrl(): ?string
    {
        return $this->getDocument()->find('css', 'link[href*="sylius-fix.css"]')?->getAttribute('href');
    }

    public function getLibraryScriptUrl(): ?string
    {
        return $this->getDocument()->find('css', 'script[src*="tarteaucitron.min.js"]')?->getAttribute('src');
    }

    public function getForcedConsentLifetime(): ?int
    {
        $matched = preg_match('/tarteaucitronForceExpire = (\d+);/', $this->getDocument()->getContent(), $matches);

        return 1 === $matched ? (int) $matches[1] : null;
    }

    /**
     * The library inserts #tarteaucitronRoot only once its lang/ file loaded (C-1), and swaps the
     * banner for an anti-adblock screen without any consent button when advertising.min.js is
     * missing (C-2): a visible "accept all" button inside the banner proves both chains completed.
     * The library only opens the banner when an enabled service needs consent.
     */
    public function waitForConsentBanner(): bool
    {
        return $this->getDriver()->wait(
            self::BANNER_TIMEOUT_MS,
            "document.querySelector('#tarteaucitronRoot #tarteaucitronAlertBig .tarteaucitronAllow')?.checkVisibility() === true",
        );
    }

    /**
     * The library adds the cross 100 ms after the banner; sylius-fix.css once made it `relative`,
     * which dropped it onto the call-to-action row.
     */
    public function isCloseCrossInTheTopCorner(): bool
    {
        $this->getDriver()->wait(2000, "document.getElementById('tarteaucitronCloseCross') !== null");

        return true === $this->getDriver()->evaluateScript(<<<'JS'
            (() => {
                const cross = document.getElementById('tarteaucitronCloseCross');
                if (!cross || getComputedStyle(cross).position !== 'absolute') {
                    return false;
                }
                const c = cross.getBoundingClientRect();
                const b = document.getElementById('tarteaucitronAlertBig').getBoundingClientRect();
                return c.top - b.top < 40 && b.right - c.right < 60;
            })()
            JS);
    }

    /**
     * The library wires the banner buttons 500 ms after inserting them; a click before that is lost,
     * and a second one would land on the panel backdrop and close it again.
     */
    public function openPreferences(): void
    {
        $this->waitForConsentBanner();
        $this->getDriver()->wait(800, 'false');
        $this->getDocument()->find('css', '#tarteaucitronCloseAlert')?->click();
        $this->getDriver()->wait(2000, "document.body.classList.contains('tarteaucitron-modal-open')");
    }

    /** Same 500 ms wiring delay as the banner buttons. */
    public function openPreferencesFromIcon(): void
    {
        $this->getDriver()->wait(
            self::BANNER_TIMEOUT_MS,
            "document.querySelector('#tarteaucitronIcon #tarteaucitronManager')?.checkVisibility() === true",
        );
        $this->getDriver()->wait(800, 'false');
        $this->getDocument()->find('css', '#tarteaucitronIcon #tarteaucitronManager')?->click();
        $this->getDriver()->wait(2000, "document.body.classList.contains('tarteaucitron-modal-open')");
    }

    /** sylius-fix.css once kept the banner above everything, hiding the panel it opened. */
    public function isPreferencesPanelInFront(): bool
    {
        return true === $this->getDriver()->evaluateScript(<<<'JS'
            (() => {
                const panel = document.getElementById('tarteaucitron').getBoundingClientRect();
                const banner = document.getElementById('tarteaucitronAlertBig').getBoundingClientRect();
                const x = (Math.max(panel.left, banner.left) + Math.min(panel.right, banner.right)) / 2;
                const y = (Math.max(panel.top, banner.top) + Math.min(panel.bottom, banner.bottom)) / 2;
                return document.elementFromPoint(x, y)?.closest('#tarteaucitron') !== null;
            })()
            JS);
    }

    public function closePreferencesByClickingOutside(): void
    {
        $this->getDocument()->find('css', '#tarteaucitronBack')?->click();
        $this->getDriver()->wait(2000, "!document.body.classList.contains('tarteaucitron-modal-open')");
    }

    public function isBannerShown(): bool
    {
        return true === $this->getDriver()->evaluateScript(
            "document.getElementById('tarteaucitronAlertBig')?.checkVisibility({visibilityProperty: true}) === true",
        );
    }

    public function isPreferencesPanelOpen(): bool
    {
        return true === $this->getDriver()->evaluateScript("document.body.classList.contains('tarteaucitron-modal-open')");
    }

    public function isInitOptionOn(string $jsKey): bool
    {
        return true === $this->getDriver()->evaluateScript('tarteaucitron.parameters.' . $jsKey);
    }

    public function getAcceptAllButtonText(): string
    {
        $text = $this->getDriver()->evaluateScript(
            "document.querySelector('#tarteaucitronAlertBig .tarteaucitronAllow')?.textContent.trim() ?? ''",
        );

        return is_string($text) ? $text : '';
    }

    public function hasAntiAdblockScreen(): bool
    {
        return (bool) $this->getDriver()->evaluateScript(
            "document.getElementById('tarteaucitronCTAButton') !== null",
        );
    }

    public function getConsentLanguage(): string
    {
        $language = $this->getDriver()->evaluateScript('tarteaucitron.getLanguage()');

        return is_string($language) ? $language : '';
    }
}

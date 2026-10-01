<?php

declare(strict_types=1);

namespace Tests\CylleneDigital\SyliusTarteaucitronPlugin\Behat\Page\Shop;

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
        $this->waitFor(2000, "document.getElementById('tarteaucitronCloseCross') !== null", 'the close cross');

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
        $this->waitForBannerOrFail();
        $this->getDriver()->wait(800, 'false');
        $this->click('#tarteaucitronCloseAlert');
        $this->waitFor(2000, "document.body.classList.contains('tarteaucitron-modal-open')", 'the preferences panel to open');
    }

    /** Same 500 ms wiring delay as "personalize". */
    public function acceptAllFromBanner(): void
    {
        $this->clickBannerButton('#tarteaucitronPersonalize2');
    }

    public function denyAllFromBanner(): void
    {
        $this->clickBannerButton('#tarteaucitronAllDenied2');
    }

    /** The choice the library stores in its cookie (`!youtube=true!…`), empty when none was made. */
    public function getStoredChoice(string $type): string
    {
        $choice = $this->getDriver()->evaluateScript(sprintf(
            "(document.cookie.match(new RegExp('(?:^|!)' + %s + '=(true|false|wait)')) || [])[1] || ''",
            json_encode($type, \JSON_THROW_ON_ERROR),
        ));

        return is_string($choice) ? $choice : '';
    }

    public function waitForBannerToClose(): bool
    {
        return $this->getDriver()->wait(
            2000,
            "document.getElementById('tarteaucitronAlertBig')?.checkVisibility({visibilityProperty: true}) !== true",
        );
    }

    private function clickBannerButton(string $selector): void
    {
        $this->waitForBannerOrFail();
        $this->getDriver()->wait(800, 'false');
        $this->click('#tarteaucitronAlertBig ' . $selector);
    }

    private function waitForBannerOrFail(): void
    {
        if (!$this->waitForConsentBanner()) {
            throw new \RuntimeException('The consent banner did not show up.');
        }
    }

    /** The wait is the test: a selector that matches nothing fails here, not one step later. */
    private function waitFor(int $milliseconds, string $condition, string $what): void
    {
        if (!$this->getDriver()->wait($milliseconds, $condition)) {
            throw new \RuntimeException(sprintf('Timed out waiting for %s.', $what));
        }
    }

    private function click(string $selector): void
    {
        ($this->getDocument()->find('css', $selector) ?? throw new \RuntimeException(sprintf('Nothing matches "%s".', $selector)))->click();
    }

    /** Same 500 ms wiring delay as the banner buttons. */
    public function openPreferencesFromIcon(): void
    {
        $this->waitFor(
            self::BANNER_TIMEOUT_MS,
            "document.querySelector('#tarteaucitronIcon #tarteaucitronManager')?.checkVisibility() === true",
            'the tarteaucitron icon',
        );
        $this->getDriver()->wait(800, 'false');
        $this->click('#tarteaucitronIcon #tarteaucitronManager');
        $this->waitFor(2000, "document.body.classList.contains('tarteaucitron-modal-open')", 'the preferences panel to open');
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
        $this->click('#tarteaucitronBack');
        $this->waitFor(2000, "!document.body.classList.contains('tarteaucitron-modal-open')", 'the preferences panel to close');
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

    /**
     * The library fills tarteaucitron.state once its services file loaded, and only from a boolean
     * default state.
     */
    public function waitForServiceState(string $type, bool $allowed): bool
    {
        return $this->getDriver()->wait(
            self::BANNER_TIMEOUT_MS,
            sprintf('tarteaucitron.state?.[%s] === %s', json_encode($type, \JSON_THROW_ON_ERROR), $allowed ? 'true' : 'false'),
        );
    }

    /**
     * A preload whose URL differs from the one the library builds would be fetched a second time
     * by the library's own <script>: each file must show up once, fetched by the <link>.
     *
     * @return list<string> files the library did not take from a preload
     */
    public function getLibraryFilesNotPreloaded(): array
    {
        $this->waitForConsentBanner();

        $files = $this->getDriver()->evaluateScript(<<<'JS'
            ['/lang/tarteaucitron.', '/tarteaucitron.services.min.js', '/css/tarteaucitron.min.css'].filter(function (file) {
                var entries = performance.getEntriesByType('resource').filter(function (entry) {
                    return entry.name.indexOf(file) !== -1;
                });

                return entries.length !== 1 || entries[0].initiatorType !== 'link';
            })
            JS);

        return is_array($files) ? array_values(array_filter($files, 'is_string')) : ['(no result)'];
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

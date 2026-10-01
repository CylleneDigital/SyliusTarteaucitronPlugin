<?php

declare(strict_types=1);

namespace CylleneDigital\SyliusTarteaucitronPlugin\Tests\Behat\Context\Ui\Shop;

use Behat\Behat\Context\Context;
use Behat\Step\Then;
use Behat\Step\When;
use CylleneDigital\SyliusTarteaucitronPlugin\Tests\Behat\Page\Shop\HomepageWithConsentPage;
use Sylius\Behat\Service\SharedStorageInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Webmozart\Assert\Assert;

final readonly class ConsentBannerContext implements Context
{
    public function __construct(
        private HomepageWithConsentPage $homepage,
        private SharedStorageInterface $sharedStorage,
    ) {
    }

    #[When('I open the shop homepage')]
    public function iOpenTheShopHomepage(): void
    {
        $channel = $this->sharedStorage->get('channel');
        \assert($channel instanceof ChannelInterface);

        $this->homepage->open(['_locale' => $channel->getDefaultLocale()?->getCode() ?? 'en_US']);
    }

    #[When('I open the shop homepage in the :code locale')]
    public function iOpenTheShopHomepageInTheLocale(string $code): void
    {
        $this->homepage->open(['_locale' => $code]);
    }

    #[Then('the page should contain the tarteaucitron init script')]
    public function thePageShouldContainTheTarteaucitronInitScript(): void
    {
        Assert::true(
            $this->homepage->hasTarteaucitronInitScript(),
            'Expected tarteaucitron.init(...) in the shop homepage HTML.',
        );
    }

    #[Then('the visitor\'s choice should be kept for :days days')]
    public function theVisitorsChoiceShouldBeKeptForDays(int $days): void
    {
        Assert::same($this->homepage->getForcedConsentLifetime(), $days);
    }

    #[Then('the plugin stylesheet URL should carry its content version')]
    public function thePluginStylesheetUrlShouldCarryItsContentVersion(): void
    {
        Assert::endsWith((string) $this->homepage->getPluginStylesheetUrl(), '?v=' . $this->fingerprint('css/sylius-fix.css'));
    }

    #[Then('the tarteaucitron.js URL should carry its content version')]
    public function theTarteaucitronJsUrlShouldCarryItsContentVersion(): void
    {
        Assert::endsWith((string) $this->homepage->getLibraryScriptUrl(), '?v=' . $this->fingerprint('tarteaucitron.min.js'));
    }

    private function fingerprint(string $file): string
    {
        return substr((string) hash_file('xxh128', dirname(__DIR__, 5) . '/public/tarteaucitron/' . $file), 0, 12);
    }

    #[Then('the page should not contain the tarteaucitron init script')]
    public function thePageShouldNotContainTheTarteaucitronInitScript(): void
    {
        Assert::false(
            $this->homepage->hasTarteaucitronInitScript(),
            'Did not expect tarteaucitron.init(...) in the shop homepage HTML.',
        );
    }

    #[Then('I should see the consent banner with a way to accept cookies')]
    public function iShouldSeeTheConsentBannerWithAWayToAcceptCookies(): void
    {
        Assert::true(
            $this->homepage->waitForConsentBanner(),
            'The tarteaucitron banner with its "accept all" button never appeared.',
        );
        Assert::false(
            $this->homepage->hasAntiAdblockScreen(),
            'tarteaucitron shows its anti-adblock screen instead of the consent banner.',
        );
    }

    #[When('I choose to personalize my consent')]
    public function iChooseToPersonalizeMyConsent(): void
    {
        $this->homepage->openPreferences();
    }

    #[Then('the preferences panel should be in front of the banner')]
    public function thePreferencesPanelShouldBeInFrontOfTheBanner(): void
    {
        Assert::true($this->homepage->isPreferencesPanelInFront(), 'The banner covers the preferences panel.');
    }

    #[Then('the banner should be hidden behind the preferences panel')]
    public function theBannerShouldBeHiddenBehindThePreferencesPanel(): void
    {
        Assert::false($this->homepage->isBannerShown(), 'The banner still shows under the preferences panel.');
    }

    #[Then('the banner should be shown again')]
    public function theBannerShouldBeShownAgain(): void
    {
        Assert::true($this->homepage->isBannerShown(), 'The banner did not come back after closing the panel without a choice.');
    }

    #[When('I click outside the preferences panel')]
    public function iClickOutsideThePreferencesPanel(): void
    {
        $this->homepage->closePreferencesByClickingOutside();
    }

    #[When('I open the preferences panel from the tarteaucitron icon')]
    public function iOpenThePreferencesPanelFromTheTarteaucitronIcon(): void
    {
        $this->homepage->openPreferencesFromIcon();
    }

    #[Then('the preferences panel should be open')]
    public function thePreferencesPanelShouldBeOpen(): void
    {
        Assert::true($this->homepage->isPreferencesPanelOpen(), 'The preferences panel did not open.');
    }

    #[Then('the preferences panel should be closed')]
    public function thePreferencesPanelShouldBeClosed(): void
    {
        Assert::false($this->homepage->isPreferencesPanelOpen());
    }

    #[Then('the close cross should sit in the top corner of the banner')]
    public function theCloseCrossShouldSitInTheTopCornerOfTheBanner(): void
    {
        Assert::true($this->homepage->isCloseCrossInTheTopCorner(), 'The close cross is not in the top corner of the banner.');
    }

    #[Then('the tarteaucitron init options should enable the adblocker detection')]
    public function theInitOptionsShouldEnableTheAdblockerDetection(): void
    {
        Assert::true($this->homepage->isInitOptionOn('adblocker'));
    }

    #[Then('the "accept all" button should read :text')]
    public function theAcceptAllButtonShouldRead(string $text): void
    {
        Assert::same($this->homepage->getAcceptAllButtonText(), $text);
    }

    #[Then('the consent banner should be in :language')]
    public function theConsentBannerShouldBeIn(string $language): void
    {
        Assert::same($this->homepage->getConsentLanguage(), $language);
    }
}

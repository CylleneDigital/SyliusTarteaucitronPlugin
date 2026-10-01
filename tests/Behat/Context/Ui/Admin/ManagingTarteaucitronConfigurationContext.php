<?php

declare(strict_types=1);

namespace Tests\CylleneDigital\SyliusTarteaucitronPlugin\Behat\Context\Ui\Admin;

use Behat\Behat\Context\Context;
use Behat\Step\Then;
use Behat\Step\When;
use Sylius\Behat\Element\Admin\NotificationsElementInterface;
use Sylius\Behat\NotificationType;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Tests\CylleneDigital\SyliusTarteaucitronPlugin\Behat\Page\Admin\Configuration\UpdatePage;
use Webmozart\Assert\Assert;

final readonly class ManagingTarteaucitronConfigurationContext implements Context
{
    /**
     * @param ChannelRepositoryInterface<ChannelInterface> $channelRepository
     */
    public function __construct(
        private UpdatePage $updatePage,
        private NotificationsElementInterface $notificationsElement,
        private ChannelRepositoryInterface $channelRepository,
    ) {
    }

    #[When('I go to the tarteaucitron configuration page')]
    public function iGoToTheTarteaucitronConfigurationPage(): void
    {
        $this->updatePage->open();
    }

    #[When('I go to the tarteaucitron configuration page of the :channelName channel')]
    public function iGoToTheConfigurationPageOfTheChannel(string $channelName): void
    {
        $channel = $this->channelRepository->findOneBy(['name' => $channelName]);
        Assert::isInstanceOf($channel, ChannelInterface::class);

        $this->updatePage->open(['channelCode' => $channel->getCode()]);
    }

    #[Then('I should be able to switch to the :channelName channel')]
    public function iShouldBeAbleToSwitchToTheChannel(string $channelName): void
    {
        Assert::inArray($channelName, $this->updatePage->getSwitchableChannels());
    }

    #[Then('tarteaucitron should be enabled')]
    public function tarteaucitronShouldBeEnabled(): void
    {
        Assert::true($this->updatePage->isEnabled());
    }

    #[Then('tarteaucitron should be disabled')]
    public function tarteaucitronShouldBeDisabled(): void
    {
        Assert::false($this->updatePage->isEnabled());
    }

    #[Then('I should see the tarteaucitron configuration form')]
    public function iShouldSeeTheTarteaucitronConfigurationForm(): void
    {
        Assert::true(
            $this->updatePage->hasConfigurationForm(),
            'Expected the tarteaucitron configuration form on the page.',
        );
    }

    #[Then('I should see that I am configuring the :channelName channel')]
    public function iShouldSeeThatIAmConfiguringTheChannel(string $channelName): void
    {
        Assert::contains($this->updatePage->getCurrentChannel(), $channelName);
    }

    #[When('I keep the visitor\'s choice for :days days')]
    public function iKeepTheVisitorsChoiceForDays(int $days): void
    {
        $this->updatePage->setConsentLifetime($days);
    }

    #[Then('the visitor\'s choice should be kept for :days days')]
    public function theVisitorsChoiceShouldBeKeptForDays(int $days): void
    {
        Assert::same($this->updatePage->getConsentLifetime(), (string) $days);
    }

    #[Then('I should be notified that the consent lifetime cannot exceed :max days')]
    public function iShouldBeNotifiedThatTheConsentLifetimeCannotExceedDays(int $max): void
    {
        Assert::contains($this->updatePage->getConsentLifetimeValidationMessage(), (string) $max);
    }

    #[Then('I should be warned that :count setting(s) depart(s) from the CNIL guidance')]
    public function iShouldBeWarnedThatSettingsDepartFromTheCnilGuidance(int $count): void
    {
        Assert::contains($this->updatePage->getComplianceSummary(), sprintf('%d setting', $count));
    }

    #[Then('the consent lifetime should be flagged')]
    public function theConsentLifetimeShouldBeFlagged(): void
    {
        Assert::true($this->updatePage->hasComplianceWarningOn('consent_lifetime_days'));
    }

    #[Then('no setting should depart from the CNIL guidance')]
    public function noSettingShouldDepartFromTheCnilGuidance(): void
    {
        Assert::same($this->updatePage->getComplianceSummary(), '');
    }

    #[When('I set the :key banner text to :text in the :localeCode locale')]
    public function iSetTheBannerTextInTheLocale(string $key, string $text, string $localeCode): void
    {
        $this->updatePage->setBannerText($localeCode, $key, $text);
    }

    #[Then('I should be notified that the :key banner text in the :localeCode locale cannot contain :characters')]
    public function iShouldBeNotifiedThatTheBannerTextCannotContain(string $key, string $localeCode, string $characters): void
    {
        Assert::contains($this->updatePage->getBannerTextValidationMessage($localeCode, $key), $characters);
    }

    #[Then('I should be able to enable the :name service')]
    public function iShouldBeAbleToEnableTheService(string $name): void
    {
        Assert::inArray($name, $this->updatePage->getServiceNames());
    }

    #[When('I switch on the :name service')]
    public function iSwitchOnTheService(string $name): void
    {
        $this->updatePage->toggleService($name);
    }

    #[Then('the :name service settings should be folded')]
    public function theServiceSettingsShouldBeFolded(string $name): void
    {
        Assert::false($this->updatePage->areServiceDetailsShown($name));
    }

    #[Then('the :name service settings should be shown')]
    public function theServiceSettingsShouldBeShown(string $name): void
    {
        Assert::true($this->updatePage->areServiceDetailsShown($name));
    }

    #[When('I search the :query service')]
    public function iSearchTheService(string $query): void
    {
        $this->updatePage->searchService($query);
    }

    #[Then('I should only see the :name service')]
    public function iShouldOnlySeeTheService(string $name): void
    {
        Assert::same($this->updatePage->getVisibleServiceNames(), [$name]);
    }

    #[When('I switch on the :name service with its :key set to :value')]
    public function iSwitchOnTheServiceWithItsParameterSetTo(string $name, string $key, string $value): void
    {
        $this->updatePage->setServiceEnabled($name, true);
        $this->updatePage->setServiceParameter($name, $key, $value);
    }

    #[When('I switch off the :name service')]
    public function iSwitchOffTheService(string $name): void
    {
        $this->updatePage->setServiceEnabled($name, false);
    }

    #[Then('the :name service should be switched on with its :key set to :value')]
    public function theServiceShouldBeSwitchedOnWithItsParameterSetTo(string $name, string $key, string $value): void
    {
        Assert::true($this->updatePage->isServiceEnabled($name), sprintf('The "%s" service is off.', $name));
        Assert::same($this->updatePage->getServiceParameter($name, $key), $value);
    }

    #[Then('the :name service should be switched off')]
    public function theServiceShouldBeSwitchedOff(string $name): void
    {
        Assert::false($this->updatePage->isServiceEnabled($name), sprintf('The "%s" service is on.', $name));
    }

    #[When('I disable tarteaucitron')]
    public function iDisableTarteaucitron(): void
    {
        $this->updatePage->disable();
    }

    #[When('I enable tarteaucitron')]
    public function iEnableTarteaucitron(): void
    {
        $this->updatePage->enable();
    }

    #[Then('I should be notified that the tarteaucitron configuration has been saved')]
    public function iShouldBeNotifiedThatTheTarteaucitronConfigurationHasBeenSaved(): void
    {
        Assert::true(
            $this->notificationsElement->hasNotification(
                (string) NotificationType::success(),
                'Tarteaucitron configuration has been saved.',
            ),
        );
    }
}

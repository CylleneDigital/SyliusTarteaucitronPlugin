<?php

declare(strict_types=1);

namespace CylleneDigital\SyliusTarteaucitronPlugin\Tests\Behat\Page\Admin\Configuration;

use Sylius\Behat\Page\SyliusPage;

final class UpdatePage extends SyliusPage
{
    public function getRouteName(): string
    {
        return 'cyllene_digital_sylius_tarteaucitron_admin_configuration';
    }

    public function hasConfigurationForm(): bool
    {
        return $this->hasElement('enabled');
    }

    public function getCurrentChannel(): string
    {
        return $this->getElement('channel')->getText();
    }

    public function getConsentLifetime(): string
    {
        return (string) $this->getElement('consent_lifetime')->getValue();
    }

    public function setConsentLifetime(int $days): void
    {
        $this->getElement('consent_lifetime')->setValue((string) $days);
    }

    public function getConsentLifetimeValidationMessage(): string
    {
        $feedback = $this->getElement('consent_lifetime')->getParent()->find('css', '.invalid-feedback');

        return null === $feedback ? '' : $feedback->getText();
    }

    public function getComplianceSummary(): string
    {
        return $this->hasElement('compliance_summary') ? $this->getElement('compliance_summary')->getText() : '';
    }

    public function hasComplianceWarningOn(string $field): bool
    {
        return $this->hasElement('compliance_warning', ['%field%' => $field]);
    }

    public function setBannerText(string $localeCode, string $key, string $text): void
    {
        $this->getElement('localized_field', ['%locale%' => $localeCode, '%key%' => $key])->setValue($text);
    }

    public function getBannerTextValidationMessage(string $localeCode, string $key): string
    {
        $feedback = $this->getElement('localized_field', ['%locale%' => $localeCode, '%key%' => $key])
            ->getParent()->find('css', '.invalid-feedback');

        return null === $feedback ? '' : $feedback->getText();
    }

    /** @return list<string> */
    public function getServiceNames(): array
    {
        return array_map(
            static fn ($title): string => trim($title->getText()),
            $this->getDocument()->findAll('css', '#tarteaucitron-services-accordion [data-tac-service-name]'),
        );
    }

    public function toggleService(string $name): void
    {
        $this->serviceRow($name)->find('css', '[data-tac-service-toggle]')?->click();
    }

    public function areServiceDetailsShown(string $name): bool
    {
        $details = $this->serviceRow($name)->find('css', '[data-tac-service-details]');

        return null !== $details && $details->isVisible();
    }

    public function searchService(string $query): void
    {
        $this->getDocument()->find('css', '[data-tac-service-search]')?->setValue($query);
        $this->getDriver()->evaluateScript("document.querySelector('[data-tac-service-search]').dispatchEvent(new Event('input'))");
    }

    /** @return list<string> */
    public function getVisibleServiceNames(): array
    {
        $names = [];
        foreach ($this->getDocument()->findAll('css', '#tarteaucitron-services-accordion [data-tac-service-name]') as $name) {
            if ($name->isVisible()) {
                $names[] = trim($name->getText());
            }
        }

        return $names;
    }

    private function serviceRow(string $name): \Behat\Mink\Element\NodeElement
    {
        foreach ($this->getDocument()->findAll('css', '[data-tac-service]') as $row) {
            if (trim((string) $row->find('css', '[data-tac-service-name]')?->getText()) === $name) {
                return $row;
            }
        }

        throw new \RuntimeException(sprintf('No "%s" service in the services column.', $name));
    }

    /** @return list<string> */
    public function getSwitchableChannels(): array
    {
        return array_map(
            static fn ($link): string => trim($link->getText()),
            $this->getDocument()->findAll('css', '[data-test-tarteaucitron-channel] .dropdown-item'),
        );
    }

    public function isEnabled(): bool
    {
        return $this->getElement('enabled')->isChecked();
    }

    public function enable(): void
    {
        $this->getElement('enabled')->check();
    }

    protected function getDefinedElements(): array
    {
        return array_merge(parent::getDefinedElements(), [
            'channel' => '[data-test-tarteaucitron-channel]',
            'compliance_summary' => '[data-test-tarteaucitron-compliance-summary]',
            'compliance_warning' => '[data-test-tarteaucitron-compliance-%field%]',
            'localized_field' => '#cyllene_digital_sylius_tarteaucitron_configuration_localized_options_%locale%_%key%',
            'consent_lifetime' => '#cyllene_digital_sylius_tarteaucitron_configuration_consent_lifetime_days',
            'enabled' => '[data-test-tarteaucitron-enabled]',
        ]);
    }
}

<?php

declare(strict_types=1);

namespace Tests\CylleneDigital\SyliusTarteaucitronPlugin\Behat\Page\Admin\Configuration;

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
        $value = $this->getElement('consent_lifetime')->getValue();

        return is_string($value) ? $value : '';
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
        return array_values(array_map(
            static fn ($title): string => trim($title->getText()),
            $this->getDocument()->findAll('css', '#tarteaucitron-services-accordion [data-tac-service-name]'),
        ));
    }

    public function toggleService(string $name): void
    {
        $this->serviceToggle($name)->click();
    }

    public function setServiceEnabled(string $name, bool $enabled): void
    {
        $toggle = $this->serviceToggle($name);
        $enabled ? $toggle->check() : $toggle->uncheck();
    }

    public function isServiceEnabled(string $name): bool
    {
        return $this->serviceToggle($name)->isChecked();
    }

    public function setServiceParameter(string $name, string $key, string $value): void
    {
        $this->serviceParameter($name, $key)->setValue($value);
    }

    public function getServiceParameter(string $name, string $key): string
    {
        $value = $this->serviceParameter($name, $key)->getValue();

        return is_string($value) ? $value : '';
    }

    private function serviceToggle(string $name): \Behat\Mink\Element\NodeElement
    {
        return $this->serviceRow($name)->find('css', '[data-tac-service-toggle]')
            ?? throw new \RuntimeException(sprintf('No switch for the "%s" service.', $name));
    }

    private function serviceParameter(string $name, string $key): \Behat\Mink\Element\NodeElement
    {
        return $this->serviceRow($name)->find('css', sprintf('input[name$="[%s]"]', $key))
            ?? throw new \RuntimeException(sprintf('No "%s" parameter for the "%s" service.', $key, $name));
    }

    public function areServiceDetailsShown(string $name): bool
    {
        $details = $this->serviceRow($name)->find('css', '[data-tac-service-details]');

        return null !== $details && $details->isVisible();
    }

    public function searchService(string $query): void
    {
        ($this->getDocument()->find('css', '[data-tac-service-search]') ?? throw new \RuntimeException('No service search box.'))->setValue($query);
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
        return array_values(array_map(
            static fn ($link): string => trim($link->getText()),
            $this->getDocument()->findAll('css', '[data-test-tarteaucitron-channel] .dropdown-item'),
        ));
    }

    public function isEnabled(): bool
    {
        return $this->getElement('enabled')->isChecked();
    }

    public function enable(): void
    {
        $this->getElement('enabled')->check();
    }

    public function disable(): void
    {
        $this->getElement('enabled')->uncheck();
    }

    /** @return array<mixed> the parent's elements (untyped there) plus ours */
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

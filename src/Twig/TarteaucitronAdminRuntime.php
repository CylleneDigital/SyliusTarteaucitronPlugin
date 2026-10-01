<?php

declare(strict_types=1);

namespace CylleneDigital\SyliusTarteaucitronPlugin\Twig;

use CylleneDigital\SyliusTarteaucitronPlugin\Consent\ComplianceCheck;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\ConsentAlert;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Init\InitOption;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Init\InitOptionCatalog;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Init\InitOptions;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Init\InitOptionSection;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\OptionConflicts;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\ConsentMode;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\TrackerRegistryInterface;
use CylleneDigital\SyliusTarteaucitronPlugin\Entity\TarteaucitronConfiguration;
use CylleneDigital\SyliusTarteaucitronPlugin\Entity\TarteaucitronService;
use Symfony\Component\Form\FormView;
use Twig\Extension\RuntimeExtensionInterface;

/**
 * @internal
 */
final class TarteaucitronAdminRuntime implements RuntimeExtensionInterface
{
    public function __construct(
        private readonly InitOptionCatalog $initOptionCatalog,
        private readonly TrackerRegistryInterface $trackerRegistry,
        private readonly ConsentAlert $alertResolver,
        private readonly ComplianceCheck $complianceCheck,
        private readonly OptionConflicts $optionConflicts,
    ) {
    }

    /**
     * `localized` tabs render the per-locale links and texts instead of init option cards.
     *
     * @return list<array{id: string, title: string, localized: bool, cards: list<array{title: string|null, intro: string|null, wrap: bool, options: list<InitOption>}>}>
     */
    public function initTabs(): array
    {
        $ui = static fn (string $suffix): string => 'cyllene_digital_sylius_tarteaucitron.ui.' . $suffix;

        return [
            [
                'id' => 'essential',
                'title' => $ui('tab_essential'),
                'localized' => false,
                'cards' => [$this->card(InitOptionSection::Essential)],
            ],
            [
                'id' => 'compliance',
                'title' => $ui('tab_compliance'),
                'localized' => false,
                'cards' => [
                    $this->card(InitOptionSection::Compliance, $ui('section_compliance')),
                    $this->card(InitOptionSection::ConsentMode, $ui('section_consent_mode'), $ui('consent_mode_intro'), wrap: true),
                ],
            ],
            [
                'id' => 'texts',
                'title' => $ui('tab_texts'),
                'localized' => true,
                'cards' => [],
            ],
            [
                'id' => 'display',
                'title' => $ui('tab_display'),
                'localized' => false,
                'cards' => [$this->card(InitOptionSection::Display)],
            ],
            [
                'id' => 'advanced',
                'title' => $ui('tab_advanced'),
                'localized' => false,
                'cards' => [$this->card(InitOptionSection::Advanced, intro: $ui('advanced_intro'))],
            ],
        ];
    }

    /**
     * Findings for the values the form currently shows (submitted ones included, so a warning
     * appears as soon as the admin saves a risky setting).
     *
     * @return array<string, string> field => translation key
     */
    public function complianceFindings(FormView $form): array
    {
        $lifetime = $form->children[ComplianceCheck::LIFETIME_FIELD]->vars['data'] ?? null;

        $findings = [];
        foreach ($this->complianceCheck->check(
            $this->formOptions($form),
            is_int($lifetime) ? $lifetime : TarteaucitronConfiguration::DEFAULT_CONSENT_LIFETIME_DAYS,
        ) as $finding) {
            $findings[$finding['field']] = $finding['message'];
        }

        return $findings;
    }

    /**
     * @return array<string, string> option key => translation key
     */
    public function optionConflicts(FormView $form): array
    {
        $conflicts = [];
        foreach ($this->optionConflicts->check($this->formOptions($form)) as $conflict) {
            $conflicts[$conflict['field']] = $conflict['message'];
        }

        return $conflicts;
    }

    private function formOptions(FormView $form): InitOptions
    {
        $raw = [];
        foreach ($this->initOptionCatalog->all() as $option) {
            if (isset($form->children[$option->key])) {
                $raw[$option->key] = $form->children[$option->key]->vars['data'] ?? null;
            }
        }

        return InitOptions::fromArray($raw, $this->initOptionCatalog);
    }

    /**
     * @return array{title: string|null, intro: string|null, wrap: bool, options: list<InitOption>}
     */
    private function card(InitOptionSection $section, ?string $title = null, ?string $intro = null, bool $wrap = false): array
    {
        return [
            'title' => $title,
            'intro' => $intro,
            'wrap' => $wrap,
            'options' => $this->initOptionCatalog->bySection($section),
        ];
    }

    /**
     * @param iterable<FormView> $serviceViews
     *
     * @return list<array{category: string, services: list<FormView>, open: bool, enabled: int}>
     */
    public function groupServicesByCategory(iterable $serviceViews): array
    {
        /** @var array<string, list<FormView>> $grouped */
        $grouped = [];

        foreach ($serviceViews as $serviceView) {
            $value = $serviceView->vars['value'] ?? null;
            $definition = $value instanceof TarteaucitronService ? $this->trackerRegistry->getOrNull($value->getType()) : null;
            if (null === $definition) {
                // Row left by a tracker since removed: nothing to configure (the shop skips it too).
                continue;
            }
            $grouped[$definition->getCategory()->value][] = $serviceView;
        }

        $sections = [];
        foreach ($this->trackerRegistry->sortByCategoryOrder($grouped) as $category => $services) {
            $enabled = 0;
            foreach ($services as $serviceView) {
                $value = $serviceView->vars['value'] ?? null;
                if ($value instanceof TarteaucitronService && $value->isEnabled()) {
                    ++$enabled;
                }
            }

            $sections[] = [
                'category' => $category,
                'services' => $services,
                'open' => $enabled > 0,
                'enabled' => $enabled,
            ];
        }

        $hasOpenSection = false;
        foreach ($sections as $section) {
            if ($section['open']) {
                $hasOpenSection = true;

                break;
            }
        }

        if ([] !== $sections && !$hasOpenSection) {
            $sections[0]['open'] = true;
        }

        return $sections;
    }

    /**
     * @param iterable<FormView> $serviceViews
     *
     * @return array<string, bool>
     */
    public function serviceEnabledMap(iterable $serviceViews): array
    {
        $enabled = [];
        foreach ($serviceViews as $serviceView) {
            $value = $serviceView->vars['value'] ?? null;
            if ($value instanceof TarteaucitronService) {
                $enabled[$value->getType()] = $value->isEnabled();
            }
        }

        return $enabled;
    }

    /**
     * @return list<string>
     */
    public function trackersByConsentMode(string|ConsentMode $mode): array
    {
        $consentMode = $mode instanceof ConsentMode ? $mode : ConsentMode::tryFrom($mode);
        if (null === $consentMode) {
            return [];
        }

        return array_map(
            static fn ($definition): string => $definition->getType(),
            $this->trackerRegistry->byConsentMode($consentMode),
        );
    }

    /**
     * @param array<string, bool> $serviceEnabled
     *
     * @return array{level: string, message: string}|null
     */
    public function consentAlert(string $optionKey, array $serviceEnabled): ?array
    {
        return $this->alertResolver->forOption($optionKey, $serviceEnabled);
    }

    /**
     * @param array<string, string> $parameters
     *
     * @return array{label: string, hint: string|null, badge: string|null, badgeClass: string|null, incomplete: bool}
     */
    public function serviceAdminMeta(string $type, bool $enabled = false, array $parameters = []): array
    {
        $definition = $this->trackerRegistry->getOrNull($type);
        $mode = $definition?->getConsentMode();
        $incomplete = false;
        if (null !== $definition && $enabled) {
            $incomplete = !$definition->areRequiredParametersSatisfied(true, $parameters);
        }

        return [
            'label' => $definition?->getLabel() ?? 'cyllene_digital_sylius_tarteaucitron.ui.service_' . $type,
            'hint' => $definition?->getAdminHintTranslationKey(),
            'badge' => $mode?->badgeTranslationKey(),
            'badgeClass' => $mode?->badgeCssClass(),
            'incomplete' => $incomplete,
        ];
    }
}

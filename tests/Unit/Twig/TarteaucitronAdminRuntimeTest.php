<?php

declare(strict_types=1);

namespace CylleneDigital\SyliusTarteaucitronPlugin\Tests\Unit\Twig;

use CylleneDigital\SyliusTarteaucitronPlugin\Consent\ComplianceCheck;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\ConsentAlert;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Init\InitOptionCatalog;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\OptionConflicts;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\ConsentMode;
use CylleneDigital\SyliusTarteaucitronPlugin\Entity\TarteaucitronService;
use CylleneDigital\SyliusTarteaucitronPlugin\Tests\Unit\Tracker\TrackerTestKit;
use CylleneDigital\SyliusTarteaucitronPlugin\Twig\TarteaucitronAdminRuntime;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormView;

final class TarteaucitronAdminRuntimeTest extends TestCase
{
    public function testInitTabsCoverTheWholeCatalogueInOrder(): void
    {
        $catalog = new InitOptionCatalog();
        $tabs = $this->runtime()->initTabs();

        self::assertSame(
            ['essential', 'compliance', 'texts', 'display', 'advanced'],
            array_map(static fn (array $tab): string => $tab['id'], $tabs),
        );
        self::assertTrue($tabs[1]['cards'][1]['wrap']);

        $keys = [];
        foreach ($tabs as $tab) {
            foreach ($tab['cards'] as $card) {
                foreach ($card['options'] as $option) {
                    $keys[] = $option->key;
                }
            }
        }
        self::assertSame(
            array_map(static fn ($option): string => $option->key, $catalog->all()),
            $keys,
        );
    }

    public function testComplianceFindingsReadTheValuesTheFormShows(): void
    {
        $form = new FormView();
        foreach (['high_privacy' => false, 'deny_all_cta' => true, 'consent_lifetime_days' => 365] as $name => $data) {
            $child = new FormView($form);
            $child->vars['data'] = $data;
            $form->children[$name] = $child;
        }

        self::assertSame(
            [
                'high_privacy' => 'cyllene_digital_sylius_tarteaucitron.ui.compliance_high_privacy',
                'consent_lifetime_days' => 'cyllene_digital_sylius_tarteaucitron.ui.compliance_consent_lifetime',
            ],
            $this->runtime()->complianceFindings($form),
        );
    }

    public function testOptionConflictsReadTheValuesTheFormShows(): void
    {
        $form = new FormView();
        foreach (['group_services' => true, 'show_alert_small' => false] as $name => $data) {
            $child = new FormView($form);
            $child->vars['data'] = $data;
            $form->children[$name] = $child;
        }

        self::assertSame(
            ['group_services' => 'cyllene_digital_sylius_tarteaucitron.ui.conflict_group_services_adblocker'],
            $this->runtime()->optionConflicts($form),
        );
    }

    public function testServiceSectionsCountEnabledServicesAndOpenOnlyThoseWithOne(): void
    {
        $view = static function (string $type, bool $enabled): FormView {
            $service = new TarteaucitronService();
            $service->setType($type);
            $service->setEnabled($enabled);
            $view = new FormView();
            $view->vars['value'] = $service;

            return $view;
        };

        $sections = $this->runtime()->groupServicesByCategory([
            $view('gtag', true),
            $view('matomo', true),
            $view('hotjar', false),
            $view('youtube', false),
            $view('removed_tracker', true),
        ]);

        self::assertSame(['analytic', 'video'], array_column($sections, 'category'));
        self::assertSame([2, 0], array_column($sections, 'enabled'));
        self::assertSame([true, false], array_column($sections, 'open'));
    }

    public function testServiceAdminMetaReadsCatalogueHints(): void
    {
        $meta = $this->runtime()->serviceAdminMeta('gtag');

        self::assertSame('cyllene_digital_sylius_tarteaucitron.ui.service_hint_google_native', $meta['hint']);
        self::assertSame('cyllene_digital_sylius_tarteaucitron.ui.service_badge_google_consent', $meta['badge']);
        self::assertSame('text-bg-info', $meta['badgeClass']);
        self::assertFalse($meta['incomplete']);
    }

    public function testServiceAdminMetaFlagsIncompleteEnabledTracker(): void
    {
        $meta = $this->runtime()->serviceAdminMeta('gtag', true, ['gtag_ua' => '']);

        self::assertTrue($meta['incomplete']);
    }

    public function testServiceAdminMetaCompleteWhenRequiredParameterIsSet(): void
    {
        $meta = $this->runtime()->serviceAdminMeta('gtag', true, ['gtag_ua' => 'G-1']);

        self::assertFalse($meta['incomplete']);
    }

    public function testTrackersByConsentModeAcceptsEnumAndString(): void
    {
        $runtime = $this->runtime();

        self::assertContains('gtag', $runtime->trackersByConsentMode(ConsentMode::Google));
        self::assertContains('gtag', $runtime->trackersByConsentMode('google'));
        self::assertSame([], $runtime->trackersByConsentMode('nope'));
    }

    private function runtime(): TarteaucitronAdminRuntime
    {
        $registry = TrackerTestKit::registry();

        return new TarteaucitronAdminRuntime(
            new InitOptionCatalog(),
            $registry,
            new ConsentAlert($registry),
            new ComplianceCheck(),
            new OptionConflicts(['adblocker' => true]),
        );
    }
}

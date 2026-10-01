<?php

declare(strict_types=1);

namespace Tests\CylleneDigital\SyliusTarteaucitronPlugin\Unit\Twig;

use CylleneDigital\SyliusTarteaucitronPlugin\Consent\ComplianceCheck;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\ConsentAlert;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Init\InitOptionCatalog;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\OptionConflicts;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\ConsentMode;
use CylleneDigital\SyliusTarteaucitronPlugin\Entity\TarteaucitronService;
use CylleneDigital\SyliusTarteaucitronPlugin\Twig\TarteaucitronAdminRuntime;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormView;
use Tests\CylleneDigital\SyliusTarteaucitronPlugin\Unit\Tracker\TrackerTestKit;

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

    public function testInitTabsStateOpensTheFirstTabHoldingAnErrorAndCountsFindings(): void
    {
        $form = new FormView();
        foreach (['consent_lifetime_days' => [200, true], 'cookie_name' => ['x y', false], 'privacy_url' => ['', false]] as $field => [$data, $valid]) {
            $child = new FormView($form);
            $child->vars['data'] = $data;
            $child->vars['valid'] = $valid;
            $form->children[$field] = $child;
        }
        $runtime = $this->runtime();

        $state = $runtime->initTabsState($form, $runtime->initTabs());

        self::assertSame('essential', $state['error'], 'privacy_url (essential) comes before cookie_name (advanced).');
        self::assertSame('essential', $state['active']);
        self::assertSame(1, $state['findings']['essential'], 'A consent kept 200 days departs from the CNIL guidance.');
        self::assertSame(0, $state['findings']['compliance']);
    }

    public function testInitTabsStateOpensTheFirstTabWithoutError(): void
    {
        $runtime = $this->runtime();

        $state = $runtime->initTabsState(new FormView(), $runtime->initTabs());

        self::assertNull($state['error']);
        self::assertSame('essential', $state['active']);
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

        $removed = $view('removed_tracker', true);
        $gtag = $view('gtag', true);
        $sections = $this->runtime()->groupServicesByCategory([
            $gtag,
            $view('matomo', true),
            $view('hotjar', false),
            $view('youtube', false),
            $removed,
        ]);

        self::assertSame(['analytic', 'video'], array_column($sections, 'category'));
        self::assertSame([2, 0], array_column($sections, 'enabled'));
        self::assertSame([true, false], array_column($sections, 'open'));
        self::assertTrue($removed->isRendered(), 'A removed tracker row must not reach render_rest.');
        self::assertFalse($gtag->isRendered());
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

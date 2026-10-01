<?php

declare(strict_types=1);

namespace CylleneDigital\SyliusTarteaucitronPlugin\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * @internal
 */
final class TarteaucitronExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('tarteaucitron_json', [TarteaucitronRuntime::class, 'json']),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('tarteaucitron_enabled', [TarteaucitronRuntime::class, 'isEnabled']),
            new TwigFunction('tarteaucitron_init', [TarteaucitronRuntime::class, 'init']),
            new TwigFunction('tarteaucitron_language', [TarteaucitronRuntime::class, 'language']),
            new TwigFunction('tarteaucitron_custom_text', [TarteaucitronRuntime::class, 'customText']),
            new TwigFunction('tarteaucitron_consent_lifetime_days', [TarteaucitronRuntime::class, 'consentLifetimeDays']),
            new TwigFunction('tarteaucitron_asset_version', [TarteaucitronRuntime::class, 'assetVersion']),
            new TwigFunction('tarteaucitron_script_nonce', [TarteaucitronRuntime::class, 'scriptNonce']),
            new TwigFunction('tarteaucitron_tracker_scripts', [TarteaucitronRuntime::class, 'trackerScripts']),
            new TwigFunction('tarteaucitron_is_embed', [TarteaucitronRuntime::class, 'isEmbed']),
            new TwigFunction('tarteaucitron_init_tabs', [TarteaucitronAdminRuntime::class, 'initTabs']),
            new TwigFunction('tarteaucitron_compliance_findings', [TarteaucitronAdminRuntime::class, 'complianceFindings']),
            new TwigFunction('tarteaucitron_option_conflicts', [TarteaucitronAdminRuntime::class, 'optionConflicts']),
            new TwigFunction(
                'tarteaucitron_group_services_by_category',
                [TarteaucitronAdminRuntime::class, 'groupServicesByCategory'],
            ),
            new TwigFunction(
                'tarteaucitron_service_enabled_map',
                [TarteaucitronAdminRuntime::class, 'serviceEnabledMap'],
            ),
            new TwigFunction(
                'tarteaucitron_trackers_by_consent_mode',
                [TarteaucitronAdminRuntime::class, 'trackersByConsentMode'],
            ),
            new TwigFunction('tarteaucitron_consent_alert', [TarteaucitronAdminRuntime::class, 'consentAlert']),
            new TwigFunction('tarteaucitron_service_admin_meta', [TarteaucitronAdminRuntime::class, 'serviceAdminMeta']),
        ];
    }
}

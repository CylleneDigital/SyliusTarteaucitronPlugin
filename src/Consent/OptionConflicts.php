<?php

declare(strict_types=1);

namespace CylleneDigital\SyliusTarteaucitronPlugin\Consent;

use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Init\InitOptions;

/**
 * Back-office options that tarteaucitron.js silently ignores in a given setup (library bugs), so the
 * admin is not left wondering why the shop does not change. Unlike ComplianceCheck, nothing here is
 * about the CNIL guidance.
 *
 * @internal
 */
final readonly class OptionConflicts
{
    /**
     * @param array<string, mixed> $integrationInit `integration:` options, by jsKey
     */
    public function __construct(
        private array $integrationInit = [],
    ) {
    }

    /**
     * @return list<array{field: string, message: string}>
     */
    public function check(InitOptions $options): array
    {
        $ui = static fn (string $suffix): string => 'cyllene_digital_sylius_tarteaucitron.ui.conflict_' . $suffix;

        $conflicts = [];

        // With the ad-blocker detection the library builds its panel once advertising.js has loaded,
        // after it ran the grouping, which then finds no category to group.
        if (true === $options->get('group_services') && true === ($this->integrationInit['adblocker'] ?? false)) {
            $conflicts[] = ['field' => 'group_services', 'message' => $ui('group_services_adblocker')];
        }

        // Both buttons get id="tarteaucitronManager": the library wires the first one, the icon.
        if (true === $options->get('show_alert_small') && true === $options->get('show_icon')) {
            $conflicts[] = ['field' => 'show_alert_small', 'message' => $ui('show_alert_small_icon')];
        }

        return $conflicts;
    }
}

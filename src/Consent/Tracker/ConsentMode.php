<?php

declare(strict_types=1);

namespace CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker;

enum ConsentMode: string
{
    case Google = 'google';
    case Bing = 'bing';
    case Gtm = 'gtm';

    public function badgeTranslationKey(): string
    {
        return match ($this) {
            self::Google => 'cyllene_digital_sylius_tarteaucitron.ui.service_badge_google_consent',
            self::Bing => 'cyllene_digital_sylius_tarteaucitron.ui.service_badge_bing_consent',
            self::Gtm => 'cyllene_digital_sylius_tarteaucitron.ui.service_badge_gtm',
        };
    }

    public function badgeCssClass(): string
    {
        return self::Gtm === $this ? 'text-bg-warning' : 'text-bg-info';
    }
}

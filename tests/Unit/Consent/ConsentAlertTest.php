<?php

declare(strict_types=1);

namespace CylleneDigital\SyliusTarteaucitronPlugin\Tests\Unit\Consent;

use CylleneDigital\SyliusTarteaucitronPlugin\Consent\ConsentAlert;
use CylleneDigital\SyliusTarteaucitronPlugin\Tests\Unit\Tracker\TrackerTestKit;
use PHPUnit\Framework\TestCase;

final class ConsentAlertTest extends TestCase
{
    public function testGoogleWarnsWhenOnlyGtmIsOn(): void
    {
        $alert = new ConsentAlert(TrackerTestKit::registry());

        $result = $alert->forOption('google_consent_mode', [
            'googletagmanager' => true,
            'gtag' => false,
            'googleads' => false,
        ]);

        self::assertNotNull($result);
        self::assertSame('warning', $result['level']);
        self::assertSame(
            'cyllene_digital_sylius_tarteaucitron.ui.google_consent_mode_gtm_warning',
            $result['message'],
        );
    }

    public function testGoogleInfoWhenNoRelatedServiceIsOn(): void
    {
        $alert = new ConsentAlert(TrackerTestKit::registry());

        $result = $alert->forOption('google_consent_mode', ['gtag' => false]);

        self::assertNotNull($result);
        self::assertSame('info', $result['level']);
    }

    public function testGoogleSilentWhenNativeTrackerIsOn(): void
    {
        $alert = new ConsentAlert(TrackerTestKit::registry());

        self::assertNull($alert->forOption('google_consent_mode', ['gtag' => true]));
    }

    public function testBingInfoWhenNoRelatedServiceIsOn(): void
    {
        $alert = new ConsentAlert(TrackerTestKit::registry());

        $result = $alert->forOption('bing_consent_mode', ['clarity' => false, 'bingads' => false]);

        self::assertNotNull($result);
        self::assertSame('info', $result['level']);
        self::assertSame(
            'cyllene_digital_sylius_tarteaucitron.ui.bing_consent_mode_no_service_hint',
            $result['message'],
        );
    }

    public function testUnknownOptionIsSilent(): void
    {
        $alert = new ConsentAlert(TrackerTestKit::registry());

        self::assertNull($alert->forOption('soft_consent_mode', []));
    }
}

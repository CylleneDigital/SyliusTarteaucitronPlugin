<?php

declare(strict_types=1);

namespace CylleneDigital\SyliusTarteaucitronPlugin\Consent\Init;

/**
 * Back-office grouping only: never stored, so cases can move between versions.
 *
 * @internal
 */
enum InitOptionSection: string
{
    case Essential = 'essential';
    case Compliance = 'compliance';
    case ConsentMode = 'consent_mode';
    case Display = 'display';
    case Advanced = 'advanced';
}

<?php

declare(strict_types=1);

namespace CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker;

enum TrackerCategory: string
{
    case Analytic = 'analytic';
    case Ads = 'ads';
    case Api = 'api';
    case Video = 'video';
    case Social = 'social';
    case Support = 'support';
    case Comment = 'comment';
    case Other = 'other';
}

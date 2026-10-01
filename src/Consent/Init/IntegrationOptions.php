<?php

declare(strict_types=1);

namespace CylleneDigital\SyliusTarteaucitronPlugin\Consent\Init;

/**
 * tarteaucitron.init() options that belong to the theme integration, not to a shop admin: a wrong
 * value breaks the banner or a theme link. Set in bundle configuration (`integration:`), for every
 * channel, and merged into the init() payload after the back-office options.
 *
 * @internal
 */
final class IntegrationOptions
{
    /** configuration key => tarteaucitron.init() jsKey */
    public const JS_KEYS = [
        'use_external_css' => 'useExternalCss',
        'mandatory_cta' => 'mandatoryCta',
        'use_external_js' => 'useExternalJs',
        'server_side' => 'serverSide',
        'adblocker' => 'adblocker',
        'hashtag' => 'hashtag',
        'custom_closer_id' => 'customCloserId',
    ];

    /**
     * @param array<string, mixed> $config the processed `integration:` node
     *
     * @return array<string, mixed> jsKey => value
     */
    public static function toInit(array $config): array
    {
        $init = [];
        foreach (self::JS_KEYS as $key => $jsKey) {
            $value = $config[$key] ?? null;
            // Empty means "not set": the library then applies its own default.
            if (null === $value || '' === $value) {
                continue;
            }
            $init[$jsKey] = $value;
        }

        return $init;
    }
}

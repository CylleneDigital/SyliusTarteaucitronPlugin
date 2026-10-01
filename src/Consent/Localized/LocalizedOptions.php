<?php

declare(strict_types=1);

namespace CylleneDigital\SyliusTarteaucitronPlugin\Consent\Localized;

use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Init\InitOption;

/**
 * Per-locale overrides: two links (init options) and a curated set of banner texts
 * (tarteaucitronCustomText keys). Stored as `{localeCode: {key: value}}`; an empty value keeps the
 * channel-wide link or the library text.
 *
 * @internal
 */
final class LocalizedOptions
{
    /** snake_case key => tarteaucitron.init() jsKey */
    public const LINKS = [
        'privacy_url' => 'privacyUrl',
        'readmore_link' => 'readmoreLink',
    ];

    /** snake_case key => tarteaucitron.lang key */
    public const TEXTS = [
        'middle_bar_head' => 'middleBarHead',
        'alert_big_privacy' => 'alertBigPrivacy',
        'accept_all' => 'acceptAll',
        'deny_all' => 'denyAll',
        'personalize' => 'personalize',
        'close' => 'close',
        'disclaimer' => 'disclaimer',
        'mandatory_text' => 'mandatoryText',
    ];

    public const TEXT_MAX_LENGTH = 500;

    /**
     * tarteaucitron.js concatenates its texts into HTML and into double-quoted attributes
     * (aria-label, title): these characters would break out of both.
     */
    public const FORBIDDEN_TEXT_PATTERN = '/[<>"]/';

    /**
     * Drops unknown locales shapes, unknown keys, empty values and anything unsafe, so a value
     * written straight into the database cannot reach the shop.
     *
     * @return array<string, array<string, string>>
     */
    public static function normalize(mixed $raw): array
    {
        if (!is_array($raw)) {
            return [];
        }

        $normalized = [];
        foreach ($raw as $locale => $values) {
            if (!is_string($locale) || !is_array($values)) {
                continue;
            }

            $kept = [];
            foreach ($values as $key => $value) {
                if (!is_string($key) || !is_string($value) || '' === trim($value)) {
                    continue;
                }

                $value = trim($value);
                if (isset(self::LINKS[$key]) && InitOption::isSafeLink($value)) {
                    $kept[$key] = $value;
                } elseif (isset(self::TEXTS[$key]) && self::isSafeText($value)) {
                    $kept[$key] = $value;
                }
            }

            if ([] !== $kept) {
                $normalized[$locale] = $kept;
            }
        }

        return $normalized;
    }

    public static function isSafeText(string $value): bool
    {
        return mb_strlen($value) <= self::TEXT_MAX_LENGTH && 1 !== preg_match(self::FORBIDDEN_TEXT_PATTERN, $value);
    }
}

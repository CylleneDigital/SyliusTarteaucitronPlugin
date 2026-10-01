<?php

declare(strict_types=1);

namespace CylleneDigital\SyliusTarteaucitronPlugin\Consent;

/**
 * JSON flags for inline shop output: `JSON_HEX_TAG` so `</script>` cannot break out of a script,
 * `JSON_HEX_QUOT` / `JSON_HEX_APOS` so the `tarteaucitron_json` filter also stays inert in an
 * attribute.
 *
 * @internal
 */
final class InlineJson
{
    public const FLAGS =
        \JSON_THROW_ON_ERROR
        | \JSON_UNESCAPED_SLASHES
        | \JSON_UNESCAPED_UNICODE
        | \JSON_HEX_TAG
        | \JSON_HEX_AMP
        | \JSON_HEX_QUOT
        | \JSON_HEX_APOS;

    public static function encode(mixed $value): string
    {
        return json_encode($value, self::FLAGS);
    }
}

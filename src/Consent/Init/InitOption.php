<?php

declare(strict_types=1);

namespace CylleneDigital\SyliusTarteaucitronPlugin\Consent\Init;

use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\ConsentMode;

/**
 * One tarteaucitron.init() option. `key` is snake_case (JSON, forms);
 * `jsKey` is the official camelCase (or vendor-specific) JavaScript key.
 *
 * @internal
 */
final readonly class InitOption
{
    /**
     * Official iconSrc is "URL or base64 encoded image". Raster data URIs only —
     * SVG (and other XML) data URIs can carry script.
     */
    public const ALLOWED_ICON_DATA_URI = '#^data:image/(png|jpe?g|gif|webp);base64,[A-Za-z0-9+/]+={0,2}$#iD';

    /**
     * tarteaucitron.js concatenates links and the icon source into double-quoted attributes
     * (`<img src="…">`, `href="…"`): a quote, angle bracket, backtick, backslash, whitespace or
     * control character would break out of them.
     */
    public const UNSAFE_LINK_CHARACTERS = '#[\s"\'<>`\\\\\x00-\x1f\x7f]#';

    /** Relative path to the shop (not protocol-relative), with no character listed above. */
    public const ALLOWED_RELATIVE_LINK = '#^/(?!/)[^\s"\'<>`\\\\\x00-\x1f\x7f]*$#D';

    /**
     * @param array<string, string> $choices translation key => stored value
     */
    public function __construct(
        public string $key,
        public string $jsKey,
        public InitOptionType $type,
        public mixed $default,
        public InitOptionSection $section = InitOptionSection::Essential,
        public array $choices = [],
        public bool $omitIfEmpty = false,
        public bool $url = false,
        public ?ConsentMode $relatedConsentMode = null,
        public ?string $pattern = null,
        /** A link field that also takes a raster `data:` image (the floating icon). */
        public bool $allowRasterDataUri = false,
    ) {
    }

    public function getLabel(): string
    {
        return 'cyllene_digital_sylius_tarteaucitron.ui.' . $this->key;
    }

    public function getHelp(): string
    {
        return 'cyllene_digital_sylius_tarteaucitron.ui.' . $this->key . '_help';
    }

    public function normalize(mixed $value): mixed
    {
        return match ($this->type) {
            InitOptionType::Bool => $this->normalizeBool($value),
            InitOptionType::String => $this->normalizeString($value),
            InitOptionType::Choice => $this->normalizeChoice($value),
        };
    }

    private function normalizeBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            return !in_array(strtolower($value), ['', '0', 'false', 'off', 'no'], true);
        }

        return (bool) $value;
    }

    private function normalizeString(mixed $value): string
    {
        $string = is_string($value) ? $value : '';
        if (null !== $this->pattern) {
            $valid = '' === $string ? '' === $this->default : 1 === preg_match($this->pattern, $string);

            return $valid ? $string : (is_string($this->default) ? $this->default : '');
        }

        if (!$this->url || '' === $string) {
            return $string;
        }

        if ($this->allowRasterDataUri) {
            return $this->isAllowedIconSrc($string) ? $string : '';
        }

        return self::isSafeLink($string) ? $string : '';
    }

    /**
     * Relative path or http(s) URL: tarteaucitron.js puts these values in `href` and
     * `document.location`, where `javascript:` would run.
     */
    public static function isSafeLink(string $value): bool
    {
        if (1 === preg_match(self::UNSAFE_LINK_CHARACTERS, $value)) {
            return false;
        }

        if (1 === preg_match('#^/(?!/)#', $value)) {
            return true;
        }

        $scheme = parse_url($value, \PHP_URL_SCHEME);
        if (!is_string($scheme)) {
            return false;
        }

        return in_array(strtolower($scheme), ['http', 'https'], true);
    }

    private function isAllowedIconSrc(string $value): bool
    {
        if (1 === preg_match(self::ALLOWED_ICON_DATA_URI, $value)) {
            return true;
        }

        return self::isSafeLink($value);
    }

    private function normalizeChoice(mixed $value): mixed
    {
        $string = is_string($value) ? $value : '';
        if (in_array($string, array_values($this->choices), true)) {
            return $string;
        }

        return $this->default;
    }
}

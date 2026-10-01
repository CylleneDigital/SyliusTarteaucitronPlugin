<?php

declare(strict_types=1);

namespace CylleneDigital\SyliusTarteaucitronPlugin\Consent\Localized;

/**
 * Reads the default texts of a vendored tarteaucitron.js language file, shown as placeholders so
 * the admin sees what an empty field keeps.
 *
 * @internal
 */
final class VendorLanguageTexts
{
    /** @var array<string, array<string, string>> */
    private array $cache = [];

    public function __construct(
        private readonly ?string $languageDirectory = null,
    ) {
    }

    public function get(string $language, string $key): ?string
    {
        return ($this->cache[$language] ??= $this->parse($language))[$key] ?? null;
    }

    /**
     * @return array<string, string>
     */
    private function parse(string $language): array
    {
        if (1 !== preg_match('/^[a-z]{2}(-[a-z]{2})?$/', $language)) {
            return [];
        }

        $file = ($this->languageDirectory ?? dirname(__DIR__, 3) . '/public/tarteaucitron/lang') . '/tarteaucitron.' . $language . '.js';
        $contents = is_file($file) ? file_get_contents($file) : false;
        if (false === $contents) {
            return [];
        }

        // Top-level entries are indented by one level (four spaces, or a tab in some files);
        // nested ones (category titles) by more, so they never match.
        preg_match_all('/^(?: {4}|\t)"([A-Za-z]+)"\s*:\s*"((?:[^"\\\\]|\\\\.)*)"/m', $contents, $matches, \PREG_SET_ORDER);

        $texts = [];
        foreach ($matches as [, $key, $encoded]) {
            $decoded = json_decode('"' . $encoded . '"');
            if (is_string($decoded)) {
                $texts[$key] = $decoded;
            }
        }

        return $texts;
    }
}

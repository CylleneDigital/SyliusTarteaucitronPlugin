<?php

declare(strict_types=1);

namespace CylleneDigital\SyliusTarteaucitronPlugin\Sylius\Locale;

use Sylius\Component\Locale\Context\LocaleContextInterface;
use Sylius\Component\Locale\Context\LocaleNotFoundException;

/**
 * Maps the shop locale onto a vendor `availableLanguages` code (`en` fallback).
 *
 * @internal
 */
final class TarteaucitronLanguageResolver
{
    private const FALLBACK = 'en';

    /**
     * @param list<string>          $availableLanguages the library's codes (`VendorLibrary::availableLanguages()`)
     * @param array<string, string> $localeAliases lower-case, dash-separated (`locale_aliases` bundle configuration)
     */
    public function __construct(
        private readonly LocaleContextInterface $localeContext,
        private readonly array $availableLanguages,
        private readonly array $localeAliases,
    ) {
    }

    public function resolve(): string
    {
        $locale = $this->currentLocaleCode();

        return null === $locale ? self::FALLBACK : $this->forLocale($locale);
    }

    public function currentLocaleCode(): ?string
    {
        try {
            return $this->localeContext->getLocaleCode();
        } catch (LocaleNotFoundException) {
            return null;
        }
    }

    public function forLocale(string $locale): string
    {
        $normalized = strtolower(str_replace('_', '-', $locale));
        $primary = explode('-', $normalized)[0];
        $normalized = $this->localeAliases[$normalized] ?? $normalized;
        $primary = $this->localeAliases[$primary] ?? $primary;

        if (in_array($normalized, $this->availableLanguages, true)) {
            return $normalized;
        }

        if (in_array($primary, $this->availableLanguages, true)) {
            return $primary;
        }

        return self::FALLBACK;
    }
}

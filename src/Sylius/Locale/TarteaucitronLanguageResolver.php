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

    /** @var list<string>|null */
    private ?array $availableLanguages = null;

    /**
     * @param array<string, string> $localeAliases
     */
    public function __construct(
        private readonly LocaleContextInterface $localeContext,
        private readonly ?string $libraryFile = null,
        private readonly array $localeAliases = ['nb' => 'no'],
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
        $available = $this->availableLanguages();

        if (in_array($normalized, $available, true)) {
            return $normalized;
        }

        if (in_array($primary, $available, true)) {
            return $primary;
        }

        return self::FALLBACK;
    }

    /**
     * @return list<string>
     */
    public function availableLanguages(): array
    {
        return $this->availableLanguages ??= $this->parseAvailableLanguages();
    }

    /**
     * @return list<string>
     */
    private function parseAvailableLanguages(): array
    {
        $file = $this->libraryFile();
        if (!is_file($file)) {
            return [self::FALLBACK];
        }

        $contents = file_get_contents($file);
        if (false === $contents) {
            return [self::FALLBACK];
        }

        if (1 !== preg_match('/availableLanguages="([^"]+)"/', $contents, $matches)) {
            return [self::FALLBACK];
        }

        /** @var list<string> $languages */
        $languages = explode(',', $matches[1]);

        return $languages;
    }

    private function libraryFile(): string
    {
        return $this->libraryFile ?? dirname(__DIR__, 3) . '/public/tarteaucitron/tarteaucitron.min.js';
    }
}

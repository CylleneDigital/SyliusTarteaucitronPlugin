<?php

declare(strict_types=1);

namespace CylleneDigital\SyliusTarteaucitronPlugin\Twig;

use CylleneDigital\SyliusTarteaucitronPlugin\Consent\InlineJson;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Localized\LocalizedOptions;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\TrackerRegistryInterface;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\TrackerScriptRenderer;
use CylleneDigital\SyliusTarteaucitronPlugin\Csp\ScriptNonceProviderInterface;
use CylleneDigital\SyliusTarteaucitronPlugin\Csp\StaticScriptNonceProvider;
use CylleneDigital\SyliusTarteaucitronPlugin\Sylius\Locale\TarteaucitronLanguageResolver;
use CylleneDigital\SyliusTarteaucitronPlugin\Sylius\Provider\ConsentConfigurationProviderInterface;
use Twig\Extension\RuntimeExtensionInterface;

/**
 * @internal
 */
final class TarteaucitronRuntime implements RuntimeExtensionInterface
{
    /** @var array<string, string> */
    private array $assetVersions = [];

    public function __construct(
        private readonly ConsentConfigurationProviderInterface $provider,
        private readonly TrackerRegistryInterface $trackerRegistry,
        private readonly TrackerScriptRenderer $scriptRenderer,
        private readonly TarteaucitronLanguageResolver $languageResolver,
        private readonly ScriptNonceProviderInterface $scriptNonceProvider = new StaticScriptNonceProvider(),
        private readonly ?string $assetsDirectory = null,
    ) {
    }

    /**
     * Content fingerprint of a file under public/tarteaucitron/, appended to its URL so browsers
     * drop their cached copy when a plugin update changes it (asset URLs are not versioned).
     */
    public function assetVersion(string $file): string
    {
        if (isset($this->assetVersions[$file])) {
            return $this->assetVersions[$file];
        }

        $directory = realpath($this->assetsDirectory ?? dirname(__DIR__, 2) . '/public/tarteaucitron');
        $path = realpath(($directory ?: '') . '/' . $file);
        if (false === $directory || false === $path || !str_starts_with($path, $directory . \DIRECTORY_SEPARATOR)) {
            throw new \InvalidArgumentException(sprintf('"%s" is not a file of public/tarteaucitron/.', $file));
        }

        return $this->assetVersions[$file] = substr(hash_file('xxh128', $path) ?: '', 0, 12);
    }

    public function isEnabled(): bool
    {
        return $this->provider->resolve()->enabled;
    }

    /**
     * The channel payload, with the links of the current locale when the admin set them.
     *
     * @return array<string, mixed>
     */
    public function init(): array
    {
        $init = $this->provider->resolve()->init;
        foreach (LocalizedOptions::LINKS as $key => $jsKey) {
            $link = $this->localized()[$key] ?? null;
            if (null !== $link) {
                $init[$jsKey] = $link;
            }
        }

        return $init;
    }

    /**
     * Banner texts of the current locale, keyed by tarteaucitron.lang key (tarteaucitronCustomText).
     *
     * @return array<string, string>
     */
    public function customText(): array
    {
        $texts = [];
        foreach (LocalizedOptions::TEXTS as $key => $langKey) {
            $text = $this->localized()[$key] ?? null;
            if (null !== $text) {
                $texts[$langKey] = $text;
            }
        }

        return $texts;
    }

    /** @return array<string, string> */
    private function localized(): array
    {
        $locale = $this->languageResolver->currentLocaleCode();

        return null === $locale ? [] : $this->provider->resolve()->localizedOptions[$locale] ?? [];
    }

    public function consentLifetimeDays(): int
    {
        return $this->provider->resolve()->consentLifetimeDays;
    }

    public function trackerScripts(): string
    {
        return $this->scriptRenderer->renderAll($this->provider->resolve()->services);
    }

    public function isEmbed(string $type): bool
    {
        return $this->trackerRegistry->getOrNull($type)?->isEmbed() ?? false;
    }

    public function language(): string
    {
        return $this->languageResolver->resolve();
    }

    public function scriptNonce(): ?string
    {
        $nonce = $this->scriptNonceProvider->getScriptNonce();

        return null === $nonce || '' === $nonce ? null : $nonce;
    }

    public function json(mixed $value): string
    {
        return InlineJson::encode($value);
    }
}

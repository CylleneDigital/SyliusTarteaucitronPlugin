<?php

declare(strict_types=1);

namespace CylleneDigital\SyliusTarteaucitronPlugin\Consent;

use Symfony\Component\Finder\Finder;

/**
 * Facts about the vendored tarteaucitron.js, read once when the container is compiled: the files
 * only change with a plugin update (bin/update-tarteaucitron.sh).
 *
 * @internal
 */
final class VendorLibrary
{
    public static function directory(): string
    {
        return dirname(__DIR__, 2) . '/public/tarteaucitron';
    }

    public static function version(string $directory): string
    {
        return trim((string) file_get_contents($directory . '/VERSION'));
    }

    /**
     * @return list<string> the codes `tarteaucitron.getLanguage()` accepts
     */
    public static function availableLanguages(string $directory): array
    {
        if (1 !== preg_match('/availableLanguages="([^"]+)"/', (string) file_get_contents($directory . '/tarteaucitron.min.js'), $matches)) {
            throw new \LogicException(sprintf('availableLanguages not found in %s/tarteaucitron.min.js.', $directory));
        }

        return explode(',', $matches[1]);
    }

    /**
     * @return array<string, string> path relative to the directory => first 12 hex chars of its xxh128 hash
     */
    public static function fingerprints(string $directory): array
    {
        $fingerprints = [];
        foreach ((new Finder())->files()->in($directory) as $file) {
            $fingerprints[str_replace('\\', '/', $file->getRelativePathname())] = substr(hash_file('xxh128', $file->getPathname()) ?: '', 0, 12);
        }
        ksort($fingerprints);

        return $fingerprints;
    }
}

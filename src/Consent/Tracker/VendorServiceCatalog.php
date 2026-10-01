<?php

declare(strict_types=1);

namespace CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker;

use CylleneDigital\SyliusTarteaucitronPlugin\Consent\VendorLibrary;

/**
 * Job keys of the vendored tarteaucitron.services.js, each with the tarteaucitron.user.* keys its
 * definition reads.
 *
 * @internal
 */
final class VendorServiceCatalog
{
    /** @var array<string, list<string>>|null */
    private ?array $services = null;

    public function __construct(
        private readonly ?string $servicesFile = null,
    ) {
    }

    public function has(string $type): bool
    {
        return isset($this->all()[$type]);
    }

    /** @return list<string> */
    public function userKeysOf(string $type): array
    {
        return $this->all()[$type] ?? [];
    }

    /** @return array<string, list<string>> */
    public function all(): array
    {
        return $this->services ??= $this->parse();
    }

    /** @return array<string, list<string>> */
    private function parse(): array
    {
        $file = $this->servicesFile ?? VendorLibrary::directory() . '/tarteaucitron.services.js';
        $contents = is_file($file) ? file_get_contents($file) : false;
        if (false === $contents) {
            return [];
        }

        preg_match_all('/tarteaucitron\.services\.([A-Za-z0-9_]+)\s*=\s*\{/', $contents, $matches, \PREG_OFFSET_CAPTURE);

        $services = [];
        foreach ($matches[1] as $index => [$type]) {
            $from = $matches[0][$index][1];
            $to = $matches[0][$index + 1][1] ?? strlen($contents);

            preg_match_all('/tarteaucitron\.user\.([A-Za-z0-9_]+)/', substr($contents, $from, $to - $from), $userKeys);
            $services[$type] = array_values(array_unique($userKeys[1]));
        }

        return $services;
    }
}

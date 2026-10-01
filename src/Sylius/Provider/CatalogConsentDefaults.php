<?php

declare(strict_types=1);

namespace CylleneDigital\SyliusTarteaucitronPlugin\Sylius\Provider;

use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Init\InitOptionCatalog;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Init\InitOptions;

/**
 * @internal
 */
final readonly class CatalogConsentDefaults
{
    public function __construct(
        private InitOptionCatalog $initOptionCatalog,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function defaultInitOptions(): array
    {
        return InitOptions::defaults($this->initOptionCatalog)->toArray();
    }
}

<?php

declare(strict_types=1);

namespace CylleneDigital\SyliusTarteaucitronPlugin\Consent\Init;

/**
 * @internal
 */
final readonly class InitOptionsMapper
{
    public function __construct(
        private InitOptionCatalog $catalog,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toTarteaucitronInit(InitOptions $options): array
    {
        $init = [];
        foreach ($this->catalog->all() as $option) {
            $value = $options->get($option->key);
            if ($option->omitIfEmpty && (null === $value || '' === $value)) {
                continue;
            }
            $init[$option->jsKey] = $value;
        }

        return $init;
    }
}

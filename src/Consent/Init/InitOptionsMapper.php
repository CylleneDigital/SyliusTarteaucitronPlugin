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
            $init[$option->jsKey] = InitOptionType::Choice === $option->type ? self::jsChoice($value) : $value;
        }

        return $init;
    }

    /**
     * Choices are stored as strings, but tarteaucitron.js compares `serviceDefaultState` with
     * `true` / `false` strictly: as strings, an accepted service would only load on the next page.
     */
    private static function jsChoice(mixed $value): mixed
    {
        return match ($value) {
            'true' => true,
            'false' => false,
            default => $value,
        };
    }
}

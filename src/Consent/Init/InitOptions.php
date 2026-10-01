<?php

declare(strict_types=1);

namespace CylleneDigital\SyliusTarteaucitronPlugin\Consent\Init;

/**
 * Normalized snake_case init options. Unknown keys are ignored; missing keys
 * fall back to the catalogue defaults (upgrade-safe).
 *
 * @internal
 */
final readonly class InitOptions
{
    /**
     * @param array<string, mixed> $values
     */
    private function __construct(
        private array $values,
    ) {
    }

    public static function defaults(InitOptionCatalog $catalog): self
    {
        return self::fromArray([], $catalog);
    }

    /**
     * @param array<string, mixed> $raw
     */
    public static function fromArray(array $raw, InitOptionCatalog $catalog): self
    {
        $values = [];
        foreach ($catalog->all() as $option) {
            $values[$option->key] = array_key_exists($option->key, $raw)
                ? $option->normalize($raw[$option->key])
                : $option->default;
        }

        return new self($values);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->values;
    }

    public function get(string $key): mixed
    {
        return $this->values[$key] ?? null;
    }
}

<?php

declare(strict_types=1);

namespace CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker;

/**
 * Maps a BO/DB parameter (snake_case) to a tarteaucitron.user.* key.
 */
final readonly class TrackerParameter
{
    public function __construct(
        public string $key,
        public string $userKey,
        public bool $required = true,
        public string $placeholder = '',
        public ?string $label = null,
    ) {
        // Written as is into `tarteaucitron.user.<userKey> = …;`: it must be a plain identifier.
        if (1 !== preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/D', $userKey)) {
            throw new \InvalidArgumentException(sprintf('Tracker parameter "%s": "%s" is not a valid tarteaucitron.user key.', $key, $userKey));
        }
    }

    public function getLabel(): string
    {
        return $this->label ?? 'cyllene_digital_sylius_tarteaucitron.ui.' . $this->key;
    }
}

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
    }

    public function getLabel(): string
    {
        return $this->label ?? 'cyllene_digital_sylius_tarteaucitron.ui.' . $this->key;
    }
}

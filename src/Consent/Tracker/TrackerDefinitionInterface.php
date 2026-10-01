<?php

declare(strict_types=1);

namespace CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker;

/**
 * One implementation per tarteaucitron.js service (job key).
 *
 * Built-in trackers live under {@see \CylleneDigital\SyliusTarteaucitronPlugin\Tracker}.
 * Shops add custom trackers by tagging a service with
 * `cyllene_digital_sylius_tarteaucitron.tracker`.
 *
 * @see https://tarteaucitron.io/en/free-installation-open-source/
 */
interface TrackerDefinitionInterface
{
    /** tarteaucitron job key, e.g. "gtag", "facebookpixel" */
    public function getType(): string;

    public function getCategory(): TrackerCategory;

    /** Translation key for the admin title */
    public function getLabel(): string;

    /**
     * @return list<TrackerParameter>
     */
    public function getParameters(): array;

    /**
     * True when the service needs HTML placeholders in content (YouTube, Maps…).
     * Still registers job.push when enabled; may have zero BO parameters.
     */
    public function isEmbed(): bool;

    /**
     * Whether the BO should create a row for this tracker on first edit. Keep `true`: the BO cannot
     * add a row itself, so with `false` the service can never be switched on.
     */
    public function isSeededByDefault(): bool;

    /**
     * @param array<string, string> $parameters
     */
    public function areRequiredParametersFilled(array $parameters): bool;

    public function getConsentMode(): ?ConsentMode;

    public function getAdminHintTranslationKey(): ?string;
}

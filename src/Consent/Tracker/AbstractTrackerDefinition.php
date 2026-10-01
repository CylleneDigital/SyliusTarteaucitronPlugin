<?php

declare(strict_types=1);

namespace CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker;

abstract class AbstractTrackerDefinition implements TrackerDefinitionInterface
{
    public function getLabel(): string
    {
        return 'cyllene_digital_sylius_tarteaucitron.ui.service_' . $this->getType();
    }

    public function getParameters(): array
    {
        return [];
    }

    public function isEmbed(): bool
    {
        return false;
    }

    public function isSeededByDefault(): bool
    {
        return true;
    }

    public function getConsentMode(): ?ConsentMode
    {
        return null;
    }

    public function getAdminHintTranslationKey(): ?string
    {
        return null;
    }

    public function areRequiredParametersSatisfied(bool $enabled, array $parameters): bool
    {
        if (!$enabled) {
            return false;
        }

        foreach ($this->getParameters() as $parameter) {
            if (!$parameter->required) {
                continue;
            }

            if ('' === trim($parameters[$parameter->key] ?? '')) {
                return false;
            }
        }

        return true;
    }
}

<?php

declare(strict_types=1);

namespace CylleneDigital\SyliusTarteaucitronPlugin\Form\DataMapper;

use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Init\InitOptionCatalog;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Init\InitOptions;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Localized\LocalizedOptions;
use CylleneDigital\SyliusTarteaucitronPlugin\Entity\TarteaucitronConfiguration;
use Symfony\Component\Form\DataMapperInterface;
use Traversable;

/**
 * @internal
 */
final readonly class TarteaucitronConfigurationDataMapper implements DataMapperInterface
{
    public function __construct(
        private InitOptionCatalog $initOptionCatalog,
    ) {
    }

    public function mapDataToForms(mixed $viewData, Traversable $forms): void
    {
        if (!$viewData instanceof TarteaucitronConfiguration) {
            return;
        }

        $forms = IndexedForms::of($forms);

        if (isset($forms['enabled'])) {
            $forms['enabled']->setData($viewData->isEnabled());
        }
        if (isset($forms['consent_lifetime_days'])) {
            $forms['consent_lifetime_days']->setData($viewData->getConsentLifetimeDays());
        }
        if (isset($forms['services'])) {
            $forms['services']->setData($viewData->getServices());
        }

        $options = InitOptions::fromArray($viewData->getInitOptions(), $this->initOptionCatalog);
        foreach ($this->initOptionCatalog->all() as $option) {
            if (isset($forms[$option->key])) {
                $forms[$option->key]->setData($options->get($option->key));
            }
        }
    }

    public function mapFormsToData(Traversable $forms, mixed &$viewData): void
    {
        if (!$viewData instanceof TarteaucitronConfiguration) {
            return;
        }

        $forms = IndexedForms::of($forms);

        if (isset($forms['enabled'])) {
            $viewData->setEnabled((bool) $forms['enabled']->getData());
        }
        $lifetime = isset($forms['consent_lifetime_days']) ? $forms['consent_lifetime_days']->getData() : null;
        if (is_int($lifetime)) {
            $viewData->setConsentLifetimeDays($lifetime);
        }

        if (isset($forms['localized_options'])) {
            $submitted = $forms['localized_options']->getData();
            if (is_array($submitted)) {
                // Keeps what was set for a locale since removed from the channel.
                $viewData->setLocalizedOptions(
                    array_diff_key($viewData->getLocalizedOptions(), $submitted) + LocalizedOptions::normalize($submitted),
                );
            }
        }

        $raw = [];
        foreach ($this->initOptionCatalog->all() as $option) {
            if (isset($forms[$option->key])) {
                $raw[$option->key] = $forms[$option->key]->getData();
            }
        }
        $viewData->setInitOptions(InitOptions::fromArray($raw, $this->initOptionCatalog)->toArray());
    }
}

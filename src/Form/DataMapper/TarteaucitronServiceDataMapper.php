<?php

declare(strict_types=1);

namespace CylleneDigital\SyliusTarteaucitronPlugin\Form\DataMapper;

use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\TrackerRegistry;
use CylleneDigital\SyliusTarteaucitronPlugin\Entity\TarteaucitronService;
use Symfony\Component\Form\DataMapperInterface;
use Traversable;

/**
 * @internal
 */
final readonly class TarteaucitronServiceDataMapper implements DataMapperInterface
{
    public function __construct(
        private TrackerRegistry $trackerRegistry,
    ) {
    }

    public function mapDataToForms(mixed $viewData, Traversable $forms): void
    {
        if (!$viewData instanceof TarteaucitronService) {
            return;
        }

        $forms = IndexedForms::of($forms);

        if (isset($forms['enabled'])) {
            $forms['enabled']->setData($viewData->isEnabled());
        }

        $definition = $this->trackerRegistry->getOrNull($viewData->getType());
        if (null === $definition) {
            return;
        }

        foreach ($definition->getParameters() as $parameter) {
            if (isset($forms[$parameter->key])) {
                $forms[$parameter->key]->setData($viewData->getParameter($parameter->key));
            }
        }
    }

    public function mapFormsToData(Traversable $forms, mixed &$viewData): void
    {
        if (!$viewData instanceof TarteaucitronService) {
            return;
        }

        // A tracker removed from the code or from `trackers:` is not shown in the back office: keep
        // its row untouched, so its identifiers are still there if the tracker comes back.
        $definition = $this->trackerRegistry->getOrNull($viewData->getType());
        if (null === $definition) {
            return;
        }

        $forms = IndexedForms::of($forms);

        if (isset($forms['enabled'])) {
            $viewData->setEnabled((bool) $forms['enabled']->getData());
        }

        $params = [];
        foreach ($definition->getParameters() as $parameter) {
            if (!isset($forms[$parameter->key])) {
                continue;
            }

            $data = $forms[$parameter->key]->getData();
            $params[$parameter->key] = is_string($data) ? $data : '';
        }
        $viewData->setParameters($params);
    }
}

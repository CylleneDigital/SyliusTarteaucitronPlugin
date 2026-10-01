<?php

declare(strict_types=1);

namespace CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker;

use CylleneDigital\SyliusTarteaucitronPlugin\Consent\InlineJson;

/**
 * Official install step 3: `tarteaucitron.user.* = …` then `job.push('type')`.
 *
 * @internal
 */
final class TrackerScriptRenderer
{
    public function __construct(
        private readonly TrackerRegistry $registry,
    ) {
    }

    /**
     * @param iterable<TrackerRuntimeState> $services
     */
    public function renderAll(iterable $services): string
    {
        $snippets = [];

        foreach ($services as $service) {
            $snippet = $this->render($service);
            if (null !== $snippet) {
                $snippets[] = $snippet;
            }
        }

        return implode("\n", $snippets);
    }

    public function render(TrackerRuntimeState $service): ?string
    {
        $definition = $this->registry->getOrNull($service->type);
        if (null === $definition || !$definition->areRequiredParametersFilled($service->parameters)) {
            return null;
        }

        $lines = [];
        foreach ($definition->getParameters() as $parameter) {
            $value = trim($service->getParameter($parameter->key));
            if ('' === $value) {
                continue;
            }
            $lines[] = sprintf(
                'tarteaucitron.user.%s = %s;',
                $parameter->userKey,
                InlineJson::encode($value),
            );
        }

        $lines[] = sprintf(
            '(tarteaucitron.job = tarteaucitron.job || []).push(%s);',
            InlineJson::encode($definition->getType()),
        );

        return implode("\n", $lines);
    }
}

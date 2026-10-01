<?php

declare(strict_types=1);

namespace CylleneDigital\SyliusTarteaucitronPlugin\Form\DataMapper;

use Symfony\Component\Form\FormInterface;
use Traversable;

/**
 * @internal
 */
final class IndexedForms
{
    /**
     * @param Traversable<mixed, FormInterface> $forms
     *
     * @return array<string, FormInterface>
     */
    public static function of(Traversable $forms): array
    {
        $indexed = [];
        foreach ($forms as $key => $form) {
            if (is_string($key)) {
                $indexed[$key] = $form;
            }
        }

        return $indexed;
    }
}

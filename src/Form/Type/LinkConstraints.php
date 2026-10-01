<?php

declare(strict_types=1);

namespace CylleneDigital\SyliusTarteaucitronPlugin\Form\Type;

use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Init\InitOption;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Validation of the links (and icon source) tarteaucitron.js writes into attributes. Mirrors
 * InitOption::isSafeLink(), which re-checks stored values on read.
 *
 * @internal
 */
final class LinkConstraints
{
    /**
     * @return list<Constraint>
     */
    public static function create(bool $allowRasterDataUri = false): array
    {
        return [
            new Assert\AtLeastOneOf([
                new Assert\Blank(),
                new Assert\Url(protocols: ['http', 'https'], requireTld: false),
                new Assert\Regex(pattern: InitOption::ALLOWED_RELATIVE_LINK),
                ...($allowRasterDataUri ? [new Assert\Regex(pattern: InitOption::ALLOWED_ICON_DATA_URI)] : []),
            ]),
            new Assert\Regex(pattern: InitOption::UNSAFE_LINK_CHARACTERS, match: false),
        ];
    }
}

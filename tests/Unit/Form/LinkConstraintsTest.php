<?php

declare(strict_types=1);

namespace CylleneDigital\SyliusTarteaucitronPlugin\Tests\Unit\Form;

use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Init\InitOptionCatalog;
use CylleneDigital\SyliusTarteaucitronPlugin\Form\Type\LinkConstraints;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

/**
 * tarteaucitron.js writes these values into double-quoted attributes (`<img src="…">`, `href="…"`).
 */
final class LinkConstraintsTest extends TestCase
{
    /** @return iterable<string, array{string, bool, bool}> value, icon field, expected valid */
    public static function values(): iterable
    {
        yield 'empty' => ['', false, true];
        yield 'https URL' => ['https://shop.test/privacy', false, true];
        yield 'relative path' => ['/fr/confidentialite?x=1', false, true];
        yield 'raster data URI for the icon' => ['data:image/png;base64,iVBORw0KGgo=', true, true];
        yield 'raster data URI outside the icon' => ['data:image/png;base64,iVBORw0KGgo=', false, false];
        yield 'javascript scheme' => ['javascript:alert(1)', false, false];
        yield 'protocol-relative' => ['//evil.test/x', false, false];
        yield 'svg data URI' => ['data:image/svg+xml;base64,PHN2Zz4=', true, false];
        yield 'quote after a data URI' => ['data:image/png;base64,x" onerror="alert(1)', true, false];
        yield 'quote after a relative path' => ['/img" onerror="alert(1)', true, false];
        yield 'quote in an https URL' => ['https://cdn.test/i.png"onerror="x', true, false];
    }

    #[DataProvider('values')]
    public function testFormAndReadNormalizationAgree(string $value, bool $icon, bool $valid): void
    {
        $violations = Validation::createValidator()->validate($value, LinkConstraints::create($icon));
        self::assertSame($valid, 0 === count($violations), 'form validation');

        $option = (new InitOptionCatalog())->get($icon ? 'icon_src' : 'privacy_url');
        self::assertNotNull($option);
        self::assertSame($valid ? $value : '', $option->normalize($value), 'read normalization');
    }
}

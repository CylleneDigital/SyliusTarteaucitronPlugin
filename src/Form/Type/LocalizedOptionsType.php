<?php

declare(strict_types=1);

namespace CylleneDigital\SyliusTarteaucitronPlugin\Form\Type;

use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Localized\LocalizedOptions;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Localized\VendorLanguageTexts;
use CylleneDigital\SyliusTarteaucitronPlugin\Sylius\Locale\TarteaucitronLanguageResolver;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * One block per channel locale: links overriding the channel-wide ones, and banner texts whose
 * placeholder is the library text an empty field keeps.
 *
 * @internal
 */
final class LocalizedOptionsType extends AbstractType
{
    private const LONG_TEXTS = ['alert_big_privacy', 'disclaimer', 'mandatory_text'];

    public function __construct(
        private readonly TarteaucitronLanguageResolver $languageResolver,
        private readonly VendorLanguageTexts $vendorTexts,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var array<string, string> $locales */
        $locales = $options['locales'];
        /** @var array<string, string> $defaultLinks */
        $defaultLinks = $options['default_links'];
        $ui = static fn (string $suffix): string => 'cyllene_digital_sylius_tarteaucitron.ui.' . $suffix;

        foreach ($locales as $code => $name) {
            $language = $this->languageResolver->forLocale($code);
            $locale = $builder->create($code, FormType::class, ['label' => $name]);

            foreach (array_keys(LocalizedOptions::LINKS) as $key) {
                $default = $defaultLinks[$key] ?? '';
                $locale->add($key, TextType::class, [
                    'label' => $ui('localized_' . $key),
                    'required' => false,
                    'help' => $ui('localized_link_help'),
                    'attr' => ['placeholder' => $default],
                    'constraints' => LinkConstraints::create(),
                ]);
            }

            foreach (LocalizedOptions::TEXTS as $key => $langKey) {
                $locale->add($key, in_array($key, self::LONG_TEXTS, true) ? TextareaType::class : TextType::class, [
                    'label' => $ui('localized_' . $key),
                    'required' => false,
                    'attr' => ['placeholder' => $this->vendorTexts->get($language, $langKey) ?? '', 'rows' => 2],
                    'constraints' => [
                        new Assert\Length(max: LocalizedOptions::TEXT_MAX_LENGTH),
                        new Assert\Regex(
                            pattern: LocalizedOptions::FORBIDDEN_TEXT_PATTERN,
                            match: false,
                            message: 'cyllene_digital_sylius_tarteaucitron.localized_text.forbidden_characters',
                        ),
                    ],
                ]);
            }

            $builder->add($locale);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired('locales')
            ->setAllowedTypes('locales', 'array')
            ->setDefault('default_links', [])
            ->setAllowedTypes('default_links', 'array')
        ;
    }
}

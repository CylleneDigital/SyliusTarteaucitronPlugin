<?php

declare(strict_types=1);

namespace CylleneDigital\SyliusTarteaucitronPlugin\Form\Type;

use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Init\InitOption;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Init\InitOptionCatalog;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Init\InitOptions;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Init\InitOptionType;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Localized\LocalizedOptions;
use CylleneDigital\SyliusTarteaucitronPlugin\Entity\TarteaucitronConfiguration;
use CylleneDigital\SyliusTarteaucitronPlugin\Form\DataMapper\TarteaucitronConfigurationDataMapper;
use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Component\Core\Model\ChannelInterface as CoreChannelInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @internal
 */
final class TarteaucitronConfigurationType extends AbstractType
{
    public function __construct(
        private readonly InitOptionCatalog $initOptionCatalog,
        private readonly TarteaucitronConfigurationDataMapper $dataMapper,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->setDataMapper($this->dataMapper)
            ->add('enabled', CheckboxType::class, [
                'label' => 'cyllene_digital_sylius_tarteaucitron.ui.enabled',
                'required' => false,
            ])
            ->add('consent_lifetime_days', IntegerType::class, [
                'label' => 'cyllene_digital_sylius_tarteaucitron.ui.consent_lifetime_days',
                'help' => 'cyllene_digital_sylius_tarteaucitron.ui.consent_lifetime_days_help',
                'attr' => ['min' => 1, 'max' => TarteaucitronConfiguration::MAX_CONSENT_LIFETIME_DAYS],
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Range(min: 1, max: TarteaucitronConfiguration::MAX_CONSENT_LIFETIME_DAYS),
                ],
            ])
            ->add('services', CollectionType::class, [
                'entry_type' => TarteaucitronServiceType::class,
                'allow_add' => false,
                'allow_delete' => false,
                'label' => 'cyllene_digital_sylius_tarteaucitron.ui.services',
            ])
        ;

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event): void {
            $configuration = $event->getData();
            $form = $event->getForm();
            $options = $configuration instanceof TarteaucitronConfiguration
                ? InitOptions::fromArray($configuration->getInitOptions(), $this->initOptionCatalog)
                : InitOptions::defaults($this->initOptionCatalog);

            foreach ($this->initOptionCatalog->all() as $option) {
                $this->addOptionField($form, $option, $options->get($option->key));
            }

            $channel = $configuration instanceof TarteaucitronConfiguration ? $configuration->getChannel() : null;
            $form->add('localized_options', LocalizedOptionsType::class, [
                'label' => false,
                'mapped' => false,
                'locales' => $this->channelLocales($channel),
                'default_links' => array_map(
                    static fn (string $key): string => is_string($link = $options->get($key)) ? $link : '',
                    array_combine(array_keys(LocalizedOptions::LINKS), array_keys(LocalizedOptions::LINKS)),
                ),
                'data' => $configuration instanceof TarteaucitronConfiguration
                    ? LocalizedOptions::normalize($configuration->getLocalizedOptions())
                    : [],
            ]);
        });
    }

    /**
     * @return array<string, string> locale code => name, the channel default locale first
     */
    private function channelLocales(?ChannelInterface $channel): array
    {
        if (!$channel instanceof CoreChannelInterface) {
            return [];
        }

        $default = $channel->getDefaultLocale()?->getCode();
        $locales = [];
        foreach ($channel->getLocales() as $locale) {
            $code = $locale->getCode();
            if (null !== $code) {
                $locales[$code] = $locale->getName() ?? $code;
            }
        }

        uksort($locales, static fn (string $a, string $b): int => ($b === $default) <=> ($a === $default) ?: strcmp($a, $b));

        return $locales;
    }

    private function addOptionField(FormInterface $form, InitOption $option, mixed $data): void
    {
        $type = match ($option->type) {
            InitOptionType::Bool => CheckboxType::class,
            InitOptionType::Choice => ChoiceType::class,
            InitOptionType::String => TextType::class,
        };

        $fieldOptions = [
            'label' => $option->getLabel(),
            'help' => $option->getHelp(),
            'required' => false,
            'mapped' => false,
            'data' => $data,
        ];

        if (InitOptionType::Choice === $option->type) {
            $fieldOptions['choices'] = $option->choices;
        }

        if (null !== $option->pattern) {
            $fieldOptions['constraints'] = [
                ...('' !== $option->default ? [new Assert\NotBlank()] : []),
                new Assert\Regex(pattern: $option->pattern),
            ];
        }

        if ($option->url) {
            $fieldOptions['constraints'] = LinkConstraints::create('icon_src' === $option->key);
        }

        $form->add($option->key, $type, $fieldOptions);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TarteaucitronConfiguration::class,
            'translation_domain' => 'messages',
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'cyllene_digital_sylius_tarteaucitron_configuration';
    }
}

<?php

declare(strict_types=1);

namespace CylleneDigital\SyliusTarteaucitronPlugin\Form\Type;

use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\TrackerRegistry;
use CylleneDigital\SyliusTarteaucitronPlugin\Entity\TarteaucitronService;
use CylleneDigital\SyliusTarteaucitronPlugin\Form\DataMapper\TarteaucitronServiceDataMapper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
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
final class TarteaucitronServiceType extends AbstractType
{
    /** Account ids, hosts and URLs: a longer value is a paste error, not an identifier. */
    public const PARAMETER_MAX_LENGTH = 255;

    public function __construct(
        private readonly TrackerRegistry $trackerRegistry,
        private readonly TarteaucitronServiceDataMapper $dataMapper,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->setDataMapper($this->dataMapper)
            // Rendered by hand as a switch labelled with the service name (admin template).
            ->add('enabled', CheckboxType::class, [
                'label' => false,
                'required' => false,
            ])
        ;

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event): void {
            $service = $event->getData();
            $form = $event->getForm();
            if (!$service instanceof TarteaucitronService) {
                return;
            }

            $this->addParameterFields($form, $service);
        });
    }

    private function addParameterFields(FormInterface $form, TarteaucitronService $service): void
    {
        $definition = $this->trackerRegistry->getOrNull($service->getType());
        if (null === $definition) {
            return;
        }

        foreach ($definition->getParameters() as $parameter) {
            $form->add($parameter->key, TextType::class, [
                'label' => $parameter->getLabel(),
                'required' => false,
                'constraints' => [new Assert\Length(max: self::PARAMETER_MAX_LENGTH)],
                'attr' => [
                    'placeholder' => $parameter->placeholder,
                ],
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TarteaucitronService::class,
            'translation_domain' => 'messages',
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'cyllene_digital_sylius_tarteaucitron_service';
    }
}

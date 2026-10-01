<?php

declare(strict_types=1);

namespace CylleneDigital\SyliusTarteaucitronPlugin\Controller\Admin;

use CylleneDigital\SyliusTarteaucitronPlugin\Form\Type\TarteaucitronConfigurationType;
use CylleneDigital\SyliusTarteaucitronPlugin\Repository\TarteaucitronConfigurationRepositoryInterface;
use CylleneDigital\SyliusTarteaucitronPlugin\Sylius\Admin\AdminChannelResolver;
use CylleneDigital\SyliusTarteaucitronPlugin\Sylius\Factory\TarteaucitronConfigurationFactory;
use CylleneDigital\SyliusTarteaucitronPlugin\Sylius\Synchronizer\ServiceCatalogSynchronizer;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Twig\Environment;

/**
 * @internal
 */
#[IsGranted('ROLE_ADMINISTRATION_ACCESS')]
final readonly class TarteaucitronConfigurationAction
{
    public function __construct(
        private TarteaucitronConfigurationRepositoryInterface $repository,
        private AdminChannelResolver $channelResolver,
        private TarteaucitronConfigurationFactory $factory,
        private ServiceCatalogSynchronizer $synchronizer,
        private EntityManagerInterface $entityManager,
        private FormFactoryInterface $formFactory,
        private Environment $twig,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $channels = $this->channelResolver->allOrdered();
        $channel = $this->channelResolver->fromRequest($request, $channels);

        $configuration = $this->repository->findOneByChannel($channel);
        if (null === $configuration) {
            $configuration = $this->factory->createForChannel($channel);
        } else {
            $this->synchronizer->ensureSeeded($configuration);
        }

        $form = $this->formFactory->create(TarteaucitronConfigurationType::class, $configuration);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($configuration);

            try {
                $this->entityManager->flush();
                $this->flash($request, 'success', 'cyllene_digital_sylius_tarteaucitron.ui.configuration_saved');
            } catch (UniqueConstraintViolationException) {
                // Another admin created this channel's configuration, or a tracker row added since,
                // between our read and this save: show theirs rather than a 500.
                $this->flash($request, 'error', 'cyllene_digital_sylius_tarteaucitron.ui.configuration_saved_concurrently');
            }

            return new RedirectResponse(
                $this->urlGenerator->generate('cyllene_digital_sylius_tarteaucitron_admin_configuration', [
                    'channelCode' => (string) $channel->getCode(),
                ]),
            );
        }

        return new Response(
            $this->twig->render('@CylleneDigitalSyliusTarteaucitronPlugin/admin/configuration.html.twig', [
                'form' => $form->createView(),
                'channel' => $channel,
                'channels' => $channels,
            ]),
            $form->isSubmitted() ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK,
        );
    }

    private function flash(Request $request, string $type, string $message): void
    {
        $session = $request->getSession();
        if ($session instanceof FlashBagAwareSessionInterface) {
            $session->getFlashBag()->add($type, $message);
        }
    }
}

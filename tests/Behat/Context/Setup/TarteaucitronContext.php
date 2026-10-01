<?php

declare(strict_types=1);

namespace Tests\CylleneDigital\SyliusTarteaucitronPlugin\Behat\Context\Setup;

use Behat\Behat\Context\Context;
use Behat\Step\Given;
use CylleneDigital\SyliusTarteaucitronPlugin\Entity\TarteaucitronConfiguration;
use CylleneDigital\SyliusTarteaucitronPlugin\Repository\TarteaucitronConfigurationRepositoryInterface;
use CylleneDigital\SyliusTarteaucitronPlugin\Sylius\Factory\TarteaucitronConfigurationFactory;
use Doctrine\ORM\EntityManagerInterface;
use Sylius\Behat\Service\SharedStorageInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Locale\Model\LocaleInterface;
use Sylius\Resource\Doctrine\Persistence\RepositoryInterface;

final readonly class TarteaucitronContext implements Context
{
    /**
     * @param RepositoryInterface<LocaleInterface> $localeRepository
     */
    public function __construct(
        private SharedStorageInterface $sharedStorage,
        private TarteaucitronConfigurationFactory $configurationFactory,
        private TarteaucitronConfigurationRepositoryInterface $configurationRepository,
        private EntityManagerInterface $entityManager,
        private RepositoryInterface $localeRepository,
    ) {
    }

    #[Given('the channel is also available in the :code locale')]
    public function theChannelIsAlsoAvailableInTheLocale(string $code): void
    {
        $locale = $this->localeRepository->findOneBy(['code' => $code]);
        \assert($locale instanceof LocaleInterface, sprintf('No "%s" locale: add "the store has locale" first.', $code));

        $this->channel()->addLocale($locale);
        $this->entityManager->flush();
    }

    #[Given('tarteaucitron is enabled for the current channel')]
    public function tarteaucitronIsEnabledForTheCurrentChannel(): void
    {
        $this->persistConfiguration(enabled: true);
    }

    #[Given('tarteaucitron is disabled for the current channel')]
    public function tarteaucitronIsDisabledForTheCurrentChannel(): void
    {
        $this->persistConfiguration(enabled: false);
    }

    #[Given('the :type service is enabled')]
    public function theServiceIsEnabled(string $type): void
    {
        $configuration = $this->sharedStorage->get('tarteaucitron_configuration');
        \assert($configuration instanceof TarteaucitronConfiguration);

        $service = $configuration->getServiceByType($type);
        \assert(null !== $service, sprintf('No "%s" service seeded.', $type));
        $service->setEnabled(true);

        $this->entityManager->flush();
    }

    #[Given('services are :state by default')]
    public function servicesAreByDefault(string $state): void
    {
        $configuration = $this->sharedStorage->get('tarteaucitron_configuration');
        \assert($configuration instanceof TarteaucitronConfiguration);

        $initOptions = $configuration->getInitOptions();
        $initOptions['service_default_state'] = match ($state) {
            'accepted' => 'true',
            'denied' => 'false',
            default => throw new \InvalidArgumentException(sprintf('Unknown default state "%s".', $state)),
        };
        $configuration->setInitOptions($initOptions);

        $this->entityManager->flush();
    }

    #[Given('the :key banner text is :text in the :localeCode locale')]
    public function theBannerTextIsInTheLocale(string $key, string $text, string $localeCode): void
    {
        $configuration = $this->sharedStorage->get('tarteaucitron_configuration');
        \assert($configuration instanceof TarteaucitronConfiguration);

        $localized = $configuration->getLocalizedOptions();
        $localized[$localeCode][$key] = $text;
        $configuration->setLocalizedOptions($localized);

        $this->entityManager->flush();
    }

    private function persistConfiguration(bool $enabled): void
    {
        $channel = $this->channel();
        $configuration = $this->configurationRepository->findOneByChannel($channel);
        if (null === $configuration) {
            $configuration = $this->configurationFactory->createForChannel($channel);
        }

        $configuration->setEnabled($enabled);

        $this->entityManager->persist($configuration);
        $this->entityManager->flush();

        $this->sharedStorage->set('tarteaucitron_configuration', $configuration);
    }

    private function channel(): ChannelInterface
    {
        $channel = $this->sharedStorage->get('channel');
        \assert($channel instanceof ChannelInterface);

        return $channel;
    }
}

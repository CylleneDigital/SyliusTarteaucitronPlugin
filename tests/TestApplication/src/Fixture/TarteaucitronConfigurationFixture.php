<?php

declare(strict_types=1);

namespace Tests\CylleneDigital\SyliusTarteaucitronPlugin\Fixture;

use CylleneDigital\SyliusTarteaucitronPlugin\Repository\TarteaucitronConfigurationRepositoryInterface;
use CylleneDigital\SyliusTarteaucitronPlugin\Sylius\Factory\TarteaucitronConfigurationFactory;
use Doctrine\ORM\EntityManagerInterface;
use Sylius\Bundle\FixturesBundle\Fixture\AbstractFixture;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

/**
 * Test application only: a ready-to-try tarteaucitron configuration per channel, so the dev shop
 * shows a banner right after `sylius:fixtures:load`.
 */
final class TarteaucitronConfigurationFixture extends AbstractFixture
{
    /**
     * @param ChannelRepositoryInterface<ChannelInterface> $channelRepository
     */
    public function __construct(
        private readonly ChannelRepositoryInterface $channelRepository,
        private readonly TarteaucitronConfigurationFactory $configurationFactory,
        private readonly TarteaucitronConfigurationRepositoryInterface $configurationRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function getName(): string
    {
        return 'tarteaucitron_configuration';
    }

    /**
     * @param array{channels: array<string, array{enabled: bool, services: array<string, array<string, string>>, init_options: array<string, mixed>, localized_options: array<string, array<string, string>>}>} $options
     */
    public function load(array $options): void
    {
        foreach ($options['channels'] as $code => $settings) {
            $channel = $this->channelRepository->findOneByCode($code);
            if (!$channel instanceof ChannelInterface) {
                throw new \InvalidArgumentException(sprintf('No channel "%s" to configure tarteaucitron for.', $code));
            }

            $configuration = $this->configurationRepository->findOneByChannel($channel)
                ?? $this->configurationFactory->createForChannel($channel);
            $configuration->setEnabled($settings['enabled']);
            $configuration->setInitOptions(array_replace($configuration->getInitOptions(), $settings['init_options']));
            $configuration->setLocalizedOptions($settings['localized_options']);

            foreach ($settings['services'] as $type => $parameters) {
                $service = $configuration->getServiceByType($type);
                if (null === $service) {
                    throw new \InvalidArgumentException(sprintf('No tarteaucitron service "%s".', $type));
                }
                $service->setEnabled(true);
                $service->setParameters($parameters);
            }

            $this->entityManager->persist($configuration);
        }

        $this->entityManager->flush();
    }

    protected function configureOptionsNode(ArrayNodeDefinition $optionsNode): void
    {
        $optionsNode
            ->children()
                ->arrayNode('channels')
                    ->useAttributeAsKey('code')
                    ->arrayPrototype()
                        ->children()
                            ->booleanNode('enabled')->defaultTrue()->end()
                            ->arrayNode('services')
                                ->useAttributeAsKey('type')
                                ->arrayPrototype()
                                    ->useAttributeAsKey('key')
                                    ->scalarPrototype()->end()
                                ->end()
                            ->end()
                            ->variableNode('init_options')->defaultValue([])->end()
                            ->variableNode('localized_options')->defaultValue([])->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }
}

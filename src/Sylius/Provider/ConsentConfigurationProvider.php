<?php

declare(strict_types=1);

namespace CylleneDigital\SyliusTarteaucitronPlugin\Sylius\Provider;

use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Init\InitOptionCatalog;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Init\InitOptions;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Init\InitOptionsMapper;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Localized\LocalizedOptions;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\TrackerRuntimeState;
use CylleneDigital\SyliusTarteaucitronPlugin\Entity\TarteaucitronConfiguration;
use CylleneDigital\SyliusTarteaucitronPlugin\Entity\TarteaucitronService;
use CylleneDigital\SyliusTarteaucitronPlugin\Repository\TarteaucitronConfigurationRepositoryInterface;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Channel\Context\ChannelNotFoundException;
use Sylius\Component\Channel\Model\ChannelInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * @internal
 */
final class ConsentConfigurationProvider implements ConsentConfigurationProviderInterface, ResetInterface
{
    /** @var array<int|string, ResolvedConsent> */
    private array $cache = [];

    /**
     * @param array<string, mixed> $integrationInit jsKey => value, from the `integration:` bundle configuration
     */
    public function __construct(
        private readonly TarteaucitronConfigurationRepositoryInterface $repository,
        private readonly ChannelContextInterface $channelContext,
        private readonly InitOptionCatalog $initOptionCatalog,
        private readonly InitOptionsMapper $initOptionsMapper,
        private readonly array $integrationInit = [],
    ) {
    }

    public function resolve(?ChannelInterface $channel = null): ResolvedConsent
    {
        $channel ??= $this->currentChannel();
        $cacheKey = $this->cacheKey($channel);

        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        if (null === $channel) {
            return $this->cache[$cacheKey] = $this->unconfigured();
        }

        $configuration = $this->repository->findOneByChannel($channel);
        if (null === $configuration) {
            return $this->cache[$cacheKey] = $this->unconfigured();
        }

        return $this->cache[$cacheKey] = $this->fromDatabase($configuration);
    }

    public function reset(): void
    {
        $this->cache = [];
    }

    private function fromDatabase(TarteaucitronConfiguration $configuration): ResolvedConsent
    {
        $options = InitOptions::fromArray($configuration->getInitOptions(), $this->initOptionCatalog);
        $services = [];
        foreach ($configuration->getServices() as $service) {
            $services[] = $this->toRuntimeState($service);
        }

        return new ResolvedConsent(
            $configuration->isEnabled(),
            array_merge($this->initOptionsMapper->toTarteaucitronInit($options), $this->integrationInit),
            $services,
            max(1, min($configuration->getConsentLifetimeDays(), TarteaucitronConfiguration::MAX_CONSENT_LIFETIME_DAYS)),
            LocalizedOptions::normalize($configuration->getLocalizedOptions()),
        );
    }

    private function unconfigured(): ResolvedConsent
    {
        return new ResolvedConsent(false, [], []);
    }

    private function toRuntimeState(TarteaucitronService $service): TrackerRuntimeState
    {
        // The JSON column may hold anything written outside the back office (imports, scripts).
        $parameters = array_filter($service->getParameters(), 'is_string');

        return new TrackerRuntimeState($service->getType(), $service->isEnabled(), $parameters);
    }

    private function currentChannel(): ?ChannelInterface
    {
        try {
            return $this->channelContext->getChannel();
        } catch (ChannelNotFoundException) {
            return null;
        }
    }

    private function cacheKey(?ChannelInterface $channel): string
    {
        $id = $channel?->getId();

        return is_int($id) || is_string($id) ? (string) $id : '_';
    }
}

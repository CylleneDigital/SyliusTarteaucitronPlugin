<?php

declare(strict_types=1);

namespace CylleneDigital\SyliusTarteaucitronPlugin\Repository;

use CylleneDigital\SyliusTarteaucitronPlugin\Entity\TarteaucitronConfiguration;
use Sylius\Component\Channel\Model\ChannelInterface;

/**
 * @phpstan-type ShopConsent array{
 *     enabled: bool,
 *     initOptions: array<string, mixed>,
 *     localizedOptions: array<mixed>,
 *     consentLifetimeDays: int,
 *     services: list<array{type: string, parameters: array<mixed>}>,
 * }
 *
 * @internal
 */
interface TarteaucitronConfigurationRepositoryInterface
{
    public function findOneByChannel(ChannelInterface $channel): ?TarteaucitronConfiguration;

    /**
     * Shop read: plain values and enabled services only, so no entity lands in the unit of work.
     *
     * @return ShopConsent|null
     */
    public function findShopConsentByChannel(ChannelInterface $channel): ?array;
}

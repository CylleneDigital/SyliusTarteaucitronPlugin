<?php

declare(strict_types=1);

namespace CylleneDigital\SyliusTarteaucitronPlugin\Repository;

use CylleneDigital\SyliusTarteaucitronPlugin\Entity\TarteaucitronConfiguration;
use Sylius\Component\Channel\Model\ChannelInterface;

/**
 * @internal
 */
interface TarteaucitronConfigurationRepositoryInterface
{
    public function findOneByChannel(ChannelInterface $channel): ?TarteaucitronConfiguration;
}

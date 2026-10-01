<?php

declare(strict_types=1);

namespace CylleneDigital\SyliusTarteaucitronPlugin\Sylius\Provider;

use Sylius\Component\Channel\Model\ChannelInterface;

/**
 * @internal
 */
interface ConsentConfigurationProviderInterface
{
    public function resolve(?ChannelInterface $channel = null): ResolvedConsent;
}

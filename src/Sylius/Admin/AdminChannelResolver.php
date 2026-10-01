<?php

declare(strict_types=1);

namespace CylleneDigital\SyliusTarteaucitronPlugin\Sylius\Admin;

use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Resolves the BO channel from `?channelCode=` (not shop ChannelContext).
 *
 * @internal
 */
final readonly class AdminChannelResolver
{
    /**
     * @param ChannelRepositoryInterface<ChannelInterface> $channelRepository
     */
    public function __construct(
        private ChannelRepositoryInterface $channelRepository,
    ) {
    }

    public function fromRequest(Request $request): ChannelInterface
    {
        $code = (string) $request->query->get('channelCode', '');
        if ('' !== $code) {
            $channel = $this->channelRepository->findOneBy(['code' => $code]);
            if (!$channel instanceof ChannelInterface) {
                throw new NotFoundHttpException(sprintf('Channel "%s" not found.', $code));
            }

            return $channel;
        }

        $channels = $this->allOrdered();
        if ([] === $channels) {
            throw new NotFoundHttpException('No channel is defined.');
        }

        return $channels[0];
    }

    /**
     * @return list<ChannelInterface>
     */
    public function allOrdered(): array
    {
        $ordered = [];
        foreach ($this->channelRepository->findBy([], ['id' => 'ASC']) as $channel) {
            if ($channel instanceof ChannelInterface) {
                $ordered[] = $channel;
            }
        }

        return $ordered;
    }
}

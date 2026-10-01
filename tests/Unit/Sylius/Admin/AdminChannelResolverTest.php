<?php

declare(strict_types=1);

namespace Tests\CylleneDigital\SyliusTarteaucitronPlugin\Unit\Sylius\Admin;

use CylleneDigital\SyliusTarteaucitronPlugin\Sylius\Admin\AdminChannelResolver;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class AdminChannelResolverTest extends TestCase
{
    public function testChannelsAreOrderedById(): void
    {
        $first = $this->channel('first');
        $second = $this->channel('second');
        $repository = $this->createMock(ChannelRepositoryInterface::class);
        $repository->expects(self::once())
            ->method('findBy')
            ->with([], ['id' => 'ASC'])
            ->willReturn([$first, $second]);

        self::assertSame([$first, $second], (new AdminChannelResolver($repository))->allOrdered());
    }

    public function testUsesChannelCodeQueryParameterWithoutAnotherQuery(): void
    {
        $fashion = $this->channel('fashion');
        $repository = $this->createMock(ChannelRepositoryInterface::class);
        $repository->expects(self::never())->method(self::anything());

        $request = Request::create('/admin/tarteaucitron', 'GET', ['channelCode' => 'fashion']);

        self::assertSame($fashion, (new AdminChannelResolver($repository))->fromRequest($request, [$this->channel('web'), $fashion]));
    }

    public function testFallsBackToTheFirstChannel(): void
    {
        $first = $this->channel('first');

        self::assertSame($first, $this->resolver()->fromRequest(Request::create('/admin/tarteaucitron'), [$first, $this->channel('second')]));
    }

    public function testUnknownChannelCodeThrows(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Channel "missing" not found.');

        $this->resolver()->fromRequest(Request::create('/admin/tarteaucitron', 'GET', ['channelCode' => 'missing']), [$this->channel('web')]);
    }

    public function testNoChannelThrows(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('No channel is defined.');

        $this->resolver()->fromRequest(Request::create('/admin/tarteaucitron'), []);
    }

    private function resolver(): AdminChannelResolver
    {
        return new AdminChannelResolver($this->createMock(ChannelRepositoryInterface::class));
    }

    private function channel(string $code): ChannelInterface
    {
        $channel = $this->createMock(ChannelInterface::class);
        $channel->method('getCode')->willReturn($code);

        return $channel;
    }
}

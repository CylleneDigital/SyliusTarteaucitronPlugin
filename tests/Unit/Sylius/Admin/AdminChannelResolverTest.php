<?php

declare(strict_types=1);

namespace CylleneDigital\SyliusTarteaucitronPlugin\Tests\Unit\Sylius\Admin;

use CylleneDigital\SyliusTarteaucitronPlugin\Sylius\Admin\AdminChannelResolver;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class AdminChannelResolverTest extends TestCase
{
    public function testUsesChannelCodeQueryParameter(): void
    {
        if (!interface_exists(ChannelInterface::class)) {
            self::markTestSkipped('sylius/sylius is not installed.');
        }

        $channel = $this->createMock(ChannelInterface::class);
        $repository = $this->createMock(ChannelRepositoryInterface::class);
        $repository->expects(self::once())
            ->method('findOneBy')
            ->with(['code' => 'fashion'])
            ->willReturn($channel);
        $repository->expects(self::never())->method('findBy');

        $resolver = new AdminChannelResolver($repository);
        $request = Request::create('/admin/tarteaucitron', 'GET', ['channelCode' => 'fashion']);

        self::assertSame($channel, $resolver->fromRequest($request));
    }

    public function testFallsBackToFirstChannelOrderedById(): void
    {
        if (!interface_exists(ChannelInterface::class)) {
            self::markTestSkipped('sylius/sylius is not installed.');
        }

        $first = $this->createMock(ChannelInterface::class);
        $second = $this->createMock(ChannelInterface::class);
        $repository = $this->createMock(ChannelRepositoryInterface::class);
        $repository->expects(self::once())
            ->method('findBy')
            ->with([], ['id' => 'ASC'])
            ->willReturn([$first, $second]);

        $resolver = new AdminChannelResolver($repository);

        self::assertSame($first, $resolver->fromRequest(Request::create('/admin/tarteaucitron')));
    }

    public function testUnknownChannelCodeThrows(): void
    {
        if (!interface_exists(ChannelInterface::class)) {
            self::markTestSkipped('sylius/sylius is not installed.');
        }

        $repository = $this->createMock(ChannelRepositoryInterface::class);
        $repository->method('findOneBy')->willReturn(null);

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Channel "missing" not found.');

        (new AdminChannelResolver($repository))->fromRequest(
            Request::create('/admin/tarteaucitron', 'GET', ['channelCode' => 'missing']),
        );
    }

    public function testNoChannelThrows(): void
    {
        if (!interface_exists(ChannelInterface::class)) {
            self::markTestSkipped('sylius/sylius is not installed.');
        }

        $repository = $this->createMock(ChannelRepositoryInterface::class);
        $repository->method('findBy')->willReturn([]);

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('No channel is defined.');

        (new AdminChannelResolver($repository))->fromRequest(Request::create('/admin/tarteaucitron'));
    }
}

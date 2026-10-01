<?php

declare(strict_types=1);

namespace CylleneDigital\SyliusTarteaucitronPlugin\Tests\Unit\Form\DataMapper;

use CylleneDigital\SyliusTarteaucitronPlugin\Entity\TarteaucitronService;
use CylleneDigital\SyliusTarteaucitronPlugin\Form\DataMapper\TarteaucitronServiceDataMapper;
use CylleneDigital\SyliusTarteaucitronPlugin\Tests\Unit\Tracker\TrackerTestKit;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormInterface;

final class TarteaucitronServiceDataMapperTest extends TestCase
{
    public function testMapsParametersOntoUnmappedFields(): void
    {
        $service = new TarteaucitronService();
        $service->setType('gtag');
        $service->setEnabled(true);
        $service->setParameters(['gtag_ua' => 'G-TEST']);

        $enabled = $this->createMock(FormInterface::class);
        $enabled->expects(self::once())->method('setData')->with(true);

        $type = $this->createMock(FormInterface::class);
        $type->expects(self::once())->method('setData')->with('gtag');

        $ua = $this->createMock(FormInterface::class);
        $ua->expects(self::once())->method('setData')->with('G-TEST');

        $mapper = new TarteaucitronServiceDataMapper(TrackerTestKit::registry());
        $mapper->mapDataToForms($service, new \ArrayIterator([
            'type' => $type,
            'enabled' => $enabled,
            'gtag_ua' => $ua,
        ]));
    }

    public function testMapsFormsBackToJsonParameters(): void
    {
        $service = new TarteaucitronService();
        $service->setType('gtag');

        $enabled = $this->createMock(FormInterface::class);
        $enabled->method('getData')->willReturn(true);

        $ua = $this->createMock(FormInterface::class);
        $ua->method('getData')->willReturn('G-FROM-FORM');

        $mapper = new TarteaucitronServiceDataMapper(TrackerTestKit::registry());
        $viewData = $service;
        $mapper->mapFormsToData(new \ArrayIterator([
            'enabled' => $enabled,
            'gtag_ua' => $ua,
        ]), $viewData);

        self::assertInstanceOf(TarteaucitronService::class, $viewData);
        self::assertTrue($viewData->isEnabled());
        self::assertSame(['gtag_ua' => 'G-FROM-FORM'], $viewData->getParameters());
    }

    public function testRowOfARemovedTrackerIsLeftUntouched(): void
    {
        $service = new TarteaucitronService();
        $service->setType('removed_tracker');
        $service->setEnabled(true);
        $service->setParameters(['removed_id' => 'ID-1']);

        $enabled = $this->createMock(FormInterface::class);
        $enabled->method('getData')->willReturn(null);

        $viewData = $service;
        (new TarteaucitronServiceDataMapper(TrackerTestKit::registry()))
            ->mapFormsToData(new \ArrayIterator(['enabled' => $enabled]), $viewData);

        self::assertTrue($service->isEnabled());
        self::assertSame(['removed_id' => 'ID-1'], $service->getParameters());
    }
}

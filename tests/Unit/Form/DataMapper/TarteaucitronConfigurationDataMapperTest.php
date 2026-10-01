<?php

declare(strict_types=1);

namespace CylleneDigital\SyliusTarteaucitronPlugin\Tests\Unit\Form\DataMapper;

use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Init\InitOptionCatalog;
use CylleneDigital\SyliusTarteaucitronPlugin\Entity\TarteaucitronConfiguration;
use CylleneDigital\SyliusTarteaucitronPlugin\Form\DataMapper\TarteaucitronConfigurationDataMapper;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Channel\Model\ChannelInterface;
use Symfony\Component\Form\FormInterface;

final class TarteaucitronConfigurationDataMapperTest extends TestCase
{
    public function testMapsInitOptionsOntoUnmappedFields(): void
    {
        if (!interface_exists(ChannelInterface::class)) {
            self::markTestSkipped('sylius/sylius is not installed.');
        }

        $configuration = new TarteaucitronConfiguration();
        $configuration->setEnabled(false);
        $configuration->setInitOptions(['high_privacy' => false, 'cookie_name' => 'tac']);

        $enabled = $this->createMock(FormInterface::class);
        $enabled->expects(self::once())->method('setData')->with(false);

        $highPrivacy = $this->createMock(FormInterface::class);
        $highPrivacy->expects(self::once())->method('setData')->with(false);

        $cookieName = $this->createMock(FormInterface::class);
        $cookieName->expects(self::once())->method('setData')->with('tac');

        $mapper = new TarteaucitronConfigurationDataMapper(new InitOptionCatalog());
        $mapper->mapDataToForms($configuration, new \ArrayIterator([
            'enabled' => $enabled,
            'high_privacy' => $highPrivacy,
            'cookie_name' => $cookieName,
        ]));
    }

    public function testMapsFormsBackToJsonInitOptions(): void
    {
        if (!interface_exists(ChannelInterface::class)) {
            self::markTestSkipped('sylius/sylius is not installed.');
        }

        $configuration = new TarteaucitronConfiguration();

        $enabled = $this->createMock(FormInterface::class);
        $enabled->method('getData')->willReturn(true);

        $highPrivacy = $this->createMock(FormInterface::class);
        $highPrivacy->method('getData')->willReturn(false);

        $lifetime = $this->createMock(FormInterface::class);
        $lifetime->method('getData')->willReturn(90);

        $mapper = new TarteaucitronConfigurationDataMapper(new InitOptionCatalog());
        $viewData = $configuration;
        $mapper->mapFormsToData(new \ArrayIterator([
            'enabled' => $enabled,
            'consent_lifetime_days' => $lifetime,
            'high_privacy' => $highPrivacy,
        ]), $viewData);

        self::assertInstanceOf(TarteaucitronConfiguration::class, $viewData);
        self::assertTrue($viewData->isEnabled());
        self::assertSame(90, $viewData->getConsentLifetimeDays());
        self::assertFalse($viewData->getInitOptions()['high_privacy']);
        self::assertTrue($viewData->getInitOptions()['google_consent_mode']);
        self::assertSame('tarteaucitron', $viewData->getInitOptions()['cookie_name']);
    }

    public function testMapsLocalizedOptionsAndKeepsLocalesNoLongerOnTheChannel(): void
    {
        if (!interface_exists(ChannelInterface::class)) {
            self::markTestSkipped('sylius/sylius is not installed.');
        }

        $configuration = new TarteaucitronConfiguration();
        $configuration->setLocalizedOptions([
            'de_DE' => ['accept_all' => 'Alle akzeptieren'],
            'fr_FR' => ['accept_all' => 'Ancien'],
        ]);

        $localized = $this->createMock(FormInterface::class);
        $localized->method('getData')->willReturn([
            'fr_FR' => ['accept_all' => 'J’accepte', 'deny_all' => '', 'close' => '<b>'],
            'en_US' => ['accept_all' => null],
        ]);

        $viewData = $configuration;
        (new TarteaucitronConfigurationDataMapper(new InitOptionCatalog()))
            ->mapFormsToData(new \ArrayIterator(['localized_options' => $localized]), $viewData);

        self::assertInstanceOf(TarteaucitronConfiguration::class, $viewData);
        self::assertSame(
            ['de_DE' => ['accept_all' => 'Alle akzeptieren'], 'fr_FR' => ['accept_all' => 'J’accepte']],
            $viewData->getLocalizedOptions(),
        );
    }
}

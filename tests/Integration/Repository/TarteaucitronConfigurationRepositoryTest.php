<?php

declare(strict_types=1);

namespace Tests\CylleneDigital\SyliusTarteaucitronPlugin\Integration\Repository;

use CylleneDigital\SyliusTarteaucitronPlugin\Entity\TarteaucitronConfiguration;
use CylleneDigital\SyliusTarteaucitronPlugin\Entity\TarteaucitronService;
use CylleneDigital\SyliusTarteaucitronPlugin\Repository\TarteaucitronConfigurationRepositoryInterface;
use CylleneDigital\SyliusTarteaucitronPlugin\Sylius\Factory\TarteaucitronConfigurationFactory;
use Doctrine\ORM\EntityManagerInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Currency\Model\CurrencyInterface;
use Sylius\Component\Locale\Model\LocaleInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Runs against the test database (migrated), inside a transaction rolled back after each test.
 */
final class TarteaucitronConfigurationRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    private TarteaucitronConfigurationRepositoryInterface $repository;

    protected function setUp(): void
    {
        self::bootKernel();

        $entityManager = self::getContainer()->get('doctrine.orm.entity_manager');
        \assert($entityManager instanceof EntityManagerInterface);
        $this->entityManager = $entityManager;
        $this->entityManager->beginTransaction();

        $repository = self::getContainer()->get('cyllene_digital_sylius_tarteaucitron.repository.configuration');
        \assert($repository instanceof TarteaucitronConfigurationRepositoryInterface);
        $this->repository = $repository;
    }

    protected function tearDown(): void
    {
        $this->entityManager->rollback();
        parent::tearDown();
    }

    public function testDoctrineHandsOutTheRepositoryService(): void
    {
        self::assertSame($this->repository, $this->entityManager->getRepository(TarteaucitronConfiguration::class));
    }

    public function testShopReadKeepsEnabledServicesOnlyAndManagesNoEntity(): void
    {
        $channel = $this->channel('TAC_SHOP_READ');
        $configuration = $this->configuration($channel);
        $configuration->setInitOptions(['privacy_url' => 'https://shop.test/privacy']);
        $configuration->setLocalizedOptions(['fr_FR' => ['accept_all' => 'J’accepte']]);
        $configuration->setConsentLifetimeDays(90);
        $gtag = $configuration->getServiceByType('gtag');
        self::assertInstanceOf(TarteaucitronService::class, $gtag);
        $gtag->setEnabled(true);
        $gtag->setParameters(['gtag_ua' => 'G-TEST']);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $consent = $this->repository->findShopConsentByChannel($this->reload($channel));

        self::assertNotNull($consent);
        self::assertTrue($consent['enabled']);
        self::assertSame(['privacy_url' => 'https://shop.test/privacy'], $consent['initOptions']);
        self::assertSame(['fr_FR' => ['accept_all' => 'J’accepte']], $consent['localizedOptions']);
        self::assertSame(90, $consent['consentLifetimeDays']);
        self::assertSame([['type' => 'gtag', 'parameters' => ['gtag_ua' => 'G-TEST']]], $consent['services']);
        self::assertSame([], $this->entityManager->getUnitOfWork()->getIdentityMap()[TarteaucitronConfiguration::class] ?? []);
        self::assertSame([], $this->entityManager->getUnitOfWork()->getIdentityMap()[TarteaucitronService::class] ?? []);
    }

    public function testEachChannelReadsItsOwnConfiguration(): void
    {
        $web = $this->channel('TAC_WEB');
        $mobile = $this->channel('TAC_MOBILE');
        $webConfiguration = $this->configuration($web);
        $webConfiguration->getServiceByType('youtube')?->setEnabled(true);
        $mobileConfiguration = $this->configuration($mobile);
        $mobileConfiguration->setEnabled(false);
        $mobileConfiguration->getServiceByType('gtag')?->setEnabled(true);
        $mobileConfiguration->getServiceByType('gtag')?->setParameters(['gtag_ua' => 'G-MOBILE']);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $webConsent = $this->repository->findShopConsentByChannel($this->reload($web));
        $mobileConsent = $this->repository->findShopConsentByChannel($this->reload($mobile));

        self::assertNotNull($webConsent);
        self::assertNotNull($mobileConsent);
        self::assertTrue($webConsent['enabled']);
        self::assertSame([['type' => 'youtube', 'parameters' => []]], $webConsent['services']);
        self::assertFalse($mobileConsent['enabled']);
        self::assertSame([['type' => 'gtag', 'parameters' => ['gtag_ua' => 'G-MOBILE']]], $mobileConsent['services']);
    }

    public function testShopReadReturnsTheConfigurationWhenNoServiceIsEnabled(): void
    {
        $channel = $this->channel('TAC_NO_SERVICE');
        $this->configuration($channel);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $consent = $this->repository->findShopConsentByChannel($this->reload($channel));

        self::assertNotNull($consent);
        self::assertSame([], $consent['services']);
    }

    public function testShopReadReturnsNullWithoutConfiguration(): void
    {
        $channel = $this->channel('TAC_NO_CONFIG');
        $this->entityManager->flush();

        self::assertNull($this->repository->findShopConsentByChannel($channel));
    }

    private function channel(string $code): ChannelInterface
    {
        $factory = self::getContainer()->get('sylius.factory.channel');
        \assert($factory instanceof FactoryInterface);
        $channel = $factory->createNew();
        \assert($channel instanceof ChannelInterface);
        $channel->setCode($code);
        $channel->setName($code);
        $channel->setDefaultLocale($this->findOrCreate('locale', 'en_US'));
        $channel->setBaseCurrency($this->findOrCreate('currency', 'USD'));
        $this->entityManager->persist($channel);

        return $channel;
    }

    /**
     * @param 'locale'|'currency' $resource
     *
     * @return ($resource is 'locale' ? LocaleInterface : CurrencyInterface)
     */
    private function findOrCreate(string $resource, string $code): LocaleInterface|CurrencyInterface
    {
        $repository = self::getContainer()->get(sprintf('sylius.repository.%s', $resource));
        \assert($repository instanceof RepositoryInterface);
        $existing = $repository->findOneBy(['code' => $code]);
        if ($existing instanceof LocaleInterface || $existing instanceof CurrencyInterface) {
            return $existing;
        }

        $factory = self::getContainer()->get(sprintf('sylius.factory.%s', $resource));
        \assert($factory instanceof FactoryInterface);
        $created = $factory->createNew();
        \assert($created instanceof LocaleInterface || $created instanceof CurrencyInterface);
        $created->setCode($code);
        $this->entityManager->persist($created);
        // findOneBy() above only sees flushed rows: the second channel would persist a duplicate code.
        $this->entityManager->flush();

        return $created;
    }

    private function configuration(ChannelInterface $channel): TarteaucitronConfiguration
    {
        $factory = self::getContainer()->get('cyllene_digital_sylius_tarteaucitron.factory.configuration');
        \assert($factory instanceof TarteaucitronConfigurationFactory);
        $configuration = $factory->createForChannel($channel);
        $configuration->setEnabled(true);
        $this->entityManager->persist($configuration);

        return $configuration;
    }

    private function reload(ChannelInterface $channel): ChannelInterface
    {
        $reloaded = $this->entityManager->find($channel::class, $channel->getId());
        \assert($reloaded instanceof ChannelInterface);

        return $reloaded;
    }
}

<?php

declare(strict_types=1);

namespace Tests\CylleneDigital\SyliusTarteaucitronPlugin\Integration\Controller;

use CylleneDigital\SyliusTarteaucitronPlugin\Csp\StaticScriptNonceProvider;
use CylleneDigital\SyliusTarteaucitronPlugin\Entity\TarteaucitronConfiguration;
use CylleneDigital\SyliusTarteaucitronPlugin\Entity\TarteaucitronService;
use CylleneDigital\SyliusTarteaucitronPlugin\Repository\TarteaucitronConfigurationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PreFlushEventArgs;
use Doctrine\ORM\Events;
use Sylius\Component\Core\Model\AdminUserInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Currency\Model\CurrencyInterface;
use Sylius\Component\Locale\Model\LocaleInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;

/**
 * The back-office page over HTTP, against the test database: one kernel for the whole test (no
 * reboot), so every request shares the connection whose transaction is rolled back at the end.
 */
final class TarteaucitronConfigurationActionTest extends WebTestCase
{
    private const URL = '/admin/tarteaucitron?channelCode=TAC_WEB';

    private KernelBrowser $client;

    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $this->client->disableReboot();

        $entityManager = self::getContainer()->get('doctrine.orm.entity_manager');
        \assert($entityManager instanceof EntityManagerInterface);
        $this->entityManager = $entityManager;
        $this->entityManager->beginTransaction();

        $this->createChannel();
    }

    protected function tearDown(): void
    {
        // Through the connection: a failed flush closes the entity manager.
        $this->entityManager->getConnection()->rollBack();
        parent::tearDown();
    }

    public function testAnonymousVisitorIsSentToTheLoginPage(): void
    {
        $this->client->request('GET', self::URL);

        self::assertResponseRedirects();
        self::assertStringContainsString('/admin/login', (string) $this->client->getResponse()->headers->get('Location'));
    }

    public function testAdministratorSavesTheConfiguration(): void
    {
        $this->logIn();

        $crawler = $this->client->request('GET', self::URL);
        self::assertResponseIsSuccessful();
        self::assertNull($this->configuration(), 'Opening the page does not save anything.');
        self::assertMatchesRegularExpression('/tarteaucitron-admin\.css\?v=[0-9a-f]{12}$/', (string) $crawler->filter('link[href*="tarteaucitron-admin.css"]')->attr('href'));
        self::assertMatchesRegularExpression('/tarteaucitron-admin\.js\?v=[0-9a-f]{12}$/', (string) $crawler->filter('script[src*="tarteaucitron-admin.js"]')->attr('src'));
        self::assertCount(0, $crawler->filter('script:not([src])')->reduce(static fn ($script): bool => str_contains($script->text(), 'tarteaucitron-init-tabs')), 'The page JavaScript is no longer inline.');

        $form = $crawler->filter('form[name="cyllene_digital_sylius_tarteaucitron_configuration"]')->form();
        $this->client->submit($form, [
            'cyllene_digital_sylius_tarteaucitron_configuration[enabled]' => '1',
            'cyllene_digital_sylius_tarteaucitron_configuration[services][gtag][enabled]' => '1',
            'cyllene_digital_sylius_tarteaucitron_configuration[services][gtag][gtag_ua]' => 'G-HTTP',
        ]);

        self::assertResponseRedirects('/admin/tarteaucitron?channelCode=TAC_WEB');
        $configuration = $this->configuration();
        self::assertNotNull($configuration);
        self::assertTrue($configuration->isEnabled());
        self::assertSame('G-HTTP', $configuration->getServiceByType('gtag')?->getParameter('gtag_ua'));
    }

    public function testInlineScriptCarriesTheConfiguredNonce(): void
    {
        self::getContainer()->set('cyllene_digital_sylius_tarteaucitron.csp.script_nonce_provider', new StaticScriptNonceProvider('adm1n'));
        $this->logIn();

        $this->client->request('GET', self::URL);

        self::assertSelectorExists('script[nonce="adm1n"]');
    }

    public function testInvalidSubmissionSavesNothing(): void
    {
        $this->logIn();

        $crawler = $this->client->request('GET', self::URL);
        $form = $crawler->filter('form[name="cyllene_digital_sylius_tarteaucitron_configuration"]')->form();
        $this->client->submit($form, ['cyllene_digital_sylius_tarteaucitron_configuration[consent_lifetime_days]' => '365']);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorExists('input[name="cyllene_digital_sylius_tarteaucitron_configuration[consent_lifetime_days]"].is-invalid');
        self::assertNull($this->configuration());
    }

    public function testConcurrentFirstSaveShowsAMessageInsteadOfAnError(): void
    {
        $this->logIn();
        $crawler = $this->client->request('GET', self::URL);
        $form = $crawler->filter('form[name="cyllene_digital_sylius_tarteaucitron_configuration"]')->form();

        // Another admin saves the same channel between this request's read and its flush.
        $channelId = $this->entityManager->getConnection()->fetchOne("SELECT id FROM sylius_channel WHERE code = 'TAC_WEB'");
        $this->entityManager->getEventManager()->addEventListener(Events::preFlush, new class($channelId) {
            private bool $done = false;

            public function __construct(private readonly mixed $channelId)
            {
            }

            public function preFlush(PreFlushEventArgs $event): void
            {
                if ($this->done) {
                    return;
                }
                $this->done = true;
                $event->getObjectManager()->getConnection()->insert('cyllene_tarteaucitron_configuration', [
                    'id' => 990001,
                    'channel_id' => $this->channelId,
                    'enabled' => 0,
                    'consent_lifetime_days' => 180,
                    'init_options' => '{}',
                    'localized_options' => '{}',
                ]);
            }
        });

        $this->client->submit($form);

        self::assertResponseRedirects('/admin/tarteaucitron?channelCode=TAC_WEB');
        $session = $this->client->getRequest()->getSession();
        \assert($session instanceof FlashBagAwareSessionInterface);
        self::assertSame(['cyllene_digital_sylius_tarteaucitron.ui.configuration_saved_concurrently'], $session->getFlashBag()->peek('error'));
    }

    public function testTrackersAddedSinceTheLastSaveShowUp(): void
    {
        $this->logIn();
        $this->client->request('GET', self::URL);
        $form = $this->client->getCrawler()->filter('form[name="cyllene_digital_sylius_tarteaucitron_configuration"]')->form();
        $this->client->submit($form);

        $configuration = $this->configuration();
        self::assertNotNull($configuration);
        $gtag = $configuration->getServiceByType('gtag');
        self::assertNotNull($gtag);
        $configuration->getServices()->removeElement($gtag);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $crawler = $this->client->request('GET', self::URL);

        self::assertCount(1, $crawler->filter('input[name="cyllene_digital_sylius_tarteaucitron_configuration[services][gtag][gtag_ua]"]'));
    }

    public function testRowOfATrackerRemovedFromTheCodeIsNotShown(): void
    {
        $this->logIn();
        $this->client->request('GET', self::URL);
        $this->client->submit($this->client->getCrawler()->filter('form[name="cyllene_digital_sylius_tarteaucitron_configuration"]')->form());
        $configuration = $this->configuration();
        self::assertNotNull($configuration);
        $removed = new TarteaucitronService();
        $removed->setType('acme_removed');
        $removed->setEnabled(true);
        $configuration->addService($removed);
        $this->entityManager->flush();

        $crawler = $this->client->request('GET', self::URL);

        self::assertResponseIsSuccessful();
        self::assertCount(0, $crawler->filter('[name*="[services][acme_removed]"]'));
    }

    public function testValueWrittenOutsideTheBackOfficeDoesNotBreakThePage(): void
    {
        $this->logIn();
        $this->client->request('GET', self::URL);
        $this->client->submit($this->client->getCrawler()->filter('form[name="cyllene_digital_sylius_tarteaucitron_configuration"]')->form());
        $configuration = $this->configuration();
        self::assertNotNull($configuration);
        // An import writing a number where the back office writes a string.
        $this->entityManager->getConnection()->executeStatement(
            "UPDATE cyllene_tarteaucitron_service SET parameters = '{\"gtag_ua\": 12345}' WHERE type = 'gtag' AND configuration_id = ?",
            [$configuration->getId()],
        );
        $this->entityManager->clear();

        $crawler = $this->client->request('GET', self::URL);

        self::assertResponseIsSuccessful();
        self::assertSame('', (string) $crawler->filter('input[name="cyllene_digital_sylius_tarteaucitron_configuration[services][gtag][gtag_ua]"]')->attr('value'));
    }

    private function logIn(): void
    {
        $factory = self::getContainer()->get('sylius.factory.admin_user');
        \assert($factory instanceof FactoryInterface);
        $admin = $factory->createNew();
        \assert($admin instanceof AdminUserInterface);
        $admin->setUsername('tac-admin');
        $admin->setEmail('tac-admin@example.com');
        $admin->setPassword('unused');
        $admin->setLocaleCode('en_US');
        $admin->setEnabled(true);
        $admin->addRole('ROLE_ADMINISTRATION_ACCESS');
        $this->entityManager->persist($admin);
        $this->entityManager->flush();

        $this->client->loginUser($admin, 'admin');
    }

    private function configuration(): ?TarteaucitronConfiguration
    {
        $this->entityManager->clear();
        $repository = self::getContainer()->get(TarteaucitronConfigurationRepository::class);
        \assert($repository instanceof TarteaucitronConfigurationRepository);

        $channel = self::getContainer()->get('sylius.repository.channel');
        \assert($channel instanceof RepositoryInterface);
        $webChannel = $channel->findOneBy(['code' => 'TAC_WEB']);
        \assert($webChannel instanceof ChannelInterface);

        return $repository->findOneByChannel($webChannel);
    }

    private function createChannel(): void
    {
        $factory = self::getContainer()->get('sylius.factory.channel');
        \assert($factory instanceof FactoryInterface);
        $channel = $factory->createNew();
        \assert($channel instanceof ChannelInterface);
        $channel->setCode('TAC_WEB');
        $channel->setName('TAC_WEB');
        $locale = $this->findOrCreate('locale', 'en_US');
        \assert($locale instanceof LocaleInterface);
        $channel->addLocale($locale);
        $channel->setDefaultLocale($locale);
        $currency = $this->findOrCreate('currency', 'USD');
        \assert($currency instanceof CurrencyInterface);
        $channel->setBaseCurrency($currency);
        $this->entityManager->persist($channel);
        $this->entityManager->flush();
    }

    private function findOrCreate(string $resource, string $code): object
    {
        $repository = self::getContainer()->get(sprintf('sylius.repository.%s', $resource));
        \assert($repository instanceof RepositoryInterface);
        $existing = $repository->findOneBy(['code' => $code]);
        if (null !== $existing) {
            return $existing;
        }

        $factory = self::getContainer()->get(sprintf('sylius.factory.%s', $resource));
        \assert($factory instanceof FactoryInterface);
        $created = $factory->createNew();
        \assert($created instanceof LocaleInterface || $created instanceof CurrencyInterface);
        $created->setCode($code);
        $this->entityManager->persist($created);

        return $created;
    }
}

<?php

declare(strict_types=1);

namespace Tests\CylleneDigital\SyliusTarteaucitronPlugin\Integration\Form;

use CylleneDigital\SyliusTarteaucitronPlugin\Entity\TarteaucitronConfiguration;
use CylleneDigital\SyliusTarteaucitronPlugin\Entity\TarteaucitronService;
use CylleneDigital\SyliusTarteaucitronPlugin\Form\Type\TarteaucitronConfigurationType;
use CylleneDigital\SyliusTarteaucitronPlugin\Sylius\Factory\TarteaucitronConfigurationFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use Sylius\Component\Core\Model\Channel;
use Sylius\Component\Locale\Model\Locale;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;

/**
 * The real form, built by the container: submission into the entity and the constraints the back
 * office relies on (CNIL warnings never block saving, invalid values do).
 */
final class TarteaucitronConfigurationTypeTest extends KernelTestCase
{
    public function testValidSubmissionUpdatesTheConfiguration(): void
    {
        [$form, $configuration] = $this->submit([
            'enabled' => '1',
            'consent_lifetime_days' => '200',
            'privacy_url' => 'https://shop.test/privacy',
            'services' => ['gtag' => ['enabled' => '1', 'gtag_ua' => 'G-ABC']],
            'localized_options' => ['en_US' => ['accept_all' => 'OK for me']],
        ]);

        self::assertTrue($form->isValid(), (string) $form->getErrors(true));
        self::assertTrue($configuration->isEnabled());
        self::assertSame(200, $configuration->getConsentLifetimeDays());
        self::assertSame('https://shop.test/privacy', $configuration->getInitOptions()['privacy_url']);
        $gtag = $configuration->getServiceByType('gtag');
        self::assertNotNull($gtag);
        self::assertTrue($gtag->isEnabled());
        self::assertSame('G-ABC', $gtag->getParameter('gtag_ua'));
        self::assertSame('OK for me', $configuration->getLocalizedOptions()['en_US']['accept_all'] ?? null);
    }

    public function testStoredValuesFillTheForm(): void
    {
        [$form] = $this->submit([], static function (TarteaucitronConfiguration $configuration): void {
            $configuration->setInitOptions(['privacy_url' => 'https://shop.test/stored']);
            $configuration->setLocalizedOptions(['en_US' => ['accept_all' => 'Stored text']]);
            $configuration->getServiceByType('gtag')?->setParameters(['gtag_ua' => 'G-STORED']);
        }, submit: false);

        self::assertSame('https://shop.test/stored', $form->get('privacy_url')->getData());
        self::assertSame('Stored text', $form->get('localized_options')->get('en_US')->get('accept_all')->getData());
        self::assertSame('G-STORED', $form->get('services')->get('gtag')->get('gtag_ua')->getData());
    }

    public function testTextsOfALocaleNoLongerOnTheChannelAreKept(): void
    {
        [$form, $configuration] = $this->submit(
            ['localized_options' => ['en_US' => ['accept_all' => 'OK']]],
            static function (TarteaucitronConfiguration $configuration): void {
                $configuration->setLocalizedOptions(['fr_FR' => ['accept_all' => 'J’accepte']]);
            },
        );

        self::assertTrue($form->isValid(), (string) $form->getErrors(true));
        self::assertSame('J’accepte', $configuration->getLocalizedOptions()['fr_FR']['accept_all'] ?? null);
        self::assertSame('OK', $configuration->getLocalizedOptions()['en_US']['accept_all'] ?? null);
    }

    public function testRowOfATrackerRemovedFromTheCodeIsLeftUntouched(): void
    {
        [$form, $configuration] = $this->submit(['enabled' => '1'], static function (TarteaucitronConfiguration $configuration): void {
            $removed = new TarteaucitronService();
            $removed->setType('acme_removed');
            $removed->setEnabled(true);
            $removed->setParameters(['acme_id' => 'KEEP-ME']);
            $configuration->addService($removed);
        });

        self::assertTrue($form->isValid(), (string) $form->getErrors(true));
        $removed = $configuration->getServiceByType('acme_removed');
        self::assertNotNull($removed);
        self::assertTrue($removed->isEnabled());
        self::assertSame(['acme_id' => 'KEEP-ME'], $removed->getParameters());
    }

    public function testChoiceListsOfferNoEmptyEntry(): void
    {
        [$form] = $this->submit([], submit: false);
        $view = $form->createView();

        foreach (['orientation', 'service_default_state', 'icon_position', 'body_position'] as $field) {
            self::assertNull($view[$field]->vars['placeholder'], sprintf('"%s" offers an empty entry.', $field));
        }
    }

    public function testCnilGuidanceDoesNotBlockSaving(): void
    {
        [$form] = $this->submit(['high_privacy' => '', 'service_default_state' => 'true', 'consent_lifetime_days' => '364']);

        self::assertTrue($form->isValid(), (string) $form->getErrors(true));
    }

    /**
     * @return iterable<string, array{list<string>, array<string, mixed>}>
     */
    public static function invalidSubmissions(): iterable
    {
        yield 'lifetime of 0 days' => [['consent_lifetime_days'], ['consent_lifetime_days' => '0']];
        yield 'lifetime over the CNIL year' => [['consent_lifetime_days'], ['consent_lifetime_days' => '365']];
        yield 'cookie name with a space' => [['cookie_name'], ['cookie_name' => 'my cookie']];
        yield 'javascript: privacy link' => [['privacy_url'], ['privacy_url' => 'javascript:alert(1)']];
        yield 'protocol-relative privacy link' => [['privacy_url'], ['privacy_url' => '//evil.test/privacy']];
        yield 'SVG data URI icon' => [['icon_src'], ['icon_src' => 'data:image/svg+xml;base64,PHN2Zz4=']];
        yield 'markup in a banner text' => [['localized_options', 'en_US', 'accept_all'], ['localized_options' => ['en_US' => ['accept_all' => '<b>OK</b>']]]];
        yield 'service identifier too long' => [['services', 'gtag', 'gtag_ua'], ['services' => ['gtag' => ['gtag_ua' => str_repeat('G', 256)]]]];
        yield 'javascript: link for a locale' => [['localized_options', 'en_US', 'privacy_url'], ['localized_options' => ['en_US' => ['privacy_url' => 'javascript:alert(1)']]]];
    }

    /**
     * @param list<string>         $path
     * @param array<string, mixed> $data
     */
    #[DataProvider('invalidSubmissions')]
    public function testInvalidValueIsRejectedOnItsField(array $path, array $data): void
    {
        [$form] = $this->submit($data);

        self::assertFalse($form->isValid());
        $field = $form;
        foreach ($path as $name) {
            $field = $field->get($name);
        }
        self::assertGreaterThan(0, count($field->getErrors()), sprintf('No error on "%s".', implode('.', $path)));
    }

    /**
     * @param array<string, mixed>                              $data
     * @param (\Closure(TarteaucitronConfiguration): void)|null $prepare runs on the configuration before the form is built
     *
     * @return array{FormInterface<TarteaucitronConfiguration>, TarteaucitronConfiguration}
     */
    private function submit(array $data, ?\Closure $prepare = null, bool $submit = true): array
    {
        self::bootKernel();
        $container = self::getContainer();

        $locale = new Locale();
        $locale->setCode('en_US');
        $channel = new Channel();
        $channel->setCode('WEB');
        $channel->addLocale($locale);
        $channel->setDefaultLocale($locale);

        $factory = $container->get('cyllene_digital_sylius_tarteaucitron.factory.configuration');
        \assert($factory instanceof TarteaucitronConfigurationFactory);
        $configuration = $factory->createForChannel($channel);
        if (null !== $prepare) {
            $prepare($configuration);
        }

        $formFactory = $container->get('form.factory');
        \assert($formFactory instanceof FormFactoryInterface);
        /** @var FormInterface<TarteaucitronConfiguration> $form */
        $form = $formFactory->create(TarteaucitronConfigurationType::class, $configuration, ['csrf_protection' => false]);
        if ($submit) {
            $form->submit($data, false);
        }

        return [$form, $configuration];
    }
}

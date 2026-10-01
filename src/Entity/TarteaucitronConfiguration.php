<?php

declare(strict_types=1);

namespace CylleneDigital\SyliusTarteaucitronPlugin\Entity;

use CylleneDigital\SyliusTarteaucitronPlugin\Repository\TarteaucitronConfigurationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Sylius\Component\Channel\Model\ChannelAwareInterface;
use Sylius\Component\Channel\Model\ChannelInterface;

/**
 * @internal
 */
#[ORM\Entity(repositoryClass: TarteaucitronConfigurationRepository::class)]
#[ORM\Table(name: 'cyllene_tarteaucitron_configuration')]
#[ORM\UniqueConstraint(name: 'uniq_tac_configuration_channel', columns: ['channel_id'])]
class TarteaucitronConfiguration implements ChannelAwareInterface
{
    /** CNIL guidance: keep the visitor's choice about 6 months before asking again. */
    public const DEFAULT_CONSENT_LIFETIME_DAYS = 180;

    /** tarteaucitron.js silently ignores tarteaucitronForceExpire from 365 days on (and keeps 1 year). */
    public const MAX_CONSENT_LIFETIME_DAYS = 364;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: ChannelInterface::class)]
    #[ORM\JoinColumn(name: 'channel_id', nullable: false, onDelete: 'CASCADE')]
    private ?ChannelInterface $channel = null;

    #[ORM\Column]
    private bool $enabled = false;

    #[ORM\Column(name: 'consent_lifetime_days', type: 'smallint')]
    private int $consentLifetimeDays = self::DEFAULT_CONSENT_LIFETIME_DAYS;

    /**
     * Snake_case tarteaucitron.init() options. Missing keys are filled from the catalogue at read time.
     *
     * @var array<string, mixed>
     */
    #[ORM\Column(name: 'init_options', type: 'json')]
    private array $initOptions = [];

    /**
     * Per-locale links and banner texts, `{localeCode: {key: value}}` (see LocalizedOptions).
     *
     * @var array<string, array<string, string>>
     */
    #[ORM\Column(name: 'localized_options', type: 'json')]
    private array $localizedOptions = [];

    /** @var Collection<string, TarteaucitronService> */
    #[ORM\OneToMany(
        targetEntity: TarteaucitronService::class,
        mappedBy: 'configuration',
        cascade: ['persist', 'remove'],
        orphanRemoval: true,
        indexBy: 'type',
    )]
    private Collection $services;

    public function __construct()
    {
        /** @var ArrayCollection<string, TarteaucitronService> $services */
        $services = new ArrayCollection();
        $this->services = $services;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getChannel(): ?ChannelInterface
    {
        return $this->channel;
    }

    public function setChannel(?ChannelInterface $channel): void
    {
        $this->channel = $channel;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }

    public function getConsentLifetimeDays(): int
    {
        return $this->consentLifetimeDays;
    }

    public function setConsentLifetimeDays(int $consentLifetimeDays): void
    {
        $this->consentLifetimeDays = $consentLifetimeDays;
    }

    /** @return array<string, mixed> */
    public function getInitOptions(): array
    {
        return $this->initOptions;
    }

    /** @param array<string, mixed> $initOptions */
    public function setInitOptions(array $initOptions): void
    {
        $this->initOptions = $initOptions;
    }

    /** @return array<string, array<string, string>> */
    public function getLocalizedOptions(): array
    {
        return $this->localizedOptions;
    }

    /** @param array<string, array<string, string>> $localizedOptions */
    public function setLocalizedOptions(array $localizedOptions): void
    {
        $this->localizedOptions = $localizedOptions;
    }

    /** @return Collection<string, TarteaucitronService> */
    public function getServices(): Collection
    {
        return $this->services;
    }

    public function addService(TarteaucitronService $service): void
    {
        if (!$this->services->contains($service)) {
            $this->services->set($service->getType(), $service);
            $service->setConfiguration($this);
        }
    }

    public function getServiceByType(string $type): ?TarteaucitronService
    {
        $service = $this->services->get($type);

        return $service instanceof TarteaucitronService ? $service : null;
    }
}

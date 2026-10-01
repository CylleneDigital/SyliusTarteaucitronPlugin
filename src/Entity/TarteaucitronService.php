<?php

declare(strict_types=1);

namespace CylleneDigital\SyliusTarteaucitronPlugin\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @internal
 */
#[ORM\Entity]
#[ORM\Table(name: 'cyllene_tarteaucitron_service')]
// Not redundant with uniq_tac_service_type: the ORM schema tool adds the foreign key index before the
// unique constraint, so it would come back under a generated name. Naming it keeps the migration stable.
#[ORM\Index(name: 'IDX_TAC_SERVICE_CONFIGURATION', columns: ['configuration_id'])]
#[ORM\UniqueConstraint(name: 'uniq_tac_service_type', columns: ['configuration_id', 'type'])]
class TarteaucitronService
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: TarteaucitronConfiguration::class, inversedBy: 'services')]
    #[ORM\JoinColumn(name: 'configuration_id', nullable: false, onDelete: 'CASCADE')]
    private ?TarteaucitronConfiguration $configuration = null;

    #[ORM\Column(length: 50)]
    private string $type = '';

    #[ORM\Column]
    private bool $enabled = false;

    /** @var array<mixed> what the back office writes is array<string, string>; imports may write anything */
    #[ORM\Column(type: 'json')]
    private array $parameters = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getConfiguration(): ?TarteaucitronConfiguration
    {
        return $this->configuration;
    }

    public function setConfiguration(?TarteaucitronConfiguration $configuration): void
    {
        $this->configuration = $configuration;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }

    /**
     * Non-string keys and values (written outside the back office) are dropped, as the shop does:
     * the back-office page must still open to fix them.
     *
     * @return array<string, string>
     */
    public function getParameters(): array
    {
        return array_filter(
            $this->parameters,
            static fn (mixed $value, int|string $key): bool => is_string($key) && is_string($value),
            \ARRAY_FILTER_USE_BOTH,
        );
    }

    /** @param array<string, string> $parameters */
    public function setParameters(array $parameters): void
    {
        $this->parameters = $parameters;
    }

    public function getParameter(string $key): string
    {
        return $this->getParameters()[$key] ?? '';
    }
}

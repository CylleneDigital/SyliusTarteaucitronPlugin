<?php

declare(strict_types=1);

namespace CylleneDigital\SyliusTarteaucitronPlugin\Repository;

use CylleneDigital\SyliusTarteaucitronPlugin\Entity\TarteaucitronConfiguration;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Sylius\Component\Channel\Model\ChannelInterface;

/**
 * @extends ServiceEntityRepository<TarteaucitronConfiguration>
 *
 * @internal
 */
final class TarteaucitronConfigurationRepository extends ServiceEntityRepository implements TarteaucitronConfigurationRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TarteaucitronConfiguration::class);
    }

    public function findOneByChannel(ChannelInterface $channel): ?TarteaucitronConfiguration
    {
        $configuration = $this->createQueryBuilder('configuration')
            ->leftJoin('configuration.services', 'service')
            ->addSelect('service')
            ->andWhere('configuration.channel = :channel')
            ->setParameter('channel', $channel)
            ->getQuery()
            ->getOneOrNullResult();

        return $configuration instanceof TarteaucitronConfiguration ? $configuration : null;
    }

    public function findShopConsentByChannel(ChannelInterface $channel): ?array
    {
        /** @var list<array<string, mixed>> $rows */
        $rows = $this->createQueryBuilder('configuration')
            ->select(
                'configuration.enabled',
                'configuration.initOptions',
                'configuration.localizedOptions',
                'configuration.consentLifetimeDays',
                'service.type',
                'service.parameters',
            )
            ->leftJoin('configuration.services', 'service', 'WITH', 'service.enabled = true')
            ->andWhere('configuration.channel = :channel')
            ->setParameter('channel', $channel)
            ->getQuery()
            ->getArrayResult();

        if ([] === $rows) {
            return null;
        }

        $services = [];
        foreach ($rows as $row) {
            if (is_string($row['type'] ?? null)) {
                $services[] = ['type' => $row['type'], 'parameters' => (array) ($row['parameters'] ?? [])];
            }
        }

        /** @var array<string, mixed> $initOptions JSON object keys */
        $initOptions = (array) ($rows[0]['initOptions'] ?? []);

        return [
            'enabled' => (bool) $rows[0]['enabled'],
            'initOptions' => $initOptions,
            'localizedOptions' => (array) ($rows[0]['localizedOptions'] ?? []),
            'consentLifetimeDays' => is_numeric($rows[0]['consentLifetimeDays']) ? (int) $rows[0]['consentLifetimeDays'] : TarteaucitronConfiguration::DEFAULT_CONSENT_LIFETIME_DAYS,
            'services' => $services,
        ];
    }
}

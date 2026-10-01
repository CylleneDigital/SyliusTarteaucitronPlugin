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
}

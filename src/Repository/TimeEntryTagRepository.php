<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\TimeEntry;
use App\Entity\TimeEntryTag;
use App\Traits\FindOrExceptionTrait;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method TimeEntryTag|null find($id, $lockMode = null, $lockVersion = null)
 * @method TimeEntryTag findOrException($id, $lockMode = null, $lockVersion = null)
 * @method TimeEntryTag|null findOneBy(array $criteria, array $orderBy = null)
 * @method TimeEntryTag findOneByOrException(array $criteria, array $orderBy = null)
 * @method TimeEntryTag[]    findAll()
 * @method TimeEntryTag[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TimeEntryTagRepository extends ServiceEntityRepository
{
    use FindOrExceptionTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TimeEntryTag::class);
    }

    public function createDefaultQueryBuilder(): QueryBuilder
    {
        return $this->createQueryBuilder('time_entry_tag');
    }

    /**
     * Finds the TimeEntryTags for a TimeEntry with the tags fetched.
     *
     * @param TimeEntry $timeEntry
     * @return TimeEntryTag[]
     */
    public function findForTimeEntry(TimeEntry $timeEntry): array
    {
        return $this->createDefaultQueryBuilder()
                    ->addSelect('tag')
                    ->join('time_entry_tag.tag', 'tag')
                    ->andWhere('time_entry_tag.timeEntry = :timeEntry')
                    ->setParameter('timeEntry', $timeEntry)
                    ->getQuery()
                    ->getResult()
        ;
    }
}

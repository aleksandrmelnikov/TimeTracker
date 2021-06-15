<?php

namespace App\Repository;

use App\Entity\Statistic;
use App\Entity\User;
use App\Traits\FindOrExceptionTrait;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Statistic|null find($id, $lockMode = null, $lockVersion = null)
 * @method Statistic|null findOneBy(array $criteria, array $orderBy = null)
 * @method Statistic[]    findAll()
 * @method Statistic[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @method Statistic|null findOrException($id, $lockMode = null, $lockVersion = null)
 * @method Statistic findOneByOrException(array $criteria, array $orderBy = null)
 */
class StatisticRepository extends ServiceEntityRepository
{
    use FindOrExceptionTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Statistic::class);
    }

    public function createDefaultQueryBuilder(): QueryBuilder
    {
        return $this->createQueryBuilder('statistic');
    }

    public function findWithUser(User $user): QueryBuilder
    {
        return $this->createDefaultQueryBuilder()
                    ->andWhere('statistic.assignedTo = :user')
                    ->setParameter('user', $user)
        ;
    }

    public function findWithUserNameQueryBuilder(User $user, string $name): QueryBuilder
    {
        return $this->findWithUser($user)
                    ->andWhere('statistic.name = :name')
                    ->setParameter('name', $name)
        ;
    }

    public function findWithUserName(User $user, string $name): Statistic|null
    {
        return $this->findWithUserNameQueryBuilder($user, $name)
                    ->getQuery()
                    ->getOneOrNullResult()
        ;
    }
}

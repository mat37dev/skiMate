<?php

namespace App\Repository;

use App\Entity\Users;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Users>
 */
class UsersRepository extends ServiceEntityRepository
{
    private EntityManagerInterface $entityManager;

    public function __construct(ManagerRegistry $registry, EntityManagerInterface $entityManager)
    {
        parent::__construct($registry, Users::class);
        $this->entityManager = $entityManager;
    }

    public function save(Users $user): void
    {
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }

    public function searchUsers(?string $search, ?string $role): array
    {
        $qb = $this->createQueryBuilder('u');
        if ($role) {
            $qb->leftJoin('u.roles', 'r')
                ->andWhere('r.name = :role')
                ->setParameter('role', $role);
        }
        if ($search) {
            $searchLike = '%'.$search.'%';
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like('u.email', ':search'),
                    $qb->expr()->like(
                        "CONCAT(u.firstname, ' ', u.lastname)",
                        ':search'
                    ),
                    $qb->expr()->like(
                        "CONCAT(u.lastname, ' ', u.firstname)",
                        ':search'
                    )
                )
            )->setParameter('search', $searchLike);
        }
        $qb->distinct();
        return $qb->getQuery()->getResult();
    }
}

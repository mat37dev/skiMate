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
        // Créez un QueryBuilder sur l'entité Users
        $qb = $this->createQueryBuilder('u');

        // Joindre le rôle si un filtre sur role est demandé
        if ($role) {
            // On fait un JOIN sur la relation ManyToMany "roles"
            $qb->leftJoin('u.roles', 'r')
                ->andWhere('r.name = :role')
                ->setParameter('role', $role);
        }

        // Gérer le champ de recherche
        if ($search) {
            $searchLike = '%'.$search.'%';
            $qb->andWhere(
                $qb->expr()->orX(
                // email
                    $qb->expr()->like('u.email', ':search'),
                    // firstname + lastname dans un sens
                    $qb->expr()->like(
                        "CONCAT(u.firstname, ' ', u.lastname)",
                        ':search'
                    ),
                    // lastname + firstname dans l'autre sens
                    $qb->expr()->like(
                        "CONCAT(u.lastname, ' ', u.firstname)",
                        ':search'
                    )
                )
            )->setParameter('search', $searchLike);
        }
        $qb->distinct();

        // Exécuter la requête
        return $qb->getQuery()->getResult();
    }
}

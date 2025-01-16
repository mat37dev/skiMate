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
            // Exemple : vous voulez qu'un "search" de "Crosnier Mathieu"
            // puisse matcher "firstname=Mathieu lastname=Crosnier" ou l'inverse.
            //
            // 1) On split la chaîne sur les espaces
            // 2) On essaie de matcher chacun (ou faire un "LIKE" global si plus simple).
            //
            // Méthode A : faire un seul LIKE global pour la concat "firstname + lastname".
            // Méthode B : plus complexe, on split et essaie de trouver "Mathieu" + "Crosnier" dans n'importe quel champ.

            // Méthode A :
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

        // Optionnel : si vous voulez un DISTINCT pour éviter des doublons
        // (car le JOIN roles peut retourner plusieurs lignes pour un même user)
        $qb->distinct();

        // Exécuter la requête
        return $qb->getQuery()->getResult();
    }





    //    /**
    //     * @return Users[] Returns an array of Users objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('u.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Users
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}

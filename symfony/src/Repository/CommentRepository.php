<?php

namespace App\Repository;

use App\Entity\Comment;
use DateTimeInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Comment>
 */
class CommentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Comment::class);
    }

    public function save(Comment $comment): void
    {
        $this->getEntityManager()->persist($comment);
        $this->getEntityManager()->flush();
    }

    public function remove(Comment $comment): void
    {
        $this->getEntityManager()->remove($comment);
        $this->getEntityManager()->flush();

    }

    /**
     * Recherche les commentaires en fonction d'une chaîne (dans title, description, prénom ou nom de l'utilisateur),
     * d'une date (createdAt) et/ou d'une valeur pour isValide.
     *
     * @param string|null $recherche
     * @param DateTimeInterface|null $date
     * @param bool|null $isValide
     * @return Comment[]
     */
    public function searchComments(?string $recherche, ?DateTimeInterface $date, ?bool $isValide): array
    {
        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.user', 'u');

        if ($recherche) {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like('LOWER(c.title)', ':search'),
                    $qb->expr()->like('LOWER(c.description)', ':search'),
                    $qb->expr()->like('LOWER(u.firstname)', ':search'),
                    $qb->expr()->like('LOWER(u.lastname)', ':search')
                )
            )
                ->setParameter('search', '%' . mb_strtolower($recherche) . '%');
        }

        if ($date) {
            // Définir l'intervalle du jour (de 00:00:00 à 23:59:59)
            $start = (clone $date)->setTime(0, 0, 0);
            $end   = (clone $date)->setTime(23, 59, 59);
            $qb->andWhere('c.createdAt BETWEEN :start AND :end')
                ->setParameter('start', $start, Types::DATETIME_IMMUTABLE)
                ->setParameter('end', $end, Types::DATETIME_IMMUTABLE);
        }

        if ($isValide !== null) {
            $qb->andWhere('c.isValide = :isValide')
                ->setParameter('isValide', $isValide);
        }

        return $qb->getQuery()->getResult();
    }

    //    /**
    //     * @return Comment[] Returns an array of Comment objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('c.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Comment
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}

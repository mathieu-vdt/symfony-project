<?php

namespace App\Repository;

use App\Entity\Recipe;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\User;

/**
 * @extends ServiceEntityRepository<Recipe>
 */
class RecipeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Recipe::class);
    }

    public function searchByQuery(string $q): array
    {
        return $this->createQueryBuilder('r')
            ->leftJoin('r.category', 'c')->addSelect('c')
            ->where('LOWER(r.title) LIKE :q OR LOWER(r.description) LIKE :q')
            ->setParameter('q', '%'.mb_strtolower($q).'%')
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function searchAll(?string $q): array
    {
        $qb = $this->createQueryBuilder('r')
            ->leftJoin('r.category', 'c')->addSelect('c')
            ->orderBy('r.createdAt', 'DESC');

        if ($q) {
            $qb->andWhere('r.title LIKE :q OR r.description LIKE :q OR c.name LIKE :q')
            ->setParameter('q', '%'.$q.'%');
        }

        return $qb->getQuery()->getResult();
    }

    public function searchByAuthor(User $author, ?string $q): array
    {
        $qb = $this->createQueryBuilder('r')
            ->leftJoin('r.category', 'c')->addSelect('c')
            ->andWhere('r.author = :author')
            ->setParameter('author', $author)
            ->orderBy('r.createdAt', 'DESC');

        if ($q) {
            $qb->andWhere('r.title LIKE :q OR r.description LIKE :q OR c.name LIKE :q')
            ->setParameter('q', '%'.$q.'%');
        }

        return $qb->getQuery()->getResult();
    }



//    /**
//     * @return Recipe[] Returns an array of Recipe objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('r')
//            ->andWhere('r.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('r.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Recipe
//    {
//        return $this->createQueryBuilder('r')
//            ->andWhere('r.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}

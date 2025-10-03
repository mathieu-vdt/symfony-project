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

    public function findByCategory(string $category, ?string $q = null): array
    {
        $qb = $this->createQueryBuilder('r')
            ->leftJoin('r.category', 'c')->addSelect('c')
            ->orderBy('r.createdAt', 'DESC');

        // Map category filters to actual criteria
        switch ($category) {
            case 'quick':
                $qb->andWhere('r.prepTime <= :maxTime')
                   ->setParameter('maxTime', 30); // 30 minutes or less
                break;
            case 'vegetarian':
                $qb->andWhere('LOWER(c.name) LIKE :vegCategory OR LOWER(r.title) LIKE :vegTitle OR LOWER(r.description) LIKE :vegDesc')
                   ->setParameter('vegCategory', '%vegetarian%')
                   ->setParameter('vegTitle', '%vegetarian%')
                   ->setParameter('vegDesc', '%vegetarian%');
                break;
            case 'desserts':
                $qb->andWhere('LOWER(c.name) LIKE :dessertCategory OR LOWER(r.title) LIKE :dessertTitle OR LOWER(r.description) LIKE :dessertDesc')
                   ->setParameter('dessertCategory', '%dessert%')
                   ->setParameter('dessertTitle', '%dessert%')
                   ->setParameter('dessertDesc', '%dessert%');
                break;
            default:
                // If category doesn't match predefined filters, try to match by category name
                $qb->andWhere('LOWER(c.name) LIKE :categoryName OR LOWER(c.slug) LIKE :categorySlug')
                   ->setParameter('categoryName', '%'.mb_strtolower($category).'%')
                   ->setParameter('categorySlug', '%'.mb_strtolower($category).'%');
                break;
        }

        // Add search query if provided
        if ($q) {
            $qb->andWhere('r.title LIKE :q OR r.description LIKE :q')
               ->setParameter('q', '%'.$q.'%');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Search recipes with advanced filters
     */
    public function searchAdvanced(array $criteria = []): array
    {
        $qb = $this->createQueryBuilder('r')
            ->leftJoin('r.category', 'c')
            ->leftJoin('r.reviews', 'rev')
            ->leftJoin('r.author', 'a');

        // Text search
        if (!empty($criteria['query'])) {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like('r.title', ':query'),
                    $qb->expr()->like('r.description', ':query'),
                    $qb->expr()->like('r.instructions', ':query')
                )
            )
            ->setParameter('query', '%' . $criteria['query'] . '%');
        }

        // Category filter
        if (!empty($criteria['category'])) {
            $qb->andWhere('r.category = :category')
               ->setParameter('category', $criteria['category']);
        }

        // Difficulty filter
        if (isset($criteria['difficulty']) && $criteria['difficulty'] !== null) {
            $qb->andWhere('r.difficulty = :difficulty')
               ->setParameter('difficulty', $criteria['difficulty']);
        }

        // Max prep time filter
        if (!empty($criteria['maxPrepTime'])) {
            $qb->andWhere('r.prepTime <= :maxPrepTime')
               ->setParameter('maxPrepTime', $criteria['maxPrepTime']);
        }

        // Minimum rating filter
        if (!empty($criteria['minRating'])) {
            $qb->groupBy('r.id')
               ->having('AVG(rev.rating) >= :minRating')
               ->setParameter('minRating', $criteria['minRating']);
        }

        // Dietary restrictions filter
        if (!empty($criteria['dietaryRestrictions'])) {
            $dietaryConditions = [];
            foreach ($criteria['dietaryRestrictions'] as $index => $restriction) {
                switch ($restriction) {
                    case 'vegetarian':
                        $dietaryConditions[] = $qb->expr()->orX(
                            $qb->expr()->like('r.title', ':veg' . $index),
                            $qb->expr()->like('r.description', ':veg' . $index),
                            $qb->expr()->like('c.name', ':vegCat' . $index)
                        );
                        $qb->setParameter('veg' . $index, '%vegetarian%')
                           ->setParameter('vegCat' . $index, '%vegetarian%');
                        break;
                    case 'vegan':
                        $dietaryConditions[] = $qb->expr()->orX(
                            $qb->expr()->like('r.title', ':vegan' . $index),
                            $qb->expr()->like('r.description', ':vegan' . $index),
                            $qb->expr()->like('c.name', ':veganCat' . $index)
                        );
                        $qb->setParameter('vegan' . $index, '%vegan%')
                           ->setParameter('veganCat' . $index, '%vegan%');
                        break;
                    case 'gluten_free':
                        $dietaryConditions[] = $qb->expr()->orX(
                            $qb->expr()->like('r.title', ':gluten' . $index),
                            $qb->expr()->like('r.description', ':gluten' . $index)
                        );
                        $qb->setParameter('gluten' . $index, '%gluten%free%');
                        break;
                    case 'dairy_free':
                        $dietaryConditions[] = $qb->expr()->orX(
                            $qb->expr()->like('r.title', ':dairy' . $index),
                            $qb->expr()->like('r.description', ':dairy' . $index)
                        );
                        $qb->setParameter('dairy' . $index, '%dairy%free%');
                        break;
                    case 'low_carb':
                        $dietaryConditions[] = $qb->expr()->orX(
                            $qb->expr()->like('r.title', ':lowcarb' . $index),
                            $qb->expr()->like('r.description', ':lowcarb' . $index)
                        );
                        $qb->setParameter('lowcarb' . $index, '%low%carb%');
                        break;
                }
            }
            
            if (!empty($dietaryConditions)) {
                $qb->andWhere($qb->expr()->orX(...$dietaryConditions));
            }
        }

        // Order by rating and creation date
        if (!empty($criteria['minRating'])) {
            $qb->orderBy('AVG(rev.rating)', 'DESC')
               ->addOrderBy('r.createdAt', 'DESC');
        } else {
            $qb->orderBy('r.createdAt', 'DESC');
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

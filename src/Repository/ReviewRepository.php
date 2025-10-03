<?php

namespace App\Repository;

use App\Entity\Review;
use App\Entity\Recipe;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Review>
 */
class ReviewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Review::class);
    }

    /**
     * Find review by user and recipe (to prevent duplicate reviews)
     */
    public function findByUserAndRecipe(User $user, Recipe $recipe): ?Review
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.author = :user')
            ->andWhere('r.recipe = :recipe')
            ->setParameter('user', $user)
            ->setParameter('recipe', $recipe)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Get average rating for a recipe
     */
    public function getAverageRating(Recipe $recipe): ?float
    {
        $result = $this->createQueryBuilder('r')
            ->select('AVG(r.rating) as avgRating')
            ->andWhere('r.recipe = :recipe')
            ->setParameter('recipe', $recipe)
            ->getQuery()
            ->getSingleScalarResult();

        return $result ? round((float) $result, 1) : null;
    }

    /**
     * Get review count for a recipe
     */
    public function getReviewCount(Recipe $recipe): int
    {
        return $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->andWhere('r.recipe = :recipe')
            ->setParameter('recipe', $recipe)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Get recent reviews for a recipe with authors
     */
    public function findRecentByRecipe(Recipe $recipe, int $limit = 10): array
    {
        return $this->createQueryBuilder('r')
            ->leftJoin('r.author', 'u')->addSelect('u')
            ->andWhere('r.recipe = :recipe')
            ->setParameter('recipe', $recipe)
            ->orderBy('r.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
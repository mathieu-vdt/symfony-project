<?php

namespace App\GraphQL\Resolver;

use App\Entity\Recipe;
use App\Repository\RecipeRepository;

class RecipeResolver
{
    public function __construct(
        private RecipeRepository $recipeRepository
    ) {}

    public function findAll(): array
    {
        return $this->recipeRepository->findAll();
    }

    public function findById(int $id): ?Recipe
    {
        return $this->recipeRepository->find($id);
    }

    public function findByCategory(int $categoryId): array
    {
        return $this->recipeRepository->findBy(['category' => $categoryId]);
    }

    public function findByDifficulty(int $difficulty): array
    {
        return $this->recipeRepository->findBy(['difficulty' => $difficulty]);
    }

    public function search(string $query): array
    {
        return $this->recipeRepository->createQueryBuilder('r')
            ->where('r.title LIKE :query OR r.description LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->getQuery()
            ->getResult();
    }

    public function getAverageRating(Recipe $recipe): ?float
    {
        $reviews = $recipe->getReviews();
        if ($reviews->isEmpty()) {
            return null;
        }

        $totalRating = 0;
        foreach ($reviews as $review) {
            $totalRating += $review->getRating();
        }

        return round($totalRating / $reviews->count(), 2);
    }

    public function getReviewCount(Recipe $recipe): int
    {
        return $recipe->getReviews()->count();
    }
}
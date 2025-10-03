<?php

namespace App\GraphQL\Resolver;

use App\Entity\Ingredient;
use App\Repository\IngredientRepository;

class IngredientResolver
{
    public function __construct(
        private IngredientRepository $ingredientRepository
    ) {}

    public function findAll(): array
    {
        return $this->ingredientRepository->findAll();
    }

    public function findById(int $id): ?Ingredient
    {
        return $this->ingredientRepository->find($id);
    }
}
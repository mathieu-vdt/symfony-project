<?php

namespace App\GraphQL\Resolver;

use App\Entity\Recipe;
use App\Repository\CategoryRepository;
use App\Repository\IngredientRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class RecipeMutationResolver
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CategoryRepository $categoryRepository,
        private IngredientRepository $ingredientRepository,
        private Security $security
    ) {}

    public function createRecipe(array $input): Recipe
    {
        $user = $this->security->getUser();
        if (!$user) {
            throw new \Exception('User must be authenticated to create a recipe');
        }

        $recipe = new Recipe();
        $recipe->setTitle($input['title']);
        $recipe->setDescription($input['description'] ?? null);
        $recipe->setInstructions($input['instructions']);
        $recipe->setPrepTime($input['prepTime'] ?? null);
        $recipe->setDifficulty($input['difficulty'] ?? null);
        $recipe->setAuthor($user);
        $recipe->setCreatedAt(new \DateTimeImmutable());

        // Set category
        $category = $this->categoryRepository->find($input['categoryId']);
        if (!$category) {
            throw new \Exception('Category not found');
        }
        $recipe->setCategory($category);

        // Set ingredients
        foreach ($input['ingredientIds'] as $ingredientId) {
            $ingredient = $this->ingredientRepository->find($ingredientId);
            if ($ingredient) {
                $recipe->addIngredient($ingredient);
            }
        }

        $this->entityManager->persist($recipe);
        $this->entityManager->flush();

        return $recipe;
    }
}
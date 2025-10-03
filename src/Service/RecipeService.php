<?php

namespace App\Service;

use App\Entity\Recipe;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Service for managing recipe operations
 */
class RecipeService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Creates a new recipe and persists it to the database
     *
     * @throws \Exception If the recipe cannot be saved
     */
    public function create(Recipe $recipe): void
    {
        try {
            if (null === $recipe->getCreatedAt()) {
                $recipe->setCreatedAt(new \DateTimeImmutable());
            }

            $this->entityManager->persist($recipe);
            $this->entityManager->flush();

            $this->logger->info('Recipe created successfully', [
                'recipe_id' => $recipe->getId(),
                'recipe_title' => $recipe->getTitle(),
                'author_id' => $recipe->getAuthor()?->getId(),
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to create recipe', [
                'recipe_title' => $recipe->getTitle(),
                'author_id' => $recipe->getAuthor()?->getId(),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Updates an existing recipe
     *
     * @throws \Exception If the recipe cannot be updated
     */
    public function update(Recipe $recipe): void
    {
        try {
            if (method_exists($recipe, 'setUpdatedAt')) {
                $recipe->setUpdatedAt(new \DateTimeImmutable());
            }

            $this->entityManager->flush();

            $this->logger->info('Recipe updated successfully', [
                'recipe_id' => $recipe->getId(),
                'recipe_title' => $recipe->getTitle(),
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to update recipe', [
                'recipe_id' => $recipe->getId(),
                'recipe_title' => $recipe->getTitle(),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Deletes a recipe from the database
     *
     * @throws \Exception If the recipe cannot be deleted
     */
    public function delete(Recipe $recipe): void
    {
        try {
            $recipeId = $recipe->getId();
            $recipeTitle = $recipe->getTitle();

            $this->entityManager->remove($recipe);
            $this->entityManager->flush();

            $this->logger->info('Recipe deleted successfully', [
                'recipe_id' => $recipeId,
                'recipe_title' => $recipeTitle,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to delete recipe', [
                'recipe_id' => $recipe->getId(),
                'recipe_title' => $recipe->getTitle(),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Checks if a recipe can be edited by the current user
     */
    public function canEdit(Recipe $recipe, $user): bool
    {
        if (!$user) {
            return false;
        }

        return $recipe->getAuthor() === $user ||
               (method_exists($user, 'getRoles') && in_array('ROLE_ADMIN', $user->getRoles(), true));
    }

    /**
     * Checks if a recipe can be deleted by the current user
     */
    public function canDelete(Recipe $recipe, $user): bool
    {
        return $this->canEdit($recipe, $user);
    }
}

<?php

namespace App\Controller;

use App\Entity\Recipe;
use App\Entity\User;
use App\Form\RecipeType;
use App\Service\RecipeService;
use App\Repository\RecipeRepository;
use App\Repository\ReviewRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Controller for managing recipes (CRUD operations)
 */
class RecipeController extends AbstractController
{
    /**
     * Display a single recipe with all its details
     */
    #[Route('/recipes/{id}', name: 'app_recipe_show', requirements: ['id' => '\d+'])]
    public function show(Recipe $recipe, ReviewRepository $reviewRepository): Response
    {
        $reviews = $reviewRepository->findRecentByRecipe($recipe);
        $averageRating = $reviewRepository->getAverageRating($recipe);
        $reviewCount = $reviewRepository->getReviewCount($recipe);
        
        $userReview = null;
        if ($this->getUser()) {
            $userReview = $reviewRepository->findByUserAndRecipe($this->getUser(), $recipe);
        }

        return $this->render('recipe/show.html.twig', [
            'recipe' => $recipe,
            'reviews' => $reviews,
            'averageRating' => $averageRating,
            'reviewCount' => $reviewCount,
            'userReview' => $userReview,
        ]);
    }
    
    /**
     * Create a new recipe
     */
    #[Route('/recipes/new', name: 'app_recipe_new')]
    #[IsGranted('RECIPE_CREATE')]
    public function new(Request $request, RecipeService $recipeService): Response
    {
        $recipe = new Recipe();

        /** @var User $user */
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw new AccessDeniedHttpException('You must be logged in to create a recipe.');
        }
        
        $recipe->setAuthor($user);

        $form = $this->createForm(RecipeType::class, $recipe);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $recipeService->create($recipe);
                $this->addFlash('success', 'Recipe created successfully!');
                return $this->redirectToRoute('app_recipe_show', ['id' => $recipe->getId()]);
            } catch (\Exception $e) {
                $this->addFlash('error', 'An error occurred while creating the recipe. Please try again.');
            }
        }

        return $this->render('recipe/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Display recipes created by the current user
     */
    #[Route('/recipes/mine', name: 'app_recipe_mine')]
    #[IsGranted('ROLE_USER')]
    public function mine(RecipeRepository $recipeRepository, Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw new AccessDeniedHttpException('You must be logged in to view your recipes.');
        }

        $searchQuery = trim((string) $request->query->get('q', ''));
        $recipes = $recipeRepository->searchByAuthor($user, $searchQuery);
        
        return $this->render('home/index.html.twig', [
            'recipes' => $recipes,
            'mine' => true,
        ]);
    }
    
    /**
     * Edit an existing recipe
     */
    #[Route('/recipes/{id}/edit', name: 'app_recipe_edit', requirements: ['id' => '\d+'])]
    #[IsGranted('RECIPE_EDIT', subject: 'recipe')]
    public function edit(Recipe $recipe, Request $request, RecipeService $recipeService): Response
    {
        $form = $this->createForm(RecipeType::class, $recipe);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $recipeService->update($recipe);
                $this->addFlash('success', 'Recipe updated successfully!');
                return $this->redirectToRoute('app_recipe_show', ['id' => $recipe->getId()]);
            } catch (\Exception $e) {
                $this->addFlash('error', 'An error occurred while updating the recipe. Please try again.');
            }
        }

        return $this->render('recipe/edit.html.twig', [
            'form' => $form->createView(),
            'recipe' => $recipe,
        ]);
    }

    /**
     * Delete a recipe
     */
    #[Route('/recipes/{id}/delete', name: 'app_recipe_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsGranted('RECIPE_DELETE', subject: 'recipe')]
    public function delete(Request $request, Recipe $recipe, RecipeService $recipeService): Response
    {
        $token = (string) $request->request->get('_token');
        
        if (!$this->isCsrfTokenValid('delete' . $recipe->getId(), $token)) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return $this->redirectToRoute('app_home');
        }

        try {
            $recipeService->delete($recipe);
            $this->addFlash('success', 'Recipe deleted successfully.');
        } catch (\Exception $e) {
            $this->addFlash('error', 'An error occurred while deleting the recipe. Please try again.');
        }

        return $this->redirectToRoute('app_home');
    }
}

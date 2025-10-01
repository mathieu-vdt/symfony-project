<?php

namespace App\Controller;

use App\Entity\Recipe;
use App\Form\RecipeType;
use App\Service\RecipeService;
use App\Repository\RecipeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class RecipeController extends AbstractController
{
    #[Route('/recipes/{id}', name: 'app_recipe_show', requirements: ['id' => '\d+'])]
    public function show(Recipe $recipe): Response
    {
        // $recipe est injectée automatiquement (404 si id inconnu)
        return $this->render('recipe/show.html.twig', [
            'recipe' => $recipe,
        ]);
    }
    

    #[Route('/recipes/new', name: 'app_recipe_new')]
    #[IsGranted('RECIPE_CREATE')]
    public function new(Request $request, RecipeService $recipes): Response
    {
        $recipe = new Recipe();

        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $recipe->setAuthor($user);

        $form = $this->createForm(RecipeType::class, $recipe);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $recipes->create($recipe);
            $this->addFlash('success', 'Recipe created successfully!');
            return $this->redirectToRoute('app_home');
        }

        return $this->render('recipe/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/recipes/mine', name: 'app_recipe_mine')]
    public function mine(RecipeRepository $repo, Request $request): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $q = trim((string) $request->query->get('q', ''));
        $recipes = $repo->searchByAuthor($user, $q); // à faire (exemple plus bas)
        return $this->render('home/index.html.twig', [
            'recipes' => $recipes,
            'mine'    => true,
        ]);
    }
    

    #[Route('/recipes/{id}/edit', name: 'app_recipe_edit')]
    #[IsGranted('RECIPE_EDIT', subject: 'recipe')] 
    public function edit(Recipe $recipe, Request $request, RecipeService $recipes): Response
    {
        $this->denyAccessUnlessGranted('RECIPE_EDIT', $recipe);

        $form = $this->createForm(RecipeType::class, $recipe);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $recipes->update($recipe);

            $this->addFlash('success', 'Recette mise à jour avec succès !');
            return $this->redirectToRoute('app_home');
        }

        return $this->render('recipe/edit.html.twig', [
            'form' => $form->createView(),
            'recipe' => $recipe,
        ]);
    }

    #[Route('/recipes/{id}/delete', name: 'app_recipe_delete', methods: ['POST'])]
    public function delete(Request $request, Recipe $recipe, RecipeService $recipes): Response
    {
        $this->denyAccessUnlessGranted('RECIPE_DELETE', $recipe);

        if (!$this->isCsrfTokenValid('delete'.$recipe->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('app_home');
        }

        $recipes->delete($recipe);
        $this->addFlash('success', 'Recette supprimée.');
        return $this->redirectToRoute('app_home');
    }

}

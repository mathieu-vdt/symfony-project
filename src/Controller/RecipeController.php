<?php

namespace App\Controller;

use App\Entity\Recipe;
use App\Form\RecipeType;
use App\Service\RecipeService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

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
    public function new(Request $request, RecipeService $recipes): Response
    {
        $recipe = new Recipe();
        $form = $this->createForm(RecipeType::class, $recipe);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $recipes->create($recipe);

            $this->addFlash('success', 'Recette créée avec succès !');
            return $this->redirectToRoute('app_home');
        }

        return $this->render('recipe/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }
    

    #[Route('/recipes/{id}/edit', name: 'app_recipe_edit')]
    public function edit(Recipe $recipe, Request $request, RecipeService $recipes): Response
    {
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
}

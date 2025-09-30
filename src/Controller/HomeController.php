<?php

namespace App\Controller;

use App\Entity\Recipe;
use App\Repository\RecipeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(RecipeRepository $recipeRepository): Response
    {
        // Récupérer toutes les recettes
        $recipes = $recipeRepository->findAll();

        // Passer à la vue
        return $this->render('home/index.html.twig', [
            'recipes' => $recipes,
        ]);
    }
}

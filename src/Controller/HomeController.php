<?php

namespace App\Controller;

use App\Entity\Recipe;
use App\Form\RecipeSearchType;
use App\Repository\RecipeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(Request $request, RecipeRepository $repo): Response
    {
        // Create the search form
        $searchForm = $this->createForm(RecipeSearchType::class);
        $searchForm->handleRequest($request);

        $recipes = [];
        $searchCriteria = [];

        if ($searchForm->isSubmitted() && $searchForm->isValid()) {
            // Use advanced search with form data
            $searchCriteria = array_filter($searchForm->getData(), function ($value) {
                return $value !== null && $value !== '' && (!is_array($value) || !empty($value));
            });
            
            $recipes = $repo->searchAdvanced($searchCriteria);
        } else {
            // Fallback to simple search or category filtering for backward compatibility
            $q = trim((string) $request->query->get('q', ''));
            $category = trim((string) $request->query->get('category', ''));

            if ($category !== '') {
                // Filter by category with optional search
                $recipes = $repo->findByCategory($category, $q !== '' ? $q : null);
            } elseif ($q !== '') {
                // Search only
                $recipes = $repo->searchByQuery($q);
            } else {
                // Show all recipes
                $recipes = $repo->findBy([], ['createdAt' => 'DESC']);
            }
        }

        return $this->render('home/index.html.twig', [
            'recipes' => $recipes,
            'searchForm' => $searchForm->createView(),
            'searchCriteria' => $searchCriteria,
        ]);
    }
}

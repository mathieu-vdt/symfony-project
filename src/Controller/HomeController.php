<?php

namespace App\Controller;

use App\Entity\Recipe;
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
        $q = trim((string) $request->query->get('q', ''));
        $recipes = $q === ''
            ? $repo->findBy([], ['createdAt' => 'DESC'])
            : $repo->searchByQuery($q);

        return $this->render('home/index.html.twig', [
            'recipes' => $recipes,
        ]);
    }

}

<?php

namespace App\Controller\Admin;

use App\Entity\Recipe;
use App\Entity\Category;
use App\Entity\Ingredient;
use App\Entity\User;
use App\Repository\RecipeRepository;
use App\Repository\CategoryRepository;
use App\Repository\IngredientRepository;
use App\Repository\UserRepository;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;

class DashboardController extends AbstractDashboardController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private RecipeRepository $recipeRepository,
        private CategoryRepository $categoryRepository,
        private IngredientRepository $ingredientRepository,
        private UserRepository $userRepository,
    ) {}

    #[Route('/admin', name: 'admin_dashboard')]
    public function index(): Response
    {
        // Calculate KPIs
        $stats = $this->calculateStats();
        $recentRecipes = $this->getRecentRecipes();
        $categoryStats = $this->getCategoryStats();

        return $this->render('admin/admin_dashboard.html.twig', [
            'stats' => $stats,
            'recentRecipes' => $recentRecipes,
            'categoryStats' => $categoryStats,
        ]);
    }

    private function calculateStats(): array
    {
        $totalRecipes = $this->recipeRepository->count([]);
        $totalUsers = $this->userRepository->count([]);
        $totalCategories = $this->categoryRepository->count([]);
        $totalIngredients = $this->ingredientRepository->count([]);

        // Calculate new additions this week
        $weekAgo = new \DateTimeImmutable('-1 week');
        
        $newRecipesThisWeek = $this->entityManager->createQueryBuilder()
            ->select('COUNT(r.id)')
            ->from(Recipe::class, 'r')
            ->where('r.createdAt >= :weekAgo')
            ->setParameter('weekAgo', $weekAgo)
            ->getQuery()
            ->getSingleScalarResult();

        // Since User entity doesn't have createdAt, we'll set this to 0 for now
        $newUsersThisWeek = 0;

        return [
            'totalRecipes' => $totalRecipes,
            'totalUsers' => $totalUsers,
            'totalCategories' => $totalCategories,
            'totalIngredients' => $totalIngredients,
            'newRecipesThisWeek' => $newRecipesThisWeek,
            'newUsersThisWeek' => $newUsersThisWeek,
        ];
    }

    private function getRecentRecipes(int $limit = 5): array
    {
        return $this->recipeRepository->findBy(
            [],
            ['createdAt' => 'DESC'],
            $limit
        );
    }

    private function getCategoryStats(): array
    {
        $qb = $this->entityManager->createQueryBuilder();
        
        return $qb->select('c.name, COUNT(r.id) as recipeCount')
            ->from(Category::class, 'c')
            ->leftJoin('c.recipes', 'r')
            ->groupBy('c.id')
            ->orderBy('recipeCount', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('🍳 Cookbook Admin')
            ->setFaviconPath('favicon.ico')
            ->setTranslationDomain('admin')
            ->renderContentMaximized();
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('📊 Dashboard', 'fa fa-home');
        yield MenuItem::linkToUrl('🌐 View Website', 'fa fa-external-link', $this->generateUrl('app_home'))
            ->setLinkTarget('_blank');

        yield MenuItem::section('📚 Content Management');
        yield MenuItem::linkToCrud('🍽️ Recipes', 'fa fa-utensils', Recipe::class)
            ->setBadge($this->recipeRepository->count([]), 'info');
        yield MenuItem::linkToCrud('🏷️ Categories', 'fa fa-tags', Category::class)
            ->setBadge($this->categoryRepository->count([]), 'success');
        yield MenuItem::linkToCrud('🥕 Ingredients', 'fa fa-leaf', Ingredient::class)
            ->setBadge($this->ingredientRepository->count([]), 'warning');

        yield MenuItem::section('👥 User Management');
        yield MenuItem::linkToCrud('👤 Users', 'fa fa-users', User::class)
            ->setBadge($this->userRepository->count([]), 'primary');

        yield MenuItem::section('⚙️ Settings');
        yield MenuItem::linkToLogout('🚪 Logout', 'fa fa-sign-out-alt');
    }
}


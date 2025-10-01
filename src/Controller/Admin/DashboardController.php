<?php

namespace App\Controller\Admin;

use App\Entity\Recipe;
use App\Entity\Category;
use App\Entity\Ingredient;
use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractDashboardController
{
    #[Route('/admin', name: 'admin_dashboard')]
    public function index(): Response
    {
        $url = $this->container->get(AdminUrlGenerator::class)
            ->setController(RecipeCrudController::class)
            ->setAction('index')
            ->generateUrl();

        return $this->redirect($url);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Cookbook Admin');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');

        yield MenuItem::section('Content');
        yield MenuItem::linkToCrud('Recipes', 'fa fa-utensils', Recipe::class);
        yield MenuItem::linkToCrud('Categories', 'fa fa-tags', Category::class);
        yield MenuItem::linkToCrud('Ingredients', 'fa fa-leaf', Ingredient::class);

        yield MenuItem::section('Users');
        yield MenuItem::linkToCrud('Users', 'fa fa-user', User::class);
    }
}


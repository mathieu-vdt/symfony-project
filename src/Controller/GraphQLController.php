<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class GraphQLController extends AbstractController
{
    #[Route('/graphql-info', name: 'app_graphql_info')]
    public function index(): Response
    {
        return $this->render('graphql/index.html.twig');
    }
}
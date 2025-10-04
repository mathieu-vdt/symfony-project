<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\DBAL\Connection;

class HealthController extends AbstractController
{
    #[Route('/health', name: 'app_health', methods: ['GET'])]
    public function health(Connection $connection): JsonResponse
    {
        try {
            // Check database connection
            $connection->executeQuery('SELECT 1');
            
            return new JsonResponse([
                'status' => 'ok',
                'timestamp' => date('c'),
                'services' => [
                    'database' => 'ok',
                    'application' => 'ok'
                ]
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'timestamp' => date('c'),
                'error' => 'Database connection failed'
            ], 503);
        }
    }
}
<?php

namespace App\Controller\Api;

use App\Service\MongoDBService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class HealthController extends AbstractController
{
    private MongoDBService $mongoService;

    public function __construct(MongoDBService $mongoService)
    {
        $this->mongoService = $mongoService;
    }

    #[Route('/api/health', name: 'api_health', methods: ['GET'])]
    public function health(): JsonResponse
    {
        $mongoConnected = $this->mongoService->ping();

        return $this->json([
            'status' => 'OK',
            'message' => 'API TDBR Symfony fonctionne correctement',
            'timestamp' => (new \DateTime())->format(\DateTime::ISO8601),
            'database' => [
                'mongodb' => $mongoConnected ? 'connected' : 'disconnected'
            ]
        ]);
    }
}

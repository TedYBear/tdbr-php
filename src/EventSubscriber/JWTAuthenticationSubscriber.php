<?php

namespace App\EventSubscriber;

use App\Service\JWTService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class JWTAuthenticationSubscriber implements EventSubscriberInterface
{
    private JWTService $jwtService;

    public function __construct(JWTService $jwtService)
    {
        $this->jwtService = $jwtService;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 10],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        // Ignorer les routes publiques
        $publicRoutes = [
            '/api/auth/inscription',
            '/api/auth/connexion',
            '/api/categories',
            '/api/articles',
            '/api/collections',
            '/api/health'
        ];

        $path = $request->getPathInfo();

        // Vérifier si c'est une route publique
        foreach ($publicRoutes as $publicRoute) {
            if (str_starts_with($path, $publicRoute) && !str_contains($path, '/admin')) {
                return;
            }
        }

        // Vérifier si c'est une route admin
        $isAdminRoute = str_contains($path, '/admin');

        if ($isAdminRoute || str_starts_with($path, '/api/auth/profil')) {
            // Récupérer le token depuis le header Authorization
            $authHeader = $request->headers->get('Authorization');

            if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
                $event->setResponse(new JsonResponse(
                    ['error' => 'Token manquant'],
                    Response::HTTP_UNAUTHORIZED
                ));
                return;
            }

            $token = substr($authHeader, 7); // Retirer "Bearer "

            // Vérifier le token
            $decoded = $this->jwtService->decodeToken($token);

            if (!$decoded) {
                $event->setResponse(new JsonResponse(
                    ['error' => 'Token invalide ou expiré'],
                    Response::HTTP_UNAUTHORIZED
                ));
                return;
            }

            // Ajouter les informations de l'utilisateur à la requête
            $request->attributes->set('userId', $decoded['userId']);
            $request->attributes->set('userEmail', $decoded['email']);
            $request->attributes->set('userRole', $decoded['role']);

            // Vérifier si l'utilisateur est admin pour les routes admin
            if ($isAdminRoute && $decoded['role'] !== 'admin') {
                $event->setResponse(new JsonResponse(
                    ['error' => 'Accès refusé - Droits administrateur requis'],
                    Response::HTTP_FORBIDDEN
                ));
                return;
            }
        }
    }
}

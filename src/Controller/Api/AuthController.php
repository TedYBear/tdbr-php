<?php

namespace App\Controller\Api;

use App\Service\MongoDBService;
use App\Service\JWTService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[Route('/api/auth', name: 'api_auth_')]
class AuthController extends AbstractController
{
    private MongoDBService $mongoService;
    private JWTService $jwtService;

    public function __construct(MongoDBService $mongoService, JWTService $jwtService)
    {
        $this->mongoService = $mongoService;
        $this->jwtService = $jwtService;
    }

    /**
     * User registration
     */
    #[Route('/inscription', name: 'register', methods: ['POST'])]
    public function inscription(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            // Validation
            if (!isset($data['email']) || !isset($data['password'])) {
                return $this->json(
                    ['error' => 'Email et mot de passe requis'],
                    Response::HTTP_BAD_REQUEST
                );
            }

            if (!isset($data['prenom']) || !isset($data['nom'])) {
                return $this->json(
                    ['error' => 'Prénom et nom requis'],
                    Response::HTTP_BAD_REQUEST
                );
            }

            // Vérifier que l'email n'existe pas déjà
            $collection = $this->mongoService->getCollection('users');
            $existing = $collection->findOne(['email' => $data['email']]);

            if ($existing) {
                return $this->json(
                    ['error' => 'Un compte avec cet email existe déjà'],
                    Response::HTTP_CONFLICT
                );
            }

            // Hacher le mot de passe
            $hashedPassword = password_hash($data['password'], PASSWORD_BCRYPT);

            // Créer l'utilisateur
            $userData = [
                'email' => $data['email'],
                'password' => $hashedPassword,
                'prenom' => $data['prenom'],
                'nom' => $data['nom'],
                'telephone' => $data['telephone'] ?? '',
                'adresse' => $data['adresse'] ?? [
                    'rue' => '',
                    'ville' => '',
                    'codePostal' => '',
                    'pays' => 'France'
                ],
                'role' => 'user',
                'actif' => true,
                'createdAt' => new \MongoDB\BSON\UTCDateTime(),
                'updatedAt' => new \MongoDB\BSON\UTCDateTime()
            ];

            $result = $collection->insertOne($userData);
            $userData['_id'] = $result->getInsertedId();

            // Générer le token JWT
            $token = $this->jwtService->generateToken([
                'userId' => (string) $userData['_id'],
                'email' => $userData['email'],
                'role' => $userData['role']
            ]);

            // Retirer le mot de passe de la réponse
            unset($userData['password']);

            return $this->json([
                'user' => $userData,
                'token' => $token
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * User login
     */
    #[Route('/connexion', name: 'login', methods: ['POST'])]
    public function connexion(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            // Validation
            if (!isset($data['email']) || !isset($data['password'])) {
                return $this->json(
                    ['error' => 'Email et mot de passe requis'],
                    Response::HTTP_BAD_REQUEST
                );
            }

            // Chercher l'utilisateur
            $collection = $this->mongoService->getCollection('users');
            $user = $collection->findOne(['email' => $data['email']]);

            if (!$user) {
                return $this->json(
                    ['error' => 'Email ou mot de passe incorrect'],
                    Response::HTTP_UNAUTHORIZED
                );
            }

            // Vérifier le mot de passe
            if (!password_verify($data['password'], $user['password'])) {
                return $this->json(
                    ['error' => 'Email ou mot de passe incorrect'],
                    Response::HTTP_UNAUTHORIZED
                );
            }

            // Vérifier que le compte est actif
            if (!$user['actif']) {
                return $this->json(
                    ['error' => 'Compte désactivé'],
                    Response::HTTP_FORBIDDEN
                );
            }

            // Générer le token JWT
            $token = $this->jwtService->generateToken([
                'userId' => (string) $user['_id'],
                'email' => $user['email'],
                'role' => $user['role']
            ]);

            // Retirer le mot de passe de la réponse
            $userData = (array) $user;
            unset($userData['password']);

            return $this->json([
                'user' => $userData,
                'token' => $token
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get user profile (authenticated)
     */
    #[Route('/profil', name: 'profile', methods: ['GET'])]
    public function getProfil(Request $request): JsonResponse
    {
        try {
            // Récupérer l'utilisateur depuis le token
            $userId = $request->attributes->get('userId');

            if (!$userId) {
                return $this->json(
                    ['error' => 'Non authentifié'],
                    Response::HTTP_UNAUTHORIZED
                );
            }

            $collection = $this->mongoService->getCollection('users');
            $user = $collection->findOne(['_id' => new \MongoDB\BSON\ObjectId($userId)]);

            if (!$user) {
                return $this->json(
                    ['error' => 'Utilisateur non trouvé'],
                    Response::HTTP_NOT_FOUND
                );
            }

            // Retirer le mot de passe
            $userData = (array) $user;
            unset($userData['password']);

            return $this->json($userData);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update user profile (authenticated)
     */
    #[Route('/profil', name: 'profile_update', methods: ['PUT'])]
    public function updateProfil(Request $request): JsonResponse
    {
        try {
            // Récupérer l'utilisateur depuis le token
            $userId = $request->attributes->get('userId');

            if (!$userId) {
                return $this->json(
                    ['error' => 'Non authentifié'],
                    Response::HTTP_UNAUTHORIZED
                );
            }

            $data = json_decode($request->getContent(), true);
            $collection = $this->mongoService->getCollection('users');

            // Préparer les données de mise à jour
            $updateData = [
                'updatedAt' => new \MongoDB\BSON\UTCDateTime()
            ];

            if (isset($data['prenom'])) $updateData['prenom'] = $data['prenom'];
            if (isset($data['nom'])) $updateData['nom'] = $data['nom'];
            if (isset($data['telephone'])) $updateData['telephone'] = $data['telephone'];
            if (isset($data['adresse'])) $updateData['adresse'] = $data['adresse'];

            // Si changement de mot de passe
            if (isset($data['password']) && !empty($data['password'])) {
                $updateData['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
            }

            $collection->updateOne(
                ['_id' => new \MongoDB\BSON\ObjectId($userId)],
                ['$set' => $updateData]
            );

            $updatedUser = $collection->findOne(['_id' => new \MongoDB\BSON\ObjectId($userId)]);

            // Retirer le mot de passe
            $userData = (array) $updatedUser;
            unset($userData['password']);

            return $this->json($userData);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}

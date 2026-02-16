<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/api/uploads', name: 'api_upload_')]
class UploadController extends AbstractController
{
    private string $uploadDir;
    private SluggerInterface $slugger;

    public function __construct(string $uploadDir, SluggerInterface $slugger)
    {
        $this->uploadDir = $uploadDir;
        $this->slugger = $slugger;
    }

    /**
     * Upload an image (admin only)
     */
    #[Route('/image', name: 'image', methods: ['POST'])]
    public function uploadImage(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        try {
            /** @var UploadedFile $file */
            $file = $request->files->get('image');

            if (!$file) {
                return $this->json(
                    ['error' => 'Aucun fichier fourni'],
                    Response::HTTP_BAD_REQUEST
                );
            }

            // Vérifier le type de fichier
            $mimeType = $file->getMimeType();
            $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

            if (!in_array($mimeType, $allowedMimeTypes)) {
                return $this->json(
                    ['error' => 'Type de fichier non autorisé. Formats acceptés: JPEG, PNG, GIF, WebP'],
                    Response::HTTP_BAD_REQUEST
                );
            }

            // Vérifier la taille du fichier (max 5MB)
            if ($file->getSize() > 5242880) {
                return $this->json(
                    ['error' => 'Fichier trop volumineux. Taille maximum: 5MB'],
                    Response::HTTP_BAD_REQUEST
                );
            }

            // Générer un nom de fichier unique
            $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $this->slugger->slug($originalFilename);
            $newFilename = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();

            // Créer le dossier uploads s'il n'existe pas
            if (!is_dir($this->uploadDir)) {
                mkdir($this->uploadDir, 0755, true);
            }

            // Déplacer le fichier
            try {
                $file->move($this->uploadDir, $newFilename);
            } catch (FileException $e) {
                return $this->json(
                    ['error' => 'Erreur lors de l\'upload: ' . $e->getMessage()],
                    Response::HTTP_INTERNAL_SERVER_ERROR
                );
            }

            // Retourner le chemin relatif
            $path = '/uploads/' . $newFilename;

            return $this->json([
                'message' => 'Image uploadée avec succès',
                'path' => $path,
                'filename' => $newFilename
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->json(
                ['error' => $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Delete an image (admin only)
     */
    #[Route('/{path}', name: 'delete', methods: ['DELETE'], requirements: ['path' => '.+'])]
    public function deleteImage(string $path): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        try {
            // Nettoyer le chemin
            $path = str_replace('/uploads/', '', $path);
            $path = basename($path); // Sécurité: éviter les ../ etc.

            $filePath = $this->uploadDir . '/' . $path;

            // Vérifier que le fichier existe
            if (!file_exists($filePath)) {
                return $this->json(
                    ['error' => 'Fichier non trouvé'],
                    Response::HTTP_NOT_FOUND
                );
            }

            // Supprimer le fichier
            if (!unlink($filePath)) {
                return $this->json(
                    ['error' => 'Impossible de supprimer le fichier'],
                    Response::HTTP_INTERNAL_SERVER_ERROR
                );
            }

            return $this->json([
                'message' => 'Fichier supprimé avec succès'
            ]);
        } catch (\Exception $e) {
            return $this->json(
                ['error' => $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}

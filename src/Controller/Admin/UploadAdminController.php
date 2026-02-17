<?php

namespace App\Controller\Admin;

use App\Service\UploadService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/upload')]
#[IsGranted('ROLE_ADMIN')]
class UploadAdminController extends AbstractController
{
    public function __construct(
        private UploadService $uploadService
    ) {
    }

    #[Route('/image', name: 'admin_upload_image', methods: ['POST'])]
    public function uploadImage(Request $request): JsonResponse
    {
        /** @var UploadedFile|null $file */
        $file = $request->files->get('file');

        if (!$file) {
            return $this->json(['error' => 'Aucun fichier fourni'], 400);
        }

        // Validation
        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file->getMimeType(), $allowedMimeTypes)) {
            return $this->json(['error' => 'Type de fichier non autorisé. Utilisez JPG, PNG, GIF ou WebP.'], 400);
        }

        $maxSize = 5 * 1024 * 1024; // 5 MB
        if ($file->getSize() > $maxSize) {
            return $this->json(['error' => 'Le fichier est trop volumineux (max 5 MB)'], 400);
        }

        // Répertoire cible (paramètre ?dir=categories|articles|collections|general)
        $allowedDirs = ['articles', 'categories', 'collections', 'general'];
        $dir = $request->query->get('dir', 'articles');
        if (!in_array($dir, $allowedDirs, true)) {
            $dir = 'articles';
        }

        // Upload
        $path = $this->uploadService->upload($file, $dir);

        if (!$path) {
            return $this->json(['error' => 'Erreur lors de l\'upload'], 500);
        }

        // Redimensionner l'image (best effort, ne bloque pas si GD ne supporte pas le format)
        try {
            $this->uploadService->resize($path, 1200, 1200);
        } catch (\Throwable) {
            // GD indisponible ou format non supporté — on garde l'image originale
        }

        return $this->json([
            'success' => true,
            'path' => $path,
            'url' => $request->getSchemeAndHttpHost() . $path
        ]);
    }

    #[Route('/images', name: 'admin_upload_images', methods: ['POST'])]
    public function uploadMultipleImages(Request $request): JsonResponse
    {
        $files = $request->files->get('files', []);

        if (empty($files)) {
            return $this->json(['error' => 'Aucun fichier fourni'], 400);
        }

        $uploadedPaths = [];
        $errors = [];

        foreach ($files as $index => $file) {
            if (!$file instanceof UploadedFile) {
                continue;
            }

            // Validation
            $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!in_array($file->getMimeType(), $allowedMimeTypes)) {
                $errors[] = "Fichier {$index}: Type non autorisé";
                continue;
            }

            $maxSize = 5 * 1024 * 1024; // 5 MB
            if ($file->getSize() > $maxSize) {
                $errors[] = "Fichier {$index}: Trop volumineux (max 5 MB)";
                continue;
            }

            // Upload
            $path = $this->uploadService->upload($file, 'articles');

            if ($path) {
                // Redimensionner
                $this->uploadService->resize($path, 1200, 1200);

                $uploadedPaths[] = [
                    'path' => $path,
                    'url' => $request->getSchemeAndHttpHost() . $path
                ];
            } else {
                $errors[] = "Fichier {$index}: Erreur lors de l'upload";
            }
        }

        return $this->json([
            'success' => count($uploadedPaths) > 0,
            'uploaded' => $uploadedPaths,
            'errors' => $errors
        ]);
    }

    #[Route('/delete', name: 'admin_upload_delete', methods: ['POST'])]
    public function deleteImage(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $path = $data['path'] ?? null;

        if (!$path) {
            return $this->json(['error' => 'Chemin non fourni'], 400);
        }

        $deleted = $this->uploadService->delete($path);

        return $this->json([
            'success' => $deleted,
            'message' => $deleted ? 'Image supprimée' : 'Erreur lors de la suppression'
        ]);
    }
}

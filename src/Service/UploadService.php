<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class UploadService
{
    private string $uploadsDirectory;

    public function __construct(
        string $uploadsDirectory,
        private SluggerInterface $slugger
    ) {
        $this->uploadsDirectory = $uploadsDirectory;
    }

    /**
     * Upload un fichier et retourne le chemin relatif
     */
    public function upload(UploadedFile $file, string $directory = 'articles'): ?string
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $newFilename = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();

        try {
            $targetDirectory = $this->uploadsDirectory . '/' . $directory;

            // Créer le dossier s'il n'existe pas
            if (!is_dir($targetDirectory)) {
                mkdir($targetDirectory, 0755, true);
            }

            $file->move($targetDirectory, $newFilename);

            // Retourner le chemin relatif depuis public/
            return '/uploads/' . $directory . '/' . $newFilename;
        } catch (FileException $e) {
            return null;
        }
    }

    /**
     * Upload plusieurs fichiers
     *
     * @param UploadedFile[] $files
     * @return string[]
     */
    public function uploadMultiple(array $files, string $directory = 'articles'): array
    {
        $uploadedPaths = [];

        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $path = $this->upload($file, $directory);
                if ($path) {
                    $uploadedPaths[] = $path;
                }
            }
        }

        return $uploadedPaths;
    }

    /**
     * Supprime un fichier uploadé
     */
    public function delete(string $path): bool
    {
        $fullPath = $this->uploadsDirectory . '/../' . $path;

        if (file_exists($fullPath)) {
            return unlink($fullPath);
        }

        return false;
    }

    /**
     * Redimensionne une image (nécessite GD)
     */
    public function resize(string $path, int $maxWidth = 1200, int $maxHeight = 1200): bool
    {
        $fullPath = $this->uploadsDirectory . '/../' . $path;

        if (!file_exists($fullPath)) {
            return false;
        }

        $imageInfo = getimagesize($fullPath);
        if (!$imageInfo) {
            return false;
        }

        [$width, $height, $type] = $imageInfo;

        // Si l'image est déjà plus petite, ne rien faire
        if ($width <= $maxWidth && $height <= $maxHeight) {
            return true;
        }

        // Calculer les nouvelles dimensions
        $ratio = min($maxWidth / $width, $maxHeight / $height);
        $newWidth = (int)($width * $ratio);
        $newHeight = (int)($height * $ratio);

        // Créer l'image source
        $source = match ($type) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($fullPath),
            IMAGETYPE_PNG => imagecreatefrompng($fullPath),
            IMAGETYPE_GIF => imagecreatefromgif($fullPath),
            IMAGETYPE_WEBP => imagecreatefromwebp($fullPath),
            default => null
        };

        if (!$source) {
            return false;
        }

        // Créer l'image de destination
        $destination = imagecreatetruecolor($newWidth, $newHeight);

        // Préserver la transparence pour PNG et GIF
        if ($type === IMAGETYPE_PNG || $type === IMAGETYPE_GIF) {
            imagealphablending($destination, false);
            imagesavealpha($destination, true);
            $transparent = imagecolorallocatealpha($destination, 255, 255, 255, 127);
            imagefilledrectangle($destination, 0, 0, $newWidth, $newHeight, $transparent);
        }

        // Redimensionner
        imagecopyresampled($destination, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        // Sauvegarder
        $result = match ($type) {
            IMAGETYPE_JPEG => imagejpeg($destination, $fullPath, 90),
            IMAGETYPE_PNG => imagepng($destination, $fullPath, 9),
            IMAGETYPE_GIF => imagegif($destination, $fullPath),
            IMAGETYPE_WEBP => imagewebp($destination, $fullPath, 90),
            default => false
        };

        imagedestroy($source);
        imagedestroy($destination);

        return $result;
    }
}

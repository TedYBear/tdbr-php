<?php

namespace App\Controller;

use App\Repository\GuideTailleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class GuideTailleController extends AbstractController
{
    #[Route('/guide-tailles/{slug}', name: 'guide_taille_view')]
    public function view(string $slug, GuideTailleRepository $repo): Response
    {
        $guide = $repo->findBySlug($slug);
        if (!$guide || !$guide->isActif()) {
            throw $this->createNotFoundException('Guide des tailles introuvable');
        }

        return $this->render('public/guide_taille.html.twig', [
            'guide' => $guide,
        ]);
    }
}

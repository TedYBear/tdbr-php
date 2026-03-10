<?php

namespace App\Controller\Admin;

use App\Service\PrintfulService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/printful')]
#[IsGranted('ROLE_ADMIN')]
class PrintfulAdminController extends AbstractController
{
    public function __construct(
        private PrintfulService $printfulService,
    ) {}

    #[Route('/sync-variants', name: 'admin_printful_sync_variants')]
    public function syncVariants(): Response
    {
        $products = null;
        $error    = null;

        try {
            $products = $this->printfulService->getSyncProducts();
        } catch (\Throwable $e) {
            $error = $e->getMessage();
        }

        return $this->render('admin/printful/sync_variants.html.twig', [
            'products' => $products,
            'error'    => $error,
        ]);
    }
}

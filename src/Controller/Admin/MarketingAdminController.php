<?php

namespace App\Controller\Admin;

use Dompdf\Dompdf;
use Dompdf\Options;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\PngWriter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/marketing')]
#[IsGranted('ROLE_ADMIN')]
class MarketingAdminController extends AbstractController
{
    private const SITE_URL = 'https://tedybear.fr';

    #[Route('/affiches', name: 'admin_marketing_affiches')]
    public function index(): Response
    {
        return $this->render('admin/marketing/index.html.twig');
    }

    /**
     * Génère un PDF A4 prêt à imprimer pour affichage en dépôt-vente.
     * ?format=pdf (défaut) renvoie le PDF, ?format=html permet de prévisualiser.
     */
    #[Route('/affiche-depot', name: 'admin_marketing_affiche_depot')]
    public function afficheDepot(Request $request): Response
    {
        $depotNom = $request->query->get('depot', 'Du fromage au dessert');
        $url      = $request->query->get('url', self::SITE_URL);

        $qrDataUri  = $this->generateQrDataUri($url);
        $logoBase64 = $this->fileAsBase64('/public/uploads/general/tdbr-logo.png');
        $heroBase64 = $this->fileAsBase64('/public/build/images/main_page.png');

        $context = [
            'depotNom'   => $depotNom,
            'siteUrl'    => $url,
            'siteLabel'  => preg_replace('#^https?://#', '', $url),
            'qrDataUri'  => $qrDataUri,
            'logoBase64' => $logoBase64,
            'heroBase64' => $heroBase64,
        ];

        if ($request->query->get('format') === 'html') {
            return $this->render('admin/marketing/affiche_depot.html.twig', $context);
        }

        $html = $this->renderView('admin/marketing/affiche_depot.html.twig', $context);

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return new Response($dompdf->output(), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="tdbr-affiche-' . $this->slug($depotNom) . '.pdf"',
        ]);
    }

    private function generateQrDataUri(string $url): string
    {
        $builder = new Builder(
            writer: new PngWriter(),
            data: $url,
            size: 600,
            margin: 0,
            errorCorrectionLevel: ErrorCorrectionLevel::High,
        );

        return $builder->build()->getDataUri();
    }

    private function fileAsBase64(string $relativePath): ?string
    {
        $path = $this->getParameter('kernel.project_dir') . $relativePath;
        if (!is_file($path)) return null;
        $mime = match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png'         => 'image/png',
            'webp'        => 'image/webp',
            'svg'         => 'image/svg+xml',
            default       => 'image/png',
        };
        return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($path));
    }

    private function slug(string $s): string
    {
        $s = mb_strtolower($s, 'UTF-8');
        $s = preg_replace('/[^a-z0-9]+/u', '-', $s);
        return trim($s, '-') ?: 'depot';
    }
}

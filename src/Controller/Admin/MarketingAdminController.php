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

        $context = $this->buildPosterContext($url) + [
            'depotNom' => $depotNom,
        ];

        return $this->renderPoster('admin/marketing/affiche_depot.html.twig', $context, $request, 'tdbr-affiche-' . $this->slug($depotNom));
    }

    /**
     * Affiche TDBR généraliste : présentation de la marque et des thèmes.
     */
    #[Route('/affiche-tdbr', name: 'admin_marketing_affiche_tdbr')]
    public function afficheTdbr(Request $request): Response
    {
        $url     = $request->query->get('url', self::SITE_URL);
        $context = $this->buildPosterContext($url) + [
            'samples' => $this->loadProductSamples(),
        ];

        return $this->renderPoster('admin/marketing/affiche_tdbr.html.twig', $context, $request, 'tdbr-affiche-marque');
    }

    /**
     * Charge les 3 visuels d'illustration thématiques (fromage / fanart / jeux).
     * Les fichiers attendus dans public/uploads/marketing/ :
     *   - sample-fromage.png|jpg|webp
     *   - sample-fanart.png|jpg|webp
     *   - sample-jeux.png|jpg|webp
     * Si un fichier manque, l'entrée correspondante reste null.
     */
    private function loadProductSamples(): array
    {
        $themes = [
            'fromage' => 'Fromage',
            'fanart'  => 'Fanart',
            'jeux'    => 'Jeux de société',
        ];
        $exts = ['png', 'jpg', 'jpeg', 'webp'];
        $base = '/public/uploads/marketing/';

        $samples = [];
        foreach ($themes as $key => $label) {
            $imgB64 = null;
            foreach ($exts as $ext) {
                $candidate = $base . 'sample-' . $key . '.' . $ext;
                $b64 = $this->fileAsBase64($candidate);
                if ($b64 !== null) { $imgB64 = $b64; break; }
            }
            $samples[] = ['label' => $label, 'image' => $imgB64];
        }
        return $samples;
    }

    private function buildPosterContext(string $url): array
    {
        return [
            'siteUrl'    => $url,
            'siteLabel'  => preg_replace('#^https?://#', '', $url),
            'qrDataUri'  => $this->generateQrDataUri($url),
            'logoBase64' => $this->fileAsBase64('/public/build/images/TDBR.png'),
            'heroBase64' => $this->fileAsBase64('/public/build/images/patron.png'),
        ];
    }

    private function renderPoster(string $template, array $context, Request $request, string $filename): Response
    {
        if ($request->query->get('format') === 'html') {
            return $this->render($template, $context);
        }

        $html = $this->renderView($template, $context);

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return new Response($dompdf->output(), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '.pdf"',
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

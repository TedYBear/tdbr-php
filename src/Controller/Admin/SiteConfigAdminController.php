<?php

namespace App\Controller\Admin;

use App\Repository\CodeReductionRepository;
use App\Repository\SiteConfigRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/site-config', name: 'admin_site_config')]
#[IsGranted('ROLE_ADMIN')]
class SiteConfigAdminController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private SiteConfigRepository $siteConfigRepo,
        private CodeReductionRepository $codeReductionRepo,
    ) {
    }

    #[Route('', name: '', methods: ['GET', 'POST'])]
    public function edit(Request $request): Response
    {
        $config = $this->siteConfigRepo->getConfig();

        if ($request->isMethod('POST')) {
            $config->setBannerActive((bool) $request->request->get('bannerActive'));
            $config->setBannerTitre(trim($request->request->get('bannerTitre', '')));
            $config->setBannerTexte(trim($request->request->get('bannerTexte', '')));
            $this->em->flush();
            $this->addFlash('success', 'Configuration du site enregistrée.');
            return $this->redirectToRoute('admin_site_config');
        }

        return $this->render('admin/site_config/edit.html.twig', [
            'config'     => $config,
            'giftCount'  => $this->codeReductionRepo->countCampaignGift(),
        ]);
    }

    #[Route('/gift', name: '_gift', methods: ['POST'])]
    public function editGift(Request $request): Response
    {
        $config = $this->siteConfigRepo->getConfig();

        $config->setGiftActive((bool) $request->request->get('giftActive'));
        $config->setGiftType(in_array($request->request->get('giftType'), ['fixe', 'pourcentage']) ? $request->request->get('giftType') : 'fixe');
        $config->setGiftValue(max(0.01, (float) $request->request->get('giftValue', 5)));
        $config->setGiftMaxBeneficiaires(max(1, (int) $request->request->get('giftMaxBeneficiaires', 10)));
        $this->em->flush();

        $this->addFlash('success', 'Campagne code cadeau enregistrée.');
        return $this->redirectToRoute('admin_site_config');
    }
}

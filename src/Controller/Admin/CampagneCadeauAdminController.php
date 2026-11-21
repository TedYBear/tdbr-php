<?php

namespace App\Controller\Admin;

use App\Entity\CampagneCadeau;
use App\Repository\CampagneCadeauRepository;
use App\Repository\CodeReductionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/campagnes')]
#[IsGranted('ROLE_ADMIN')]
class CampagneCadeauAdminController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private CampagneCadeauRepository $campagneRepo,
        private CodeReductionRepository $codeReductionRepo,
    ) {
    }

    #[Route('', name: 'admin_campagnes', methods: ['GET'])]
    public function index(): Response
    {
        $campagnes = $this->campagneRepo->findBy([], ['createdAt' => 'DESC']);
        $counts = [];
        foreach ($campagnes as $campagne) {
            $counts[$campagne->getId()] = $this->codeReductionRepo->countByCampagne($campagne);
        }

        return $this->render('admin/campagnes/index.html.twig', [
            'campagnes' => $campagnes,
            'counts'    => $counts,
        ]);
    }

    #[Route('/new', name: 'admin_campagnes_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('app', (string) $request->request->get('_token'))) {
                $this->addFlash('error', 'Session expirée, veuillez réessayer.');
                return $this->redirectToRoute('admin_campagnes_new');
            }

            $campagne = new CampagneCadeau();
            $this->hydrateFromRequest($campagne, $request);

            $this->em->persist($campagne);
            $this->em->flush();

            $this->addFlash('success', 'Campagne créée avec succès.');
            return $this->redirectToRoute('admin_campagnes');
        }

        return $this->render('admin/campagnes/form.html.twig', [
            'campagne'     => null,
            'declencheurs' => CampagneCadeau::DECLENCHEURS,
            'validites'    => CampagneCadeau::VALIDITES,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_campagnes_edit', methods: ['GET', 'POST'])]
    public function edit(int $id, Request $request): Response
    {
        $campagne = $this->campagneRepo->find($id);
        if (!$campagne) {
            throw $this->createNotFoundException('Campagne introuvable');
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('app', (string) $request->request->get('_token'))) {
                $this->addFlash('error', 'Session expirée, veuillez réessayer.');
                return $this->redirectToRoute('admin_campagnes_edit', ['id' => $id]);
            }

            $this->hydrateFromRequest($campagne, $request);
            $campagne->setUpdatedAt(new \DateTimeImmutable());
            $this->em->flush();

            $this->addFlash('success', 'Campagne modifiée avec succès.');
            return $this->redirectToRoute('admin_campagnes');
        }

        return $this->render('admin/campagnes/form.html.twig', [
            'campagne'     => $campagne,
            'declencheurs' => CampagneCadeau::DECLENCHEURS,
            'validites'    => CampagneCadeau::VALIDITES,
            'sentCount'    => $this->codeReductionRepo->countByCampagne($campagne),
        ]);
    }

    #[Route('/{id}/toggle', name: 'admin_campagnes_toggle', methods: ['POST'])]
    public function toggle(int $id, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('app', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Session expirée, veuillez réessayer.');
            return $this->redirectToRoute('admin_campagnes');
        }

        $campagne = $this->campagneRepo->find($id);
        if ($campagne) {
            $campagne->setActif(!$campagne->isActif());
            $campagne->setUpdatedAt(new \DateTimeImmutable());
            $this->em->flush();
            $this->addFlash('success', $campagne->isActif() ? 'Campagne activée.' : 'Campagne désactivée.');
        }

        return $this->redirectToRoute('admin_campagnes');
    }

    #[Route('/{id}/delete', name: 'admin_campagnes_delete', methods: ['POST'])]
    public function delete(int $id, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('app', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Session expirée, veuillez réessayer.');
            return $this->redirectToRoute('admin_campagnes');
        }

        $campagne = $this->campagneRepo->find($id);
        if ($campagne) {
            $this->em->remove($campagne);
            $this->em->flush();
            $this->addFlash('success', 'Campagne supprimée (les bons d\'achat déjà distribués restent valides).');
        }

        return $this->redirectToRoute('admin_campagnes');
    }

    private function hydrateFromRequest(CampagneCadeau $campagne, Request $request): void
    {
        $data = $request->request->all();

        $campagne->setNom(trim((string) ($data['nom'] ?? '')) ?: 'Campagne sans nom');
        $campagne->setActif(isset($data['actif']));

        $declencheur = (string) ($data['declencheur'] ?? CampagneCadeau::DECLENCHEUR_PREMIERE_COMMANDE);
        if (!array_key_exists($declencheur, CampagneCadeau::DECLENCHEURS)) {
            $declencheur = CampagneCadeau::DECLENCHEUR_PREMIERE_COMMANDE;
        }
        $campagne->setDeclencheur($declencheur);

        $rawMontantMin = trim((string) ($data['montantMinimum'] ?? ''));
        $campagne->setMontantMinimum($declencheur === CampagneCadeau::DECLENCHEUR_MONTANT_MIN && $rawMontantMin !== '' ? (float) $rawMontantMin : null);

        $rawNbCmd = trim((string) ($data['nombreCommandes'] ?? ''));
        $campagne->setNombreCommandes($declencheur === CampagneCadeau::DECLENCHEUR_NOMBRE_COMMANDES && $rawNbCmd !== '' ? (int) $rawNbCmd : null);

        $rawMax = trim((string) ($data['maxBeneficiaires'] ?? ''));
        $campagne->setMaxBeneficiaires($rawMax !== '' ? (int) $rawMax : null);
        $campagne->setUneFoisParClient(isset($data['uneFoisParClient']));

        $typeReduction = in_array($data['typeReduction'] ?? '', ['fixe', 'pourcentage'], true) ? $data['typeReduction'] : 'fixe';
        $campagne->setTypeReduction($typeReduction);
        $campagne->setValeur(max(0.01, (float) ($data['valeur'] ?? 5)));
        $campagne->setArrondi50Centimes(isset($data['arrondi50Centimes']));
        $campagne->setCodeNom((string) ($data['codeNom'] ?? ''));

        $rawFinAct = trim((string) ($data['dateFinActivation'] ?? ''));
        if ($rawFinAct !== '') {
            try {
                $campagne->setDateFinActivation(new \DateTimeImmutable($rawFinAct . ' 23:59:59'));
            } catch (\Exception $e) {
                $campagne->setDateFinActivation(null);
            }
        } else {
            $campagne->setDateFinActivation(null);
        }

        $typeValidite = (string) ($data['typeValidite'] ?? CampagneCadeau::VALIDITE_AUCUNE);
        if (!array_key_exists($typeValidite, CampagneCadeau::VALIDITES)) {
            $typeValidite = CampagneCadeau::VALIDITE_AUCUNE;
        }
        $campagne->setTypeValidite($typeValidite);

        $rawFinVal = trim((string) ($data['dateFinValiditeFixe'] ?? ''));
        if ($typeValidite === CampagneCadeau::VALIDITE_DATE_FIXE && $rawFinVal !== '') {
            try {
                $campagne->setDateFinValiditeFixe(new \DateTimeImmutable($rawFinVal . ' 23:59:59'));
            } catch (\Exception $e) {
                $campagne->setDateFinValiditeFixe(null);
            }
        } else {
            $campagne->setDateFinValiditeFixe(null);
        }
    }
}

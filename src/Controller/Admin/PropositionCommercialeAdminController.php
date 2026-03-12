<?php

namespace App\Controller\Admin;

use App\Entity\PropositionCommerciale;
use App\Repository\DemandeSurMesureRepository;
use App\Repository\PropositionCommercialeRepository;
use App\Repository\UserRepository;
use App\Service\MailerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/propositions')]
#[IsGranted('ROLE_ADMIN')]
class PropositionCommercialeAdminController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private PropositionCommercialeRepository $propositionRepo,
        private DemandeSurMesureRepository $devisRepo,
        private MailerService $mailer,
        private UserRepository $userRepo,
    ) {}

    #[Route('', name: 'admin_propositions')]
    public function index(): Response
    {
        $propositions = $this->propositionRepo->findBy([], ['createdAt' => 'DESC']);

        return $this->render('admin/propositions/index.html.twig', [
            'propositions' => $propositions,
        ]);
    }

    #[Route('/clients/search', name: 'admin_propositions_clients_search', methods: ['GET'])]
    public function clientsSearch(Request $request): JsonResponse
    {
        $q = trim($request->query->get('q', ''));
        if (strlen($q) < 2) {
            return $this->json([]);
        }

        $users = $this->userRepo->createQueryBuilder('u')
            ->where('LOWER(u.email) LIKE :q OR LOWER(u.nom) LIKE :q OR LOWER(u.prenom) LIKE :q')
            ->setParameter('q', '%' . strtolower($q) . '%')
            ->setMaxResults(10)
            ->orderBy('u.nom', 'ASC')
            ->getQuery()
            ->getResult();

        return $this->json(array_map(fn($u) => [
            'id'       => $u->getId(),
            'email'    => $u->getEmail(),
            'nom'      => $u->getNom() ?? '',
            'prenom'   => $u->getPrenom() ?? '',
            'fullName' => trim(($u->getPrenom() ?? '') . ' ' . ($u->getNom() ?? '')),
        ], $users));
    }

    #[Route('/new', name: 'admin_propositions_new')]
    public function new(Request $request): Response
    {
        $devisId = $request->query->get('devis');
        $devis = $devisId ? $this->devisRepo->find($devisId) : null;

        if ($request->isMethod('POST')) {
            return $this->handleSave(null, $request, $devis);
        }

        return $this->render('admin/propositions/form.html.twig', [
            'proposition' => null,
            'devis' => $devis,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_propositions_edit')]
    public function edit(int $id, Request $request): Response
    {
        $proposition = $this->propositionRepo->find($id);
        if (!$proposition) {
            throw $this->createNotFoundException();
        }

        if ($request->isMethod('POST')) {
            return $this->handleSave($proposition, $request);
        }

        return $this->render('admin/propositions/form.html.twig', [
            'proposition' => $proposition,
            'devis' => $proposition->getDemandeSurMesure(),
        ]);
    }

    #[Route('/{id}/send', name: 'admin_propositions_send', methods: ['POST'])]
    public function send(int $id): Response
    {
        $proposition = $this->propositionRepo->find($id);
        if (!$proposition) {
            throw $this->createNotFoundException();
        }

        try {
            $this->mailer->sendProposition($proposition);
            $proposition->setStatut('envoyee');
            $proposition->setUpdatedAt(new \DateTimeImmutable());
            $this->em->flush();
            $this->addFlash('success', 'Proposition envoyée à ' . $proposition->getClientEmail());
        } catch (\Throwable $e) {
            $this->addFlash('error', 'Erreur lors de l\'envoi : ' . $e->getMessage());
        }

        return $this->redirectToRoute('admin_propositions_edit', ['id' => $id]);
    }

    #[Route('/{id}/marquer-payee', name: 'admin_propositions_marquer_payee', methods: ['POST'])]
    public function marquerPayee(int $id): Response
    {
        $proposition = $this->propositionRepo->find($id);
        if (!$proposition || $proposition->getStatut() !== 'en_attente_virement') {
            $this->addFlash('error', 'Cette proposition ne peut pas être marquée comme payée.');
            return $this->redirectToRoute('admin_propositions_edit', ['id' => $id]);
        }

        $proposition->setStatut('payee');
        $proposition->setUpdatedAt(new \DateTimeImmutable());

        $commande = $proposition->getCommande();
        if ($commande) {
            $commande->setStatut('payee');
            $commande->setUpdatedAt(new \DateTimeImmutable());
        }

        $this->em->flush();

        if ($commande) {
            try {
                $this->mailer->sendOrderConfirmation($commande);
            } catch (\Throwable $e) {
                // non-bloquant
            }
        }

        $this->addFlash('success', 'Proposition marquée comme payée, confirmation envoyée au client.');
        return $this->redirectToRoute('admin_propositions_edit', ['id' => $id]);
    }

    #[Route('/{id}/facture', name: 'admin_propositions_facture')]
    public function facture(int $id): Response
    {
        $proposition = $this->propositionRepo->find($id);

        if (!$proposition || $proposition->getStatut() !== 'payee' || !$proposition->getCommande()) {
            throw $this->createNotFoundException('Facture non disponible');
        }

        return $this->render('commandes/facture.html.twig', [
            'commande' => $proposition->getCommande(),
        ]);
    }

    #[Route('/{id}/statut', name: 'admin_propositions_statut', methods: ['POST'])]
    public function changeStatut(int $id, Request $request): Response
    {
        $proposition = $this->propositionRepo->find($id);
        if (!$proposition) {
            throw $this->createNotFoundException();
        }

        $statuts = ['brouillon', 'envoyee', 'acceptee', 'en_attente_virement', 'payee'];
        $newStatut = $request->request->get('statut');

        if (in_array($newStatut, $statuts)) {
            $proposition->setStatut($newStatut);
            $proposition->setUpdatedAt(new \DateTimeImmutable());
            $this->em->flush();
            $this->addFlash('success', 'Statut mis à jour.');
        }

        return $this->redirectToRoute('admin_propositions');
    }

    #[Route('/{id}/delete', name: 'admin_propositions_delete', methods: ['POST'])]
    public function delete(int $id): Response
    {
        $proposition = $this->propositionRepo->find($id);
        if ($proposition) {
            $this->em->remove($proposition);
            $this->em->flush();
            $this->addFlash('success', 'Proposition supprimée.');
        }

        return $this->redirectToRoute('admin_propositions');
    }

    private function handleSave(?PropositionCommerciale $proposition, Request $request, $devis = null): Response
    {
        $data = $request->request->all();
        $isNew = $proposition === null;

        if ($isNew) {
            $proposition = new PropositionCommerciale();
            if ($devis) {
                $proposition->setDemandeSurMesure($devis);
                if (!isset($data['clientEmail']) || empty($data['clientEmail'])) {
                    $data['clientEmail'] = $devis->getEmail();
                }
                if (empty($data['clientNom'])) {
                    $data['clientNom'] = $devis->getNom();
                }
            }
        }

        $proposition->setDescription($data['description'] ?? '');
        $proposition->setMessagePersonnel(!empty($data['messagePersonnel']) ? trim($data['messagePersonnel']) : null);
        $proposition->setCoutDesign(!empty($data['coutDesign']) ? (float)$data['coutDesign'] : null);
        $proposition->setPrixPublic((float)($data['prixPublic'] ?? 0));
        $proposition->setFraisManutention(!empty($data['fraisManutention']) ? (float)$data['fraisManutention'] : null);
        $proposition->setRistourne(!empty($data['ristourne']) ? (float)$data['ristourne'] : null);
        $proposition->setPrixTotal($proposition->computePrixTotal());
        $proposition->setClientEmail(trim($data['clientEmail'] ?? ''));
        $proposition->setClientNom(!empty($data['clientNom']) ? trim($data['clientNom']) : null);

        if ($isNew) {
            $this->em->persist($proposition);
        } else {
            $proposition->setUpdatedAt(new \DateTimeImmutable());
        }

        $action = $data['action'] ?? 'save';

        if ($action === 'send') {
            $proposition->setStatut('envoyee');
            $this->em->flush();
            try {
                $this->mailer->sendProposition($proposition);
                $this->addFlash('success', 'Proposition envoyée à ' . $proposition->getClientEmail());
            } catch (\Throwable $e) {
                $proposition->setStatut('brouillon');
                $this->em->flush();
                $this->addFlash('error', 'Erreur lors de l\'envoi : ' . $e->getMessage());
            }
        } else {
            $this->em->flush();
            $this->addFlash('success', 'Proposition enregistrée en brouillon.');
        }

        return $this->redirectToRoute('admin_propositions_edit', ['id' => $proposition->getId()]);
    }
}

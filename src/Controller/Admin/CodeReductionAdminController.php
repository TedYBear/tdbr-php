<?php

namespace App\Controller\Admin;

use App\Entity\CodeReduction;
use App\Repository\CodeReductionRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/codes-reduction')]
#[IsGranted('ROLE_ADMIN')]
class CodeReductionAdminController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private CodeReductionRepository $codeRepo,
        private UserRepository $userRepo,
    ) {
    }

    #[Route('', name: 'admin_codes_reduction')]
    public function index(Request $request): Response
    {
        $page = max(1, $request->query->getInt('page', 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $codes = $this->codeRepo->findBy([], ['createdAt' => 'DESC'], $limit, $offset);
        $total = $this->codeRepo->count([]);
        $totalPages = (int) ceil($total / $limit);

        return $this->render('admin/code_reduction/index.html.twig', [
            'codes'       => $codes,
            'total'       => $total,
            'currentPage' => $page,
            'totalPages'  => $totalPages,
        ]);
    }

    #[Route('/new', name: 'admin_codes_reduction_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->handleForm($request, new CodeReduction());
        }

        return $this->render('admin/code_reduction/form.html.twig', [
            'code'  => null,
            'users' => $this->userRepo->findBy([], ['email' => 'ASC']),
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_codes_reduction_edit', methods: ['GET', 'POST'])]
    public function edit(int $id, Request $request): Response
    {
        $code = $this->codeRepo->find($id);

        if (!$code) {
            throw $this->createNotFoundException('Code introuvable');
        }

        if ($request->isMethod('POST')) {
            return $this->handleForm($request, $code);
        }

        return $this->render('admin/code_reduction/form.html.twig', [
            'code'  => $code,
            'users' => $this->userRepo->findBy([], ['email' => 'ASC']),
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_codes_reduction_delete', methods: ['POST'])]
    public function delete(int $id): Response
    {
        $code = $this->codeRepo->find($id);

        if ($code && $code->getStatut() === 'actif') {
            $this->em->remove($code);
            $this->em->flush();
            $this->addFlash('success', 'Code supprimé');
        } else {
            $this->addFlash('error', 'Impossible de supprimer un code déjà utilisé');
        }

        return $this->redirectToRoute('admin_codes_reduction');
    }

    private function handleForm(Request $request, CodeReduction $code): Response
    {
        $nom       = trim($request->request->get('code', ''));
        $montant   = (float) str_replace(',', '.', $request->request->get('montant', '0'));
        $statut    = $request->request->get('statut', 'actif');
        $type      = $request->request->get('type', 'global');
        $userId    = (int) $request->request->get('userId');
        $dateDebut = $request->request->get('dateDebut', '');
        $dateExp   = $request->request->get('dateExpiration', '');

        if (!$nom || $montant <= 0) {
            $this->addFlash('error', 'Code et montant sont requis');
            return $this->redirectToRoute(
                $code->getId() ? 'admin_codes_reduction_edit' : 'admin_codes_reduction_new',
                $code->getId() ? ['id' => $code->getId()] : []
            );
        }

        // Utilisateur : null si code global
        $user = null;
        if ($type === 'user' && $userId) {
            $user = $this->userRepo->find($userId);
            if (!$user) {
                $this->addFlash('error', 'Utilisateur introuvable');
                return $this->redirectToRoute('admin_codes_reduction_new');
            }
        }

        $code->setCode(strtoupper($nom));
        $code->setMontant($montant);
        $code->setStatut(in_array($statut, ['actif', 'utilise']) ? $statut : 'actif');
        $code->setUser($user);
        $code->setDateDebut(
            $dateDebut ? \DateTimeImmutable::createFromFormat('Y-m-d', $dateDebut) ?: null : null
        );
        $code->setDateExpiration(
            $dateExp ? \DateTimeImmutable::createFromFormat('Y-m-d', $dateExp) ?: null : null
        );

        $this->em->persist($code);
        $this->em->flush();

        $this->addFlash('success', $code->getId() ? 'Code mis à jour' : 'Code créé');
        return $this->redirectToRoute('admin_codes_reduction');
    }
}

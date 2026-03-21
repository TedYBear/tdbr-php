<?php

namespace App\Controller\Admin;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/utilisateurs', name: 'admin_utilisateurs')]
#[IsGranted('ROLE_ADMIN')]
class UserAdminController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepo,
        private EntityManagerInterface $em,
    ) {}

    #[Route('', name: '', methods: ['GET'])]
    public function index(): Response
    {
        $rows = $this->userRepo->findAllWithCommandeCount();

        return $this->render('admin/users/index.html.twig', [
            'rows'  => $rows,
            'total' => count($rows),
        ]);
    }

    #[Route('/{id}/toggle-admin', name: '_toggle_admin', methods: ['POST'])]
    public function toggleAdmin(int $id): Response
    {
        $user = $this->userRepo->find($id);
        if (!$user) {
            throw $this->createNotFoundException();
        }

        /** @var \App\Entity\User $me */
        $me = $this->getUser();
        if ($me->getId() === $user->getId()) {
            $this->addFlash('error', 'Vous ne pouvez pas modifier votre propre rôle.');
            return $this->redirectToRoute('admin_utilisateurs');
        }

        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            $user->setRoles([]);
            $this->addFlash('success', $user->getPrenom() . ' ' . $user->getNom() . ' n\'est plus administrateur.');
        } else {
            $user->setRoles(['ROLE_ADMIN']);
            $this->addFlash('success', $user->getPrenom() . ' ' . $user->getNom() . ' est maintenant administrateur.');
        }

        $this->em->flush();
        return $this->redirectToRoute('admin_utilisateurs');
    }

    #[Route('/{id}/toggle-depot-vente', name: '_toggle_depot_vente', methods: ['POST'])]
    public function toggleDepotVente(int $id): Response
    {
        $user = $this->userRepo->find($id);
        if (!$user) {
            throw $this->createNotFoundException();
        }

        /** @var \App\Entity\User $me */
        $me = $this->getUser();
        if ($me->getId() === $user->getId()) {
            $this->addFlash('error', 'Vous ne pouvez pas modifier votre propre rôle.');
            return $this->redirectToRoute('admin_utilisateurs');
        }

        if (in_array('ROLE_DEPOT_VENTE', $user->getRoles())) {
            $user->setRoles([]);
            $this->addFlash('success', $user->getPrenom() . ' ' . $user->getNom() . ' n\'est plus dépôt-vente.');
        } else {
            $user->setRoles(['ROLE_DEPOT_VENTE']);
            $this->addFlash('success', $user->getPrenom() . ' ' . $user->getNom() . ' est maintenant dépôt-vente.');
        }

        $this->em->flush();
        return $this->redirectToRoute('admin_utilisateurs');
    }

    #[Route('/{id}/delete', name: '_delete', methods: ['POST'])]
    public function delete(int $id): Response
    {
        $user = $this->userRepo->find($id);
        if (!$user) {
            $this->addFlash('error', 'Utilisateur introuvable.');
            return $this->redirectToRoute('admin_utilisateurs');
        }

        /** @var \App\Entity\User $me */
        $me = $this->getUser();
        if ($me->getId() === $user->getId()) {
            $this->addFlash('error', 'Vous ne pouvez pas supprimer votre propre compte.');
            return $this->redirectToRoute('admin_utilisateurs');
        }

        $nom = $user->getPrenom() . ' ' . $user->getNom();
        $this->em->remove($user);
        $this->em->flush();

        $this->addFlash('success', 'Utilisateur ' . $nom . ' supprimé.');
        return $this->redirectToRoute('admin_utilisateurs');
    }
}

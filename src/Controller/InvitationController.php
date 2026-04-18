<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class InvitationController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepo,
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $hasher,
    ) {}

    #[Route('/invitation/{token}', name: 'invitation_set_password', methods: ['GET', 'POST'])]
    public function setPassword(string $token, Request $request): Response
    {
        $user = $this->userRepo->findOneBy(['inviteToken' => $token]);

        if (!$user || !$user->isInviteTokenValid()) {
            $this->addFlash('error', 'Ce lien d\'invitation est invalide ou a expiré.');
            return $this->redirectToRoute('connexion');
        }

        if ($request->isMethod('POST')) {
            $password  = $request->request->get('password', '');
            $password2 = $request->request->get('password2', '');

            if (strlen($password) < 8) {
                $this->addFlash('error', 'Le mot de passe doit contenir au moins 8 caractères.');
                return $this->redirectToRoute('invitation_set_password', ['token' => $token]);
            }

            if ($password !== $password2) {
                $this->addFlash('error', 'Les deux mots de passe ne correspondent pas.');
                return $this->redirectToRoute('invitation_set_password', ['token' => $token]);
            }

            $user->setPassword($this->hasher->hashPassword($user, $password));
            $user->setInviteToken(null);
            $user->setInviteTokenExpiresAt(null);
            $this->em->flush();

            $this->addFlash('success', 'Votre mot de passe a été défini. Vous pouvez maintenant vous connecter.');
            return $this->redirectToRoute('connexion');
        }

        return $this->render('auth/invitation.html.twig', [
            'user'  => $user,
            'token' => $token,
        ]);
    }
}

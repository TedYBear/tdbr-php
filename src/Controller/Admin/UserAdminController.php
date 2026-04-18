<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Repository\CommandeRepository;
use App\Repository\DemandeSurMesureRepository;
use App\Repository\MessageRepository;
use App\Repository\PropositionCommercialeRepository;
use App\Repository\UserRepository;
use App\Service\MailerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/utilisateurs', name: 'admin_utilisateurs')]
#[IsGranted('ROLE_ADMIN')]
class UserAdminController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepo,
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $hasher,
        private MailerService $mailer,
        private CommandeRepository $commandeRepo,
        private PropositionCommercialeRepository $propositionRepo,
        private DemandeSurMesureRepository $demandeRepo,
        private MessageRepository $messageRepo,
    ) {}

    #[Route('/creer', name: '_create', methods: ['GET', 'POST'])]
    public function create(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $prenom = trim($request->request->get('prenom', ''));
            $nom    = trim($request->request->get('nom', ''));
            $email  = trim($request->request->get('email', ''));

            if (!$prenom || !$nom || !$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->addFlash('error', 'Tous les champs sont obligatoires et l\'email doit être valide.');
                return $this->redirectToRoute('admin_utilisateurs_create');
            }

            if ($this->userRepo->findOneBy(['email' => $email])) {
                $this->addFlash('error', 'Un compte existe déjà avec cet email.');
                return $this->redirectToRoute('admin_utilisateurs_create');
            }

            $token = bin2hex(random_bytes(32));

            $user = new User();
            $user->setPrenom($prenom);
            $user->setNom($nom);
            $user->setEmail($email);
            $user->setPassword($this->hasher->hashPassword($user, bin2hex(random_bytes(16))));
            $user->setInviteToken($token);
            $user->setInviteTokenExpiresAt(new \DateTimeImmutable('+7 days'));

            $this->em->persist($user);
            $this->em->flush();

            $inviteUrl = $this->generateUrl('invitation_set_password', ['token' => $token], UrlGeneratorInterface::ABSOLUTE_URL);

            try {
                $this->mailer->sendInvitation($user, $inviteUrl);
                $this->addFlash('success', 'Compte créé et invitation envoyée à ' . $email . '.');
            } catch (\Exception $e) {
                $this->addFlash('warning', 'Compte créé mais l\'email n\'a pas pu être envoyé : ' . $e->getMessage());
            }

            return $this->redirectToRoute('admin_utilisateurs');
        }

        return $this->render('admin/users/create.html.twig');
    }

    #[Route('', name: '', methods: ['GET'])]
    public function index(): Response
    {
        $rows = $this->userRepo->findAllWithCommandeCount();

        return $this->render('admin/users/index.html.twig', [
            'rows'  => $rows,
            'total' => count($rows),
        ]);
    }

    #[Route('/{id}/rattacher', name: '_rattacher', methods: ['GET', 'POST'])]
    public function rattacher(int $id, Request $request): Response
    {
        $user = $this->userRepo->find($id);
        if (!$user) {
            throw $this->createNotFoundException();
        }

        $email = $user->getEmail();

        if ($request->isMethod('POST')) {
            $linked = 0;

            // Rattacher les commandes sélectionnées
            foreach ($request->request->all('commandes') as $commandeId) {
                $commande = $this->commandeRepo->find((int)$commandeId);
                if ($commande && $commande->getUser() === null) {
                    $commande->setUser($user);
                    $linked++;
                }
            }

            // Rattacher les propositions sélectionnées
            foreach ($request->request->all('propositions') as $propId) {
                $prop = $this->propositionRepo->find((int)$propId);
                if ($prop && $prop->getUser() === null) {
                    $prop->setUser($user);
                    $linked++;
                }
            }

            $this->em->flush();
            $this->addFlash('success', $linked . ' élément(s) rattaché(s) au compte de ' . $user->getPrenom() . ' ' . $user->getNom() . '.');
            return $this->redirectToRoute('admin_utilisateurs_rattacher', ['id' => $id]);
        }

        return $this->render('admin/users/rattacher.html.twig', [
            'user'           => $user,
            'commandes'      => $this->commandeRepo->findAllByEmail($email),
            'propositions'   => $this->propositionRepo->findAllByEmail($email),
            'demandes'       => $this->demandeRepo->findBy(['email' => $email], ['createdAt' => 'DESC']),
            'messages'       => $this->messageRepo->findBy(['email' => $email], ['createdAt' => 'DESC']),
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

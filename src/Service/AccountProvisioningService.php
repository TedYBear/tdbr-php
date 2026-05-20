<?php

namespace App\Service;

use App\Entity\Commande;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Rattache une commande invité à un compte client.
 *
 * Idempotent : si la commande est déjà liée à un utilisateur, ne fait rien.
 * Si un compte existe déjà pour l'email, la commande lui est rattachée.
 * Sinon un compte est créé et une invitation (lien de définition du mot de
 * passe) est envoyée au client.
 */
class AccountProvisioningService
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserRepository $userRepo,
        private UserPasswordHasherInterface $hasher,
        private MailerService $mailerService,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public const RESULT_ALREADY_LINKED = 'already_linked';
    public const RESULT_INVALID_EMAIL  = 'invalid_email';
    public const RESULT_LINKED_EXISTING = 'linked_existing';
    public const RESULT_CREATED         = 'created';

    public function ensureAccountForCommande(Commande $commande): string
    {
        if ($commande->getUser() !== null) {
            return self::RESULT_ALREADY_LINKED;
        }

        $client = $commande->getClient();
        $email  = strtolower(trim($client['email'] ?? ''));

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return self::RESULT_INVALID_EMAIL;
        }

        $existing = $this->userRepo->findOneBy(['email' => $email]);
        if ($existing instanceof User) {
            $commande->setUser($existing);
            $this->em->flush();
            return self::RESULT_LINKED_EXISTING;
        }

        $token = bin2hex(random_bytes(32));

        $user = new User();
        $user->setEmail($email);
        $user->setPrenom($client['prenom'] ?? null);
        $user->setNom($client['nom'] ?? null);
        $user->setTelephone($client['telephone'] ?? null);
        $user->setPassword($this->hasher->hashPassword($user, bin2hex(random_bytes(16))));
        $user->setInviteToken($token);
        $user->setInviteTokenExpiresAt(new \DateTimeImmutable('+7 days'));

        $this->em->persist($user);
        $commande->setUser($user);
        $this->em->flush();

        $inviteUrl = $this->urlGenerator->generate(
            'invitation_set_password',
            ['token' => $token],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        try {
            $this->mailerService->sendInvitation($user, $inviteUrl);
        } catch (\Throwable $e) {
        }
    }
}

<?php

namespace App\Security;

use App\Entity\User;
use App\Service\MongoDBService;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class MongoDBUserProvider implements UserProviderInterface, PasswordUpgraderInterface
{
    public function __construct(
        private MongoDBService $mongoService
    ) {
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $collection = $this->mongoService->getCollection('utilisateurs');
        $userData = $collection->findOne(['email' => $identifier]);

        if (!$userData) {
            throw new UserNotFoundException(sprintf('User "%s" not found.', $identifier));
        }

        return new User(
            (string) $userData['_id'],
            $userData['email'],
            $userData['password'],
            $userData['roles'] ?? ['ROLE_USER'],
            $userData['prenom'] ?? null,
            $userData['nom'] ?? null,
            $userData['telephone'] ?? null
        );
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Invalid user class "%s".', get_class($user)));
        }

        return $this->loadUserByIdentifier($user->getUserIdentifier());
    }

    public function supportsClass(string $class): bool
    {
        return User::class === $class || is_subclass_of($class, User::class);
    }

    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Invalid user class "%s".', get_class($user)));
        }

        $collection = $this->mongoService->getCollection('utilisateurs');
        $collection->updateOne(
            ['email' => $user->getUserIdentifier()],
            ['$set' => ['password' => $newHashedPassword]]
        );
    }
}

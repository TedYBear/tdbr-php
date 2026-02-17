<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-admin',
    description: 'Crée un utilisateur administrateur',
)]
class CreateAdminCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserRepository $userRepo,
        private UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('email', null, InputOption::VALUE_REQUIRED, 'Email de l\'admin')
            ->addOption('password', null, InputOption::VALUE_REQUIRED, 'Mot de passe')
            ->addOption('prenom', null, InputOption::VALUE_REQUIRED, 'Prénom')
            ->addOption('nom', null, InputOption::VALUE_REQUIRED, 'Nom')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $email = $input->getOption('email') ?? $io->ask('Email de l\'administrateur', 'admin@tdbr.fr');
        $password = $input->getOption('password') ?? $io->askHidden('Mot de passe (au moins 6 caractères)');

        if (strlen($password) < 6) {
            $io->error('Le mot de passe doit contenir au moins 6 caractères');
            return Command::FAILURE;
        }

        $prenom = $input->getOption('prenom') ?? $io->ask('Prénom', 'Admin');
        $nom = $input->getOption('nom') ?? $io->ask('Nom', 'TDBR');

        $existing = $this->userRepo->findOneBy(['email' => $email]);

        if ($existing) {
            $existing->setRoles(['ROLE_ADMIN']);
            $this->em->flush();
            $io->success(sprintf('L\'utilisateur %s est maintenant administrateur', $email));
            return Command::SUCCESS;
        }

        $user = new User();
        $user->setEmail($email);
        $user->setPrenom($prenom);
        $user->setNom($nom);
        $user->setRoles(['ROLE_ADMIN']);
        $user->setPassword($this->passwordHasher->hashPassword($user, $password));

        $this->em->persist($user);
        $this->em->flush();

        $io->success(sprintf(
            'Administrateur créé avec succès !%sEmail: %s%sID: %s',
            PHP_EOL, $email, PHP_EOL, $user->getId()
        ));
        return Command::SUCCESS;
    }
}

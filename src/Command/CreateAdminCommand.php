<?php

namespace App\Command;

use App\Service\MongoDBService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:create-admin',
    description: 'Crée un utilisateur administrateur',
)]
class CreateAdminCommand extends Command
{
    private MongoDBService $mongoService;

    public function __construct(MongoDBService $mongoService)
    {
        parent::__construct();
        $this->mongoService = $mongoService;
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

        // Récupérer ou demander les informations
        $email = $input->getOption('email');
        if (!$email) {
            $email = $io->ask('Email de l\'administrateur', 'admin@tdbr.fr');
        }

        $password = $input->getOption('password');
        if (!$password) {
            $password = $io->askHidden('Mot de passe (au moins 6 caractères)');
        }

        if (strlen($password) < 6) {
            $io->error('Le mot de passe doit contenir au moins 6 caractères');
            return Command::FAILURE;
        }

        $prenom = $input->getOption('prenom');
        if (!$prenom) {
            $prenom = $io->ask('Prénom', 'Admin');
        }

        $nom = $input->getOption('nom');
        if (!$nom) {
            $nom = $io->ask('Nom', 'TDBR');
        }

        // Vérifier que l'email n'existe pas déjà
        $collection = $this->mongoService->getCollection('users');
        $existing = $collection->findOne(['email' => $email]);

        if ($existing) {
            // Mettre à jour le rôle en admin
            $collection->updateOne(
                ['email' => $email],
                ['$set' => ['role' => 'admin']]
            );
            $io->success(sprintf('L\'utilisateur %s est maintenant administrateur', $email));
            return Command::SUCCESS;
        }

        // Créer le nouvel admin
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        $userData = [
            'email' => $email,
            'password' => $hashedPassword,
            'prenom' => $prenom,
            'nom' => $nom,
            'telephone' => '',
            'adresse' => [
                'rue' => '',
                'ville' => '',
                'codePostal' => '',
                'pays' => 'France'
            ],
            'role' => 'admin',
            'actif' => true,
            'createdAt' => new \MongoDB\BSON\UTCDateTime(),
            'updatedAt' => new \MongoDB\BSON\UTCDateTime()
        ];

        $result = $collection->insertOne($userData);

        if ($result->getInsertedCount() > 0) {
            $io->success(sprintf(
                'Administrateur créé avec succès !%sEmail: %s%sID: %s',
                PHP_EOL,
                $email,
                PHP_EOL,
                $result->getInsertedId()
            ));
            return Command::SUCCESS;
        }

        $io->error('Échec de la création de l\'administrateur');
        return Command::FAILURE;
    }
}

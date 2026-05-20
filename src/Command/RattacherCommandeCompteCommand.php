<?php

namespace App\Command;

use App\Repository\CommandeRepository;
use App\Service\AccountProvisioningService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:commande:rattacher-compte',
    description: 'Crée ou rattache un compte client pour une commande invité (par numéro).',
)]
class RattacherCommandeCompteCommand extends Command
{
    public function __construct(
        private CommandeRepository $commandeRepo,
        private AccountProvisioningService $accountProvisioning,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('numero', InputArgument::REQUIRED, 'Numéro de commande (avec ou sans préfixe CMD-)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io     = new SymfonyStyle($input, $output);
        $numero = trim($input->getArgument('numero'));

        $commande = $this->commandeRepo->findOneBy(['numero' => $numero]);
        if (!$commande && !str_starts_with($numero, 'CMD-')) {
            $commande = $this->commandeRepo->findOneBy(['numero' => 'CMD-' . $numero]);
        }

        if (!$commande) {
            $io->error(sprintf('Aucune commande trouvée pour le numéro "%s".', $numero));
            return Command::FAILURE;
        }

        $client = $commande->getClient();
        $io->section('Commande ' . $commande->getNumero());
        $io->listing([
            'Client : ' . trim(($client['prenom'] ?? '') . ' ' . ($client['nom'] ?? '')),
            'Email  : ' . ($client['email'] ?? '(aucun)'),
            'Statut : ' . $commande->getStatut(),
            'Compte actuel : ' . ($commande->getUser() ? $commande->getUser()->getEmail() : 'aucun (invité)'),
        ]);

        $result = $this->accountProvisioning->ensureAccountForCommande($commande);

        switch ($result) {
            case AccountProvisioningService::RESULT_ALREADY_LINKED:
                $io->warning('La commande est déjà rattachée à un compte. Aucune action.');
                break;
            case AccountProvisioningService::RESULT_INVALID_EMAIL:
                $io->error('Email client absent ou invalide. Impossible de créer un compte.');
                return Command::FAILURE;
            case AccountProvisioningService::RESULT_LINKED_EXISTING:
                $io->success('Un compte existait déjà pour cet email : la commande lui a été rattachée (pas d\'email envoyé).');
                break;
            case AccountProvisioningService::RESULT_CREATED:
                $io->success('Compte créé et email d\'invitation envoyé au client.');
                break;
        }

        return Command::SUCCESS;
    }
}

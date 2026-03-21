<?php

namespace App\Command;

use App\Repository\DepotVenteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'tdbr:depot-vente:reset-stock',
    description: 'Remet à zéro le stock d\'un ou tous les dépôts-vente',
)]
class ResetDepotVenteStockCommand extends Command
{
    public function __construct(
        private DepotVenteRepository $depotRepo,
        private EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('id', InputArgument::OPTIONAL, 'ID du dépôt-vente (tous si omis)')
            ->addOption('yes', 'y', InputOption::VALUE_NONE, 'Confirmer sans prompt');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $id = $input->getArgument('id');

        if ($id) {
            $depot = $this->depotRepo->find((int)$id);
            if (!$depot) {
                $io->error("Aucun dépôt-vente avec l'ID {$id}.");
                return Command::FAILURE;
            }
            $depots = [$depot];
            $cible = "« {$depot->getNom()} » (ID {$id})";
        } else {
            $depots = $this->depotRepo->findAll();
            $cible = 'TOUS les dépôts-vente (' . count($depots) . ')';
        }

        $io->warning("Cette commande va remettre à zéro le stock de {$cible}.");

        if (!$input->getOption('yes') && !$io->confirm('Confirmer ?', false)) {
            $io->note('Annulé.');
            return Command::SUCCESS;
        }

        $total = 0;
        foreach ($depots as $depot) {
            foreach ($depot->getStockItems() as $item) {
                $item->setQuantite(0);
                $total++;
            }
        }

        $this->em->flush();

        $io->success("{$total} ligne(s) de stock remise(s) à zéro.");
        return Command::SUCCESS;
    }
}

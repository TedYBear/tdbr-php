<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260303010000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Renomme la table devis en demandes_sur_mesure';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('RENAME TABLE devis TO demandes_sur_mesure');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('RENAME TABLE demandes_sur_mesure TO devis');
    }
}

<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260312010000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'PropositionCommerciale : décomposition en 4 lignes (coutDesign, prixPublic, fraisManutention, ristourne)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE propositions_commerciales
            ADD cout_design DECIMAL(10,2) DEFAULT NULL,
            ADD prix_public DECIMAL(10,2) NOT NULL DEFAULT 0,
            ADD frais_manutention DECIMAL(10,2) DEFAULT NULL,
            ADD ristourne DECIMAL(10,2) DEFAULT NULL');
        // Migrer les données existantes : prixTotal → prixPublic
        $this->addSql('UPDATE propositions_commerciales SET prix_public = prix_total WHERE prix_public = 0');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE propositions_commerciales
            DROP COLUMN cout_design,
            DROP COLUMN prix_public,
            DROP COLUMN frais_manutention,
            DROP COLUMN ristourne');
    }
}

<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260302040000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute frais_vistaprint_domicile à site_config (défaut 5 €)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE site_config ADD frais_vistaprint_domicile DECIMAL(10,2) NOT NULL DEFAULT 5.00');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE site_config DROP COLUMN frais_vistaprint_domicile');
    }
}

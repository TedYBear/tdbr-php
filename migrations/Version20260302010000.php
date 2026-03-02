<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260302010000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remplace le prix fixe des variantes par un delta de prix (positif ou négatif)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE variantes CHANGE prix delta_prix DECIMAL(10, 2) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE variantes CHANGE delta_prix prix DECIMAL(10, 2) DEFAULT NULL');
    }
}

<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260302020000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Initialise delta_prix à 0 pour toutes les variantes existantes';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE variantes SET delta_prix = 0 WHERE delta_prix IS NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('UPDATE variantes SET delta_prix = NULL WHERE delta_prix = 0');
    }
}

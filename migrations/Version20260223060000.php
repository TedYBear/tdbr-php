<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260223060000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute la colonne paliers (4 tranches tarifaires) à grilles_prix';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE grilles_prix ADD COLUMN paliers JSON NULL');
        $this->addSql("UPDATE grilles_prix SET paliers = '[]' WHERE paliers IS NULL");
        $this->addSql('ALTER TABLE grilles_prix MODIFY paliers JSON NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE grilles_prix DROP COLUMN paliers');
    }
}

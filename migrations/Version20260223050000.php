<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260223050000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Supprime prix_fournisseur de articles (remplacé par grille_prix_id)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE articles DROP COLUMN prix_fournisseur');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE articles ADD COLUMN prix_fournisseur DECIMAL(10,2) NULL');
    }
}

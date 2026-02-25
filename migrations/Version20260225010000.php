<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260225010000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute mode_livraison sur commandes';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE commandes ADD mode_livraison JSON NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE commandes DROP COLUMN mode_livraison');
    }
}

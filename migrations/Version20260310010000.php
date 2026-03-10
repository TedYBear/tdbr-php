<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260310010000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Déplacement de la clé API Printful vers .env — suppression de la colonne fournisseurs.printful_api_key';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE fournisseurs DROP COLUMN printful_api_key');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE fournisseurs ADD printful_api_key VARCHAR(255) DEFAULT NULL');
    }
}

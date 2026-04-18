<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260418020000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute facture_sent_at sur commandes pour tracker l\'envoi de la facture acquittée';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE commandes ADD facture_sent_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE commandes DROP COLUMN facture_sent_at');
    }
}

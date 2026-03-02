<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260302050000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute gift_reset_at à site_config pour réinitialiser le compteur de bénéficiaires sans supprimer les codes';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE site_config ADD gift_reset_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE site_config DROP COLUMN gift_reset_at');
    }
}

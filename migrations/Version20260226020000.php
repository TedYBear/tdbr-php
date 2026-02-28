<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260226020000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Création de la table site_config pour le bandeau paramétrable';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE site_config (
            id INT AUTO_INCREMENT NOT NULL,
            banner_active TINYINT(1) NOT NULL DEFAULT 1,
            banner_titre VARCHAR(100) NOT NULL DEFAULT \'Site en construction\',
            banner_texte VARCHAR(500) NOT NULL DEFAULT \'Les articles et prix sont réels — la commande en ligne n\'\'est pas encore disponible.\',
            PRIMARY KEY (id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('INSERT INTO site_config (banner_active, banner_titre, banner_texte) VALUES (1, \'Site en construction\', \'Les articles et prix sont réels — la commande en ligne n\'\'est pas encore disponible.\')');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE site_config');
    }
}

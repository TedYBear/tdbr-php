<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260219000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout de la table devis';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE devis (
            id INT AUTO_INCREMENT NOT NULL,
            nom VARCHAR(200) NOT NULL,
            email VARCHAR(200) NOT NULL,
            telephone VARCHAR(20) DEFAULT NULL,
            concept LONGTEXT NOT NULL,
            contexte LONGTEXT DEFAULT NULL,
            supports JSON NOT NULL,
            autre_support VARCHAR(200) DEFAULT NULL,
            quantite VARCHAR(50) NOT NULL,
            moyen_contact VARCHAR(50) NOT NULL,
            message_additionnel LONGTEXT DEFAULT NULL,
            statut VARCHAR(50) NOT NULL DEFAULT \'nouveau\',
            notes_admin LONGTEXT DEFAULT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS devis');
    }
}

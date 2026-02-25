<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260225030000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Création de la table boutiques_relais';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE boutiques_relais (
            id INT AUTO_INCREMENT NOT NULL,
            nom VARCHAR(255) NOT NULL,
            adresse VARCHAR(255) NOT NULL,
            complement_adresse VARCHAR(255) DEFAULT NULL,
            code_postal VARCHAR(10) NOT NULL,
            ville VARCHAR(100) NOT NULL,
            telephone VARCHAR(20) DEFAULT NULL,
            email VARCHAR(255) DEFAULT NULL,
            actif TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
            PRIMARY KEY (id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB");

        $this->addSql("INSERT INTO boutiques_relais (nom, adresse, code_postal, ville, actif, created_at)
            VALUES ('Du fromage au dessert', 'TODO adresse — à compléter', '64300', 'Orthez', 1, NOW())");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE boutiques_relais');
    }
}

<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260309020000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Propositions commerciales sur-mesure';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE propositions_commerciales (
            id INT AUTO_INCREMENT NOT NULL,
            demande_sur_mesure_id INT DEFAULT NULL,
            commande_id INT DEFAULT NULL,
            description LONGTEXT NOT NULL,
            prix_total NUMERIC(10, 2) NOT NULL,
            client_email VARCHAR(255) NOT NULL,
            client_nom VARCHAR(255) DEFAULT NULL,
            statut VARCHAR(50) NOT NULL DEFAULT \'brouillon\',
            token VARCHAR(64) NOT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            UNIQUE INDEX UNIQ_TOKEN (token),
            INDEX IDX_DEMANDE (demande_sur_mesure_id),
            INDEX IDX_COMMANDE (commande_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE propositions_commerciales');
    }
}

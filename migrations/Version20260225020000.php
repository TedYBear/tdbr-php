<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260225020000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute la table codes_reduction et le champ reduction dans commandes';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE codes_reduction (
            id INT AUTO_INCREMENT NOT NULL,
            user_id INT NOT NULL,
            commande_id INT DEFAULT NULL,
            code VARCHAR(100) NOT NULL,
            montant DECIMAL(10,2) NOT NULL,
            statut VARCHAR(20) NOT NULL DEFAULT \'actif\',
            date_expiration DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            PRIMARY KEY (id),
            CONSTRAINT FK_cr_user FOREIGN KEY (user_id) REFERENCES `users` (id) ON DELETE CASCADE,
            CONSTRAINT FK_cr_commande FOREIGN KEY (commande_id) REFERENCES commandes (id) ON DELETE SET NULL
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE commandes ADD reduction DECIMAL(10,2) NOT NULL DEFAULT 0');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE codes_reduction');
        $this->addSql('ALTER TABLE commandes DROP COLUMN reduction');
    }
}

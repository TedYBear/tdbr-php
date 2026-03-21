<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260321010000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create depot_ventes, stock_items, transactions and transaction_lignes tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE depot_ventes (
            id INT AUTO_INCREMENT NOT NULL,
            user_id INT DEFAULT NULL,
            nom VARCHAR(255) NOT NULL,
            adresse VARCHAR(255) DEFAULT NULL,
            code_postal VARCHAR(10) DEFAULT NULL,
            ville VARCHAR(100) DEFAULT NULL,
            telephone VARCHAR(20) DEFAULT NULL,
            email VARCHAR(255) DEFAULT NULL,
            fond_de_caisse NUMERIC(10, 2) NOT NULL DEFAULT 0,
            actif TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            INDEX IDX_depot_ventes_user (user_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE depot_vente_stock_items (
            id INT AUTO_INCREMENT NOT NULL,
            depot_vente_id INT NOT NULL,
            variante_id INT NOT NULL,
            quantite INT NOT NULL DEFAULT 0,
            updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            INDEX IDX_stock_depot (depot_vente_id),
            INDEX IDX_stock_variante (variante_id),
            UNIQUE INDEX uniq_depot_variante (depot_vente_id, variante_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE depot_vente_transactions (
            id INT AUTO_INCREMENT NOT NULL,
            depot_vente_id INT NOT NULL,
            created_by_id INT DEFAULT NULL,
            type VARCHAR(20) NOT NULL,
            montant_fond NUMERIC(10, 2) DEFAULT NULL,
            note LONGTEXT DEFAULT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            INDEX IDX_tx_depot (depot_vente_id),
            INDEX IDX_tx_user (created_by_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE depot_vente_transaction_lignes (
            id INT AUTO_INCREMENT NOT NULL,
            transaction_id INT NOT NULL,
            variante_id INT DEFAULT NULL,
            variante_label VARCHAR(300) NOT NULL,
            quantite INT NOT NULL,
            prix_estime NUMERIC(10, 2) DEFAULT NULL,
            prix_reel NUMERIC(10, 2) DEFAULT NULL,
            INDEX IDX_tl_transaction (transaction_id),
            INDEX IDX_tl_variante (variante_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE depot_ventes ADD CONSTRAINT FK_dv_user FOREIGN KEY (user_id) REFERENCES `users` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE depot_vente_stock_items ADD CONSTRAINT FK_si_depot FOREIGN KEY (depot_vente_id) REFERENCES depot_ventes (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE depot_vente_stock_items ADD CONSTRAINT FK_si_variante FOREIGN KEY (variante_id) REFERENCES variantes (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE depot_vente_transactions ADD CONSTRAINT FK_tx_depot FOREIGN KEY (depot_vente_id) REFERENCES depot_ventes (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE depot_vente_transactions ADD CONSTRAINT FK_tx_user FOREIGN KEY (created_by_id) REFERENCES `users` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE depot_vente_transaction_lignes ADD CONSTRAINT FK_tl_tx FOREIGN KEY (transaction_id) REFERENCES depot_vente_transactions (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE depot_vente_transaction_lignes ADD CONSTRAINT FK_tl_variante FOREIGN KEY (variante_id) REFERENCES variantes (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE depot_vente_transaction_lignes DROP FOREIGN KEY FK_tl_tx');
        $this->addSql('ALTER TABLE depot_vente_transaction_lignes DROP FOREIGN KEY FK_tl_variante');
        $this->addSql('ALTER TABLE depot_vente_transactions DROP FOREIGN KEY FK_tx_depot');
        $this->addSql('ALTER TABLE depot_vente_transactions DROP FOREIGN KEY FK_tx_user');
        $this->addSql('ALTER TABLE depot_vente_stock_items DROP FOREIGN KEY FK_si_depot');
        $this->addSql('ALTER TABLE depot_vente_stock_items DROP FOREIGN KEY FK_si_variante');
        $this->addSql('ALTER TABLE depot_ventes DROP FOREIGN KEY FK_dv_user');
        $this->addSql('DROP TABLE depot_vente_transaction_lignes');
        $this->addSql('DROP TABLE depot_vente_transactions');
        $this->addSql('DROP TABLE depot_vente_stock_items');
        $this->addSql('DROP TABLE depot_ventes');
    }
}

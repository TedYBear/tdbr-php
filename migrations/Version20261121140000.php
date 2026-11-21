<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261121140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Crée la table campagnes_cadeau (multi-campagnes avec déclencheurs et validité) et lie codes_reduction.campagne_id';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<SQL
            CREATE TABLE campagnes_cadeau (
                id INT AUTO_INCREMENT NOT NULL,
                nom VARCHAR(150) NOT NULL,
                actif TINYINT(1) NOT NULL,
                declencheur VARCHAR(30) NOT NULL,
                montant_minimum NUMERIC(10, 2) DEFAULT NULL,
                nombre_commandes INT DEFAULT NULL,
                max_beneficiaires INT DEFAULT NULL,
                une_fois_par_client TINYINT(1) DEFAULT 1 NOT NULL,
                type_reduction VARCHAR(20) NOT NULL,
                valeur NUMERIC(10, 2) NOT NULL,
                arrondi50_centimes TINYINT(1) DEFAULT 1 NOT NULL,
                code_nom VARCHAR(100) DEFAULT NULL,
                date_fin_activation DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
                type_validite VARCHAR(20) DEFAULT 'aucune' NOT NULL,
                date_fin_validite_fixe DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
                created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);

        $this->addSql('ALTER TABLE codes_reduction ADD campagne_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE codes_reduction ADD CONSTRAINT FK_CODES_REDUCTION_CAMPAGNE FOREIGN KEY (campagne_id) REFERENCES campagnes_cadeau (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_CODES_REDUCTION_CAMPAGNE ON codes_reduction (campagne_id)');

        // Désactiver l'ancienne campagne unique dans site_config
        $this->addSql('UPDATE site_config SET gift_active = 0');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE codes_reduction DROP FOREIGN KEY FK_CODES_REDUCTION_CAMPAGNE');
        $this->addSql('DROP INDEX IDX_CODES_REDUCTION_CAMPAGNE ON codes_reduction');
        $this->addSql('ALTER TABLE codes_reduction DROP campagne_id');
        $this->addSql('DROP TABLE campagnes_cadeau');
    }
}

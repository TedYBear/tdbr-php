<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260618010000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute user_id, article_id, source, personnalisation à demandes_sur_mesure et rend concept nullable (demandes de personnalisation produit)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<SQL
            ALTER TABLE demandes_sur_mesure
                ADD user_id INT DEFAULT NULL,
                ADD article_id INT DEFAULT NULL,
                ADD source VARCHAR(30) NOT NULL DEFAULT 'devis',
                ADD personnalisation JSON DEFAULT NULL,
                CHANGE concept concept LONGTEXT DEFAULT NULL
        SQL);

        $this->addSql('ALTER TABLE demandes_sur_mesure ADD CONSTRAINT FK_DSM_USER FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE demandes_sur_mesure ADD CONSTRAINT FK_DSM_ARTICLE FOREIGN KEY (article_id) REFERENCES articles (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_DSM_USER ON demandes_sur_mesure (user_id)');
        $this->addSql('CREATE INDEX IDX_DSM_ARTICLE ON demandes_sur_mesure (article_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE demandes_sur_mesure DROP FOREIGN KEY FK_DSM_USER');
        $this->addSql('ALTER TABLE demandes_sur_mesure DROP FOREIGN KEY FK_DSM_ARTICLE');
        $this->addSql('DROP INDEX IDX_DSM_USER ON demandes_sur_mesure');
        $this->addSql('DROP INDEX IDX_DSM_ARTICLE ON demandes_sur_mesure');
        $this->addSql(<<<SQL
            ALTER TABLE demandes_sur_mesure
                DROP user_id,
                DROP article_id,
                DROP source,
                DROP personnalisation,
                CHANGE concept concept LONGTEXT NOT NULL
        SQL);
    }
}

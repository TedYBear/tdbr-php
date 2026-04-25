<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260425010000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Crée la table guides_tailles et ajoute une FK nullable sur articles';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            CREATE TABLE guides_tailles (
                id INT AUTO_INCREMENT NOT NULL,
                nom VARCHAR(200) NOT NULL,
                slug VARCHAR(220) NOT NULL,
                description LONGTEXT DEFAULT NULL,
                image_diagramme VARCHAR(500) DEFAULT NULL,
                mesures JSON NOT NULL,
                colonnes JSON NOT NULL,
                lignes JSON NOT NULL,
                unite VARCHAR(20) NOT NULL,
                actif TINYINT(1) NOT NULL,
                created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
                UNIQUE INDEX UNIQ_guides_tailles_slug (slug),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        ");

        $this->addSql("ALTER TABLE articles ADD guide_taille_id INT DEFAULT NULL");
        $this->addSql("ALTER TABLE articles ADD CONSTRAINT FK_articles_guide_taille FOREIGN KEY (guide_taille_id) REFERENCES guides_tailles (id) ON DELETE SET NULL");
        $this->addSql("CREATE INDEX IDX_articles_guide_taille ON articles (guide_taille_id)");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE articles DROP FOREIGN KEY FK_articles_guide_taille");
        $this->addSql("DROP INDEX IDX_articles_guide_taille ON articles");
        $this->addSql("ALTER TABLE articles DROP COLUMN guide_taille_id");

        $this->addSql("DROP TABLE guides_tailles");
    }
}

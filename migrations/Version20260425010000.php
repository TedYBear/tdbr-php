<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260425010000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute guide_taille_pdf_url sur variante_templates et lien variante_template_id sur articles';
    }

    public function up(Schema $schema): void
    {
        // Nettoyage de l'éventuel essai précédent (table guides_tailles + FK sur articles)
        $this->addSql("ALTER TABLE articles DROP FOREIGN KEY IF EXISTS FK_articles_guide_taille");
        $this->addSql("DROP INDEX IF EXISTS IDX_articles_guide_taille ON articles");
        $this->addSql("ALTER TABLE articles DROP COLUMN IF EXISTS guide_taille_id");
        $this->addSql("DROP TABLE IF EXISTS guides_tailles");

        // Nouveau : URL PDF guide des tailles sur le template
        $this->addSql("ALTER TABLE variante_templates ADD guide_taille_pdf_url VARCHAR(500) DEFAULT NULL");

        // Nouveau : lien optionnel article → template
        $this->addSql("ALTER TABLE articles ADD variante_template_id INT DEFAULT NULL");
        $this->addSql("ALTER TABLE articles ADD CONSTRAINT FK_articles_variante_template FOREIGN KEY (variante_template_id) REFERENCES variante_templates (id) ON DELETE SET NULL");
        $this->addSql("CREATE INDEX IDX_articles_variante_template ON articles (variante_template_id)");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE articles DROP FOREIGN KEY FK_articles_variante_template");
        $this->addSql("DROP INDEX IDX_articles_variante_template ON articles");
        $this->addSql("ALTER TABLE articles DROP COLUMN variante_template_id");

        $this->addSql("ALTER TABLE variante_templates DROP COLUMN guide_taille_pdf_url");
    }
}

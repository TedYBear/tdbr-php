<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Nettoyage des reliquats de la première tentative de guide des tailles
 * (table guides_tailles + FK guide_taille_id sur articles).
 *
 * Idempotente : utilise information_schema pour ne tenter les DROP
 * que si les objets existent réellement.
 */
final class Version20260426020000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Nettoie la table guides_tailles et la colonne articles.guide_taille_id si présents';
    }

    public function up(Schema $schema): void
    {
        $conn = $this->connection;
        $db   = $conn->getDatabase();

        // 1) Drop FK FK_articles_guide_taille si elle existe
        $fkExists = (int)$conn->fetchOne(
            "SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
             WHERE CONSTRAINT_SCHEMA = ?
               AND TABLE_NAME = 'articles'
               AND CONSTRAINT_NAME = 'FK_articles_guide_taille'
               AND CONSTRAINT_TYPE = 'FOREIGN KEY'",
            [$db]
        );
        if ($fkExists > 0) {
            $this->addSql("ALTER TABLE articles DROP FOREIGN KEY FK_articles_guide_taille");
        }

        // 2) Drop l'index si présent (peut subsister après le drop FK)
        $idxExists = (int)$conn->fetchOne(
            "SELECT COUNT(*) FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = ?
               AND TABLE_NAME = 'articles'
               AND INDEX_NAME = 'IDX_articles_guide_taille'",
            [$db]
        );
        if ($idxExists > 0) {
            $this->addSql("DROP INDEX IDX_articles_guide_taille ON articles");
        }

        // 3) Drop la colonne articles.guide_taille_id si présente
        $colExists = (int)$conn->fetchOne(
            "SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = ?
               AND TABLE_NAME = 'articles'
               AND COLUMN_NAME = 'guide_taille_id'",
            [$db]
        );
        if ($colExists > 0) {
            $this->addSql("ALTER TABLE articles DROP COLUMN guide_taille_id");
        }

        // 4) Drop la table guides_tailles si présente
        $tblExists = (int)$conn->fetchOne(
            "SELECT COUNT(*) FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = ?
               AND TABLE_NAME = 'guides_tailles'",
            [$db]
        );
        if ($tblExists > 0) {
            $this->addSql("DROP TABLE guides_tailles");
        }
    }

    public function down(Schema $schema): void
    {
        // Pas de rollback : la première version (Version20260425010000) recrée
        // la structure d'origine si nécessaire.
        $this->throwIrreversibleMigrationException('Migration de nettoyage non réversible.');
    }
}

<?php
declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260222200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create fournisseurs table and add fournisseur_id to articles';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE fournisseurs (
            id INT AUTO_INCREMENT NOT NULL,
            nom VARCHAR(255) NOT NULL,
            url VARCHAR(500) DEFAULT NULL,
            logo_filename VARCHAR(255) DEFAULT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE articles ADD fournisseur_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE articles ADD CONSTRAINT FK_BFDD3168670C757F FOREIGN KEY (fournisseur_id) REFERENCES fournisseurs (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_BFDD3168670C757F ON articles (fournisseur_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE articles DROP FOREIGN KEY FK_BFDD3168670C757F');
        $this->addSql('ALTER TABLE articles DROP INDEX IDX_BFDD3168670C757F');
        $this->addSql('ALTER TABLE articles DROP COLUMN fournisseur_id');
        $this->addSql('DROP TABLE fournisseurs');
    }
}

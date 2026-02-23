<?php
declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260223040000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create grilles_prix table and add grille_prix_id to articles';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE grilles_prix (
            id INT AUTO_INCREMENT NOT NULL,
            nom VARCHAR(200) NOT NULL,
            description LONGTEXT NULL,
            lignes JSON NOT NULL,
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');

        $this->addSql('ALTER TABLE articles ADD COLUMN grille_prix_id INT NULL');
        $this->addSql('ALTER TABLE articles ADD CONSTRAINT FK_articles_grille_prix FOREIGN KEY (grille_prix_id) REFERENCES grilles_prix(id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE articles DROP FOREIGN KEY FK_articles_grille_prix');
        $this->addSql('ALTER TABLE articles DROP COLUMN grille_prix_id');
        $this->addSql('DROP TABLE grilles_prix');
    }
}

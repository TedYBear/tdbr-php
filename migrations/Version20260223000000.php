<?php
declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260223000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add article_caracteristiques join table and drop stock column from variantes';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE article_caracteristiques (
            article_id INT NOT NULL,
            caracteristique_id INT NOT NULL,
            PRIMARY KEY(article_id, caracteristique_id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE article_caracteristiques
            ADD CONSTRAINT FK_AC_ARTICLE FOREIGN KEY (article_id) REFERENCES articles (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE article_caracteristiques
            ADD CONSTRAINT FK_AC_CARAC FOREIGN KEY (caracteristique_id) REFERENCES caracteristiques (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_AC_ARTICLE ON article_caracteristiques (article_id)');
        $this->addSql('CREATE INDEX IDX_AC_CARAC ON article_caracteristiques (caracteristique_id)');

        $this->addSql('ALTER TABLE variantes DROP COLUMN stock');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE article_caracteristiques');
        $this->addSql('ALTER TABLE variantes ADD COLUMN stock INT NOT NULL DEFAULT 0');
    }
}

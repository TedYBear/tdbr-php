<?php
declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260223010000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add valeurs JSON column to variantes; drop article_caracteristiques if exists';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE variantes ADD COLUMN valeurs JSON NULL');
        $this->addSql('DROP TABLE IF EXISTS article_caracteristiques');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE variantes DROP COLUMN valeurs');
    }
}

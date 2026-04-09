<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260403010000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add parent_id self-referential FK on propositions_commerciales for clone tracking';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE propositions_commerciales ADD parent_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE propositions_commerciales ADD CONSTRAINT FK_prop_parent FOREIGN KEY (parent_id) REFERENCES propositions_commerciales (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_prop_parent ON propositions_commerciales (parent_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE propositions_commerciales DROP FOREIGN KEY FK_prop_parent');
        $this->addSql('DROP INDEX IDX_prop_parent ON propositions_commerciales');
        $this->addSql('ALTER TABLE propositions_commerciales DROP COLUMN parent_id');
    }
}

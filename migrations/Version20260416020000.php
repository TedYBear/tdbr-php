<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260416020000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Liaison PropositionCommerciale <-> User';
    }

    public function up(Schema $schema): void
    {
        // La colonne user_id a été ajoutée lors d'une tentative précédente
        $this->addSql('ALTER TABLE propositions_commerciales ADD CONSTRAINT FK_PROP_USER FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_PROP_USER ON propositions_commerciales (user_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE propositions_commerciales DROP FOREIGN KEY FK_PROP_USER');
        $this->addSql('DROP INDEX IDX_PROP_USER ON propositions_commerciales');
        $this->addSql('ALTER TABLE propositions_commerciales DROP COLUMN user_id');
    }
}

<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260226040000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Relation ManyToOne Commande → User';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE commandes ADD user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE commandes ADD CONSTRAINT FK_35D4282CA76ED395 FOREIGN KEY (user_id) REFERENCES `users` (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_35D4282CA76ED395 ON commandes (user_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE commandes DROP FOREIGN KEY FK_35D4282CA76ED395');
        $this->addSql('DROP INDEX IDX_35D4282CA76ED395 ON commandes');
        $this->addSql('ALTER TABLE commandes DROP user_id');
    }
}

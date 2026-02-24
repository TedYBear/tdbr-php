<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260224010000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute stripe_payment_intent_id sur commandes';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE commandes ADD stripe_payment_intent_id VARCHAR(255) NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_COMMANDES_STRIPE ON commandes (stripe_payment_intent_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_COMMANDES_STRIPE ON commandes');
        $this->addSql('ALTER TABLE commandes DROP COLUMN stripe_payment_intent_id');
    }
}

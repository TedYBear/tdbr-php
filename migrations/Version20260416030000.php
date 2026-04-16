<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260416030000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remplacement Stripe par Mollie : renommage de stripe_payment_intent_id en mollie_payment_id';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE commandes CHANGE stripe_payment_intent_id mollie_payment_id VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE commandes CHANGE mollie_payment_id stripe_payment_intent_id VARCHAR(255) DEFAULT NULL');
    }
}

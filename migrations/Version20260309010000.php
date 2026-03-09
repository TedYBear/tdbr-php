<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260309010000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Intégration Printful : printfulApiKey sur fournisseurs, printfulVariantId sur variantes, printfulOrderId sur commandes';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE fournisseurs ADD printful_api_key VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE variantes ADD printful_variant_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE commandes ADD printful_order_id INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE fournisseurs DROP COLUMN printful_api_key');
        $this->addSql('ALTER TABLE variantes DROP COLUMN printful_variant_id');
        $this->addSql('ALTER TABLE commandes DROP COLUMN printful_order_id');
    }
}

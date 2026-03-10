<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260310020000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'printful_variant_id : INT → BIGINT pour supporter les grands IDs Printful';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE variantes MODIFY printful_variant_id BIGINT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE variantes MODIFY printful_variant_id INT DEFAULT NULL');
    }
}

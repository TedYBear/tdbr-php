<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260311010000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Article : ajout de printful_product_id (BIGINT unique) pour association Printful indépendante du slug';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE articles ADD printful_product_id BIGINT DEFAULT NULL, ADD UNIQUE INDEX UNIQ_ARTICLES_PRINTFUL_PRODUCT (printful_product_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE articles DROP INDEX UNIQ_ARTICLES_PRINTFUL_PRODUCT, DROP COLUMN printful_product_id');
    }
}

<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261120212500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute lien_direct_uniquement et access_token sur categories, product_collections et articles';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE categories ADD lien_direct_uniquement TINYINT(1) DEFAULT 0 NOT NULL, ADD access_token VARCHAR(64) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_3AF34668B6A2DD68 ON categories (access_token)');

        $this->addSql('ALTER TABLE product_collections ADD lien_direct_uniquement TINYINT(1) DEFAULT 0 NOT NULL, ADD access_token VARCHAR(64) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1F89D4D1B6A2DD68 ON product_collections (access_token)');

        $this->addSql('ALTER TABLE articles ADD lien_direct_uniquement TINYINT(1) DEFAULT 0 NOT NULL, ADD access_token VARCHAR(64) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_BFDD3168B6A2DD68 ON articles (access_token)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_3AF34668B6A2DD68 ON categories');
        $this->addSql('ALTER TABLE categories DROP lien_direct_uniquement, DROP access_token');

        $this->addSql('DROP INDEX UNIQ_1F89D4D1B6A2DD68 ON product_collections');
        $this->addSql('ALTER TABLE product_collections DROP lien_direct_uniquement, DROP access_token');

        $this->addSql('DROP INDEX UNIQ_BFDD3168B6A2DD68 ON articles');
        $this->addSql('ALTER TABLE articles DROP lien_direct_uniquement, DROP access_token');
    }
}

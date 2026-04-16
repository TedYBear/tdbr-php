<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260416010000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout du champ payments_disabled dans site_config';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE site_config ADD payments_disabled TINYINT(1) NOT NULL DEFAULT 0');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE site_config DROP COLUMN payments_disabled');
    }
}

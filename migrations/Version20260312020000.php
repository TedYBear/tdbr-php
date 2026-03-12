<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260312020000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute message_personnel sur proposition_commerciale';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE propositions_commerciales ADD message_personnel LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE propositions_commerciales DROP COLUMN message_personnel');
    }
}

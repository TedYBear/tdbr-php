<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260226010000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Codes de réduction globaux : user nullable + ajout date_debut';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE codes_reduction ADD date_debut DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE codes_reduction MODIFY user_id INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE codes_reduction DROP COLUMN date_debut');
        $this->addSql('ALTER TABLE codes_reduction MODIFY user_id INT NOT NULL');
    }
}

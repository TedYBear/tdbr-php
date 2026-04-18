<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260418010000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute invite_token et invite_token_expires_at sur users pour la création admin';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users ADD invite_token VARCHAR(100) DEFAULT NULL, ADD invite_token_expires_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users DROP COLUMN invite_token, DROP COLUMN invite_token_expires_at');
    }
}

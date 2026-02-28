<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260226050000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Relier les commandes existantes à leur utilisateur via l\'email du champ client JSON';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            UPDATE commandes c
            INNER JOIN users u ON u.email = JSON_UNQUOTE(JSON_EXTRACT(c.client, '$.email'))
            SET c.user_id = u.id
            WHERE c.user_id IS NULL
        ");
    }

    public function down(Schema $schema): void
    {
        // Irréversible par nature — on remet simplement tout à NULL
        $this->addSql('UPDATE commandes SET user_id = NULL');
    }
}

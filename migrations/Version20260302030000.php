<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260302030000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remet delta_prix à 0 pour toutes les variantes (les anciennes valeurs étaient des prix absolus, pas des deltas)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE variantes SET delta_prix = 0');
    }

    public function down(Schema $schema): void
    {
        // Pas de retour en arrière possible : les anciens prix absolus ne sont pas récupérables
    }
}

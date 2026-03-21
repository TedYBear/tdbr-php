<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260322010000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Reset all depot-vente stock quantities to 0';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE depot_vente_stock_items SET quantite = 0');
    }

    public function down(Schema $schema): void
    {
        // Irréversible — le stock ne peut pas être restauré automatiquement
    }
}

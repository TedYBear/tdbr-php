<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260322020000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Clear all depot-vente stock quantities and transaction history';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DELETE FROM depot_vente_transaction_lignes');
        $this->addSql('DELETE FROM depot_vente_transactions');
        $this->addSql('UPDATE depot_vente_stock_items SET quantite = 0');
    }

    public function down(Schema $schema): void
    {
        // Irréversible — les données ne peuvent pas être restaurées automatiquement
    }
}

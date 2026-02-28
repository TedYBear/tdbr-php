<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260226030000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout de la campagne code cadeau dans site_config et flags dans codes_reduction';
    }

    public function up(Schema $schema): void
    {
        // Champs campagne cadeau dans site_config
        $this->addSql('ALTER TABLE site_config
            ADD gift_active TINYINT(1) NOT NULL DEFAULT 0,
            ADD gift_type VARCHAR(20) NOT NULL DEFAULT \'fixe\',
            ADD gift_value DECIMAL(10,2) NOT NULL DEFAULT 5.00,
            ADD gift_max_beneficiaires INT NOT NULL DEFAULT 10
        ');

        // Champs campagne dans codes_reduction
        $this->addSql('ALTER TABLE codes_reduction
            ADD is_campaign_gift TINYINT(1) NOT NULL DEFAULT 0,
            ADD recipient_email VARCHAR(255) DEFAULT NULL
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE site_config
            DROP gift_active,
            DROP gift_type,
            DROP gift_value,
            DROP gift_max_beneficiaires
        ');

        $this->addSql('ALTER TABLE codes_reduction
            DROP is_campaign_gift,
            DROP recipient_email
        ');
    }
}

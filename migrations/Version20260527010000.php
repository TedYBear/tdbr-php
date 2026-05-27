<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260527010000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Crée la table page_views pour le système d\'analytiques de trafic';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<SQL
            CREATE TABLE page_views (
                id          INT UNSIGNED AUTO_INCREMENT NOT NULL,
                route_name  VARCHAR(100)  NOT NULL,
                entity_type VARCHAR(50)   DEFAULT NULL,
                entity_slug VARCHAR(300)  DEFAULT NULL,
                entity_id   INT           DEFAULT NULL,
                session_id  VARCHAR(64)   NOT NULL,
                ip_partial  VARCHAR(50)   DEFAULT NULL,
                viewed_at   DATETIME      NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                created_at  DATETIME      NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);

        $this->addSql('CREATE INDEX idx_page_views_viewed_at ON page_views (viewed_at)');
        $this->addSql('CREATE INDEX idx_page_views_entity    ON page_views (entity_type, entity_slug)');
        $this->addSql('CREATE INDEX idx_page_views_route     ON page_views (route_name)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE page_views');
    }
}

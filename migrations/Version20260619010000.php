<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260619010000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Crée types_personnalisation (liste gérée en admin) et la table de jointure template_personnalisations';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<SQL
            CREATE TABLE types_personnalisation (
                id    INT AUTO_INCREMENT NOT NULL,
                nom   VARCHAR(200) NOT NULL,
                actif TINYINT(1) NOT NULL DEFAULT 1,
                ordre INT NOT NULL DEFAULT 0,
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);

        $this->addSql(<<<SQL
            CREATE TABLE template_personnalisations (
                variante_template_id     INT NOT NULL,
                type_personnalisation_id INT NOT NULL,
                INDEX IDX_TP_VT (variante_template_id),
                INDEX IDX_TP_TYPE (type_personnalisation_id),
                PRIMARY KEY(variante_template_id, type_personnalisation_id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);

        $this->addSql('ALTER TABLE template_personnalisations
            ADD CONSTRAINT FK_TP_TEMPLATE FOREIGN KEY (variante_template_id) REFERENCES variante_templates (id) ON DELETE CASCADE,
            ADD CONSTRAINT FK_TP_TYPE FOREIGN KEY (type_personnalisation_id) REFERENCES types_personnalisation (id) ON DELETE CASCADE');

        // Valeurs par défaut (anciennement codées en dur dans la modale)
        $this->addSql("INSERT INTO types_personnalisation (nom, actif, ordre) VALUES
            ('Couleur différente', 1, 1),
            ('Taille non répertoriée', 1, 2),
            ('Modèle de T-shirt - H/F/Enfant', 1, 3)");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE template_personnalisations DROP FOREIGN KEY FK_TP_TEMPLATE');
        $this->addSql('ALTER TABLE template_personnalisations DROP FOREIGN KEY FK_TP_TYPE');
        $this->addSql('DROP TABLE template_personnalisations');
        $this->addSql('DROP TABLE types_personnalisation');
    }
}

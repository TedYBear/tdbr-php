<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260217000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Création initiale du schéma MySQL - Migration depuis MongoDB';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE `users` (
            id INT AUTO_INCREMENT NOT NULL,
            email VARCHAR(180) NOT NULL,
            roles JSON NOT NULL,
            password VARCHAR(255) NOT NULL,
            prenom VARCHAR(100) DEFAULT NULL,
            nom VARCHAR(100) DEFAULT NULL,
            telephone VARCHAR(20) DEFAULT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            UNIQUE INDEX UNIQ_1483A5E9E7927C74 (email),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE categories (
            id INT AUTO_INCREMENT NOT NULL,
            nom VARCHAR(200) NOT NULL,
            slug VARCHAR(200) NOT NULL,
            description LONGTEXT DEFAULT NULL,
            image VARCHAR(500) DEFAULT NULL,
            ordre INT NOT NULL DEFAULT 0,
            actif TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            UNIQUE INDEX UNIQ_3AF34668989D9B62 (slug),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE product_collections (
            id INT AUTO_INCREMENT NOT NULL,
            categorie_id INT DEFAULT NULL,
            nom VARCHAR(200) NOT NULL,
            slug VARCHAR(200) NOT NULL,
            description LONGTEXT DEFAULT NULL,
            image VARCHAR(500) DEFAULT NULL,
            ordre INT NOT NULL DEFAULT 0,
            actif TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            UNIQUE INDEX UNIQ_4E6A7B46989D9B62 (slug),
            INDEX IDX_4E6A7B46BCF5E72D (categorie_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE articles (
            id INT AUTO_INCREMENT NOT NULL,
            collection_id INT DEFAULT NULL,
            nom VARCHAR(300) NOT NULL,
            slug VARCHAR(300) NOT NULL,
            description LONGTEXT DEFAULT NULL,
            prix_base DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
            actif TINYINT(1) NOT NULL DEFAULT 1,
            en_vedette TINYINT(1) NOT NULL DEFAULT 0,
            personnalisable TINYINT(1) NOT NULL DEFAULT 0,
            ordre INT NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            UNIQUE INDEX UNIQ_23A0E66989D9B62 (slug),
            INDEX IDX_23A0E66514956FD (collection_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE article_images (
            id INT AUTO_INCREMENT NOT NULL,
            article_id INT NOT NULL,
            url VARCHAR(500) NOT NULL,
            alt VARCHAR(300) DEFAULT NULL,
            ordre INT NOT NULL DEFAULT 0,
            INDEX IDX_F8C1EABB7294869C (article_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE variantes (
            id INT AUTO_INCREMENT NOT NULL,
            article_id INT NOT NULL,
            nom VARCHAR(200) NOT NULL,
            sku VARCHAR(100) DEFAULT NULL,
            prix DECIMAL(10, 2) DEFAULT NULL,
            stock INT NOT NULL DEFAULT 0,
            actif TINYINT(1) NOT NULL DEFAULT 1,
            INDEX IDX_2E70F2E57294869C (article_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE commandes (
            id INT AUTO_INCREMENT NOT NULL,
            numero VARCHAR(50) NOT NULL,
            client JSON NOT NULL,
            adresse_livraison JSON NOT NULL,
            articles JSON NOT NULL,
            total DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
            mode_paiement VARCHAR(50) DEFAULT NULL,
            notes LONGTEXT DEFAULT NULL,
            statut VARCHAR(50) NOT NULL DEFAULT \'en_attente\',
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            UNIQUE INDEX UNIQ_35D4282CA3B0E7AE (numero),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE messages (
            id INT AUTO_INCREMENT NOT NULL,
            nom VARCHAR(200) NOT NULL,
            email VARCHAR(200) NOT NULL,
            sujet VARCHAR(300) DEFAULT NULL,
            message LONGTEXT NOT NULL,
            lu TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE caracteristiques (
            id INT AUTO_INCREMENT NOT NULL,
            nom VARCHAR(200) NOT NULL,
            type VARCHAR(50) NOT NULL DEFAULT \'text\',
            obligatoire TINYINT(1) NOT NULL DEFAULT 0,
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE caracteristique_valeurs (
            id INT AUTO_INCREMENT NOT NULL,
            caracteristique_id INT NOT NULL,
            valeur VARCHAR(200) NOT NULL,
            INDEX IDX_B5F6D8FE07509FE (caracteristique_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE variante_templates (
            id INT AUTO_INCREMENT NOT NULL,
            nom VARCHAR(200) NOT NULL,
            description LONGTEXT DEFAULT NULL,
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE template_caracteristiques (
            variante_template_id INT NOT NULL,
            caracteristique_id INT NOT NULL,
            INDEX IDX_TMPL_VT (variante_template_id),
            INDEX IDX_TMPL_CARAC (caracteristique_id),
            PRIMARY KEY(variante_template_id, caracteristique_id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE doctrine_migration_versions (
            version VARCHAR(191) NOT NULL,
            executed_at DATETIME DEFAULT NULL,
            execution_time INT DEFAULT NULL,
            PRIMARY KEY(version)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Foreign keys
        $this->addSql('ALTER TABLE product_collections
            ADD CONSTRAINT FK_PC_CATEGORIE FOREIGN KEY (categorie_id) REFERENCES categories (id) ON DELETE SET NULL');

        $this->addSql('ALTER TABLE articles
            ADD CONSTRAINT FK_ART_COLLECTION FOREIGN KEY (collection_id) REFERENCES product_collections (id) ON DELETE SET NULL');

        $this->addSql('ALTER TABLE article_images
            ADD CONSTRAINT FK_AI_ARTICLE FOREIGN KEY (article_id) REFERENCES articles (id) ON DELETE CASCADE');

        $this->addSql('ALTER TABLE variantes
            ADD CONSTRAINT FK_VAR_ARTICLE FOREIGN KEY (article_id) REFERENCES articles (id) ON DELETE CASCADE');

        $this->addSql('ALTER TABLE caracteristique_valeurs
            ADD CONSTRAINT FK_CV_CARAC FOREIGN KEY (caracteristique_id) REFERENCES caracteristiques (id) ON DELETE CASCADE');

        $this->addSql('ALTER TABLE template_caracteristiques
            ADD CONSTRAINT FK_TC_TEMPLATE FOREIGN KEY (variante_template_id) REFERENCES variante_templates (id) ON DELETE CASCADE,
            ADD CONSTRAINT FK_TC_CARAC FOREIGN KEY (caracteristique_id) REFERENCES caracteristiques (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE template_caracteristiques DROP FOREIGN KEY FK_TC_TEMPLATE');
        $this->addSql('ALTER TABLE template_caracteristiques DROP FOREIGN KEY FK_TC_CARAC');
        $this->addSql('ALTER TABLE caracteristique_valeurs DROP FOREIGN KEY FK_CV_CARAC');
        $this->addSql('ALTER TABLE variantes DROP FOREIGN KEY FK_VAR_ARTICLE');
        $this->addSql('ALTER TABLE article_images DROP FOREIGN KEY FK_AI_ARTICLE');
        $this->addSql('ALTER TABLE articles DROP FOREIGN KEY FK_ART_COLLECTION');
        $this->addSql('ALTER TABLE product_collections DROP FOREIGN KEY FK_PC_CATEGORIE');

        $this->addSql('DROP TABLE IF EXISTS template_caracteristiques');
        $this->addSql('DROP TABLE IF EXISTS variante_templates');
        $this->addSql('DROP TABLE IF EXISTS caracteristique_valeurs');
        $this->addSql('DROP TABLE IF EXISTS caracteristiques');
        $this->addSql('DROP TABLE IF EXISTS messages');
        $this->addSql('DROP TABLE IF EXISTS commandes');
        $this->addSql('DROP TABLE IF EXISTS variantes');
        $this->addSql('DROP TABLE IF EXISTS article_images');
        $this->addSql('DROP TABLE IF EXISTS articles');
        $this->addSql('DROP TABLE IF EXISTS product_collections');
        $this->addSql('DROP TABLE IF EXISTS categories');
        $this->addSql('DROP TABLE IF EXISTS `users`');
    }
}

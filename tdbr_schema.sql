-- ============================================================
-- TDBR - Schéma MySQL complet
-- Généré le 2026-02-17
-- À exécuter sur Hostinger via phpMyAdmin ou SSH
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- ============================================================
-- Table: users
-- ============================================================
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT NOT NULL,
    `email` VARCHAR(180) NOT NULL,
    `roles` JSON NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `prenom` VARCHAR(100) DEFAULT NULL,
    `nom` VARCHAR(100) DEFAULT NULL,
    `telephone` VARCHAR(20) DEFAULT NULL,
    `created_at` DATETIME NOT NULL,
    UNIQUE INDEX `UNIQ_users_email` (`email`),
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Table: categories
-- ============================================================
CREATE TABLE IF NOT EXISTS `categories` (
    `id` INT AUTO_INCREMENT NOT NULL,
    `nom` VARCHAR(200) NOT NULL,
    `slug` VARCHAR(200) NOT NULL,
    `description` LONGTEXT DEFAULT NULL,
    `image` VARCHAR(500) DEFAULT NULL,
    `ordre` INT NOT NULL DEFAULT 0,
    `actif` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL,
    UNIQUE INDEX `UNIQ_categories_slug` (`slug`),
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Table: product_collections
-- ============================================================
CREATE TABLE IF NOT EXISTS `product_collections` (
    `id` INT AUTO_INCREMENT NOT NULL,
    `categorie_id` INT DEFAULT NULL,
    `nom` VARCHAR(200) NOT NULL,
    `slug` VARCHAR(200) NOT NULL,
    `description` LONGTEXT DEFAULT NULL,
    `image` VARCHAR(500) DEFAULT NULL,
    `ordre` INT NOT NULL DEFAULT 0,
    `actif` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL,
    UNIQUE INDEX `UNIQ_pc_slug` (`slug`),
    INDEX `IDX_pc_categorie` (`categorie_id`),
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Table: articles
-- ============================================================
CREATE TABLE IF NOT EXISTS `articles` (
    `id` INT AUTO_INCREMENT NOT NULL,
    `collection_id` INT DEFAULT NULL,
    `nom` VARCHAR(300) NOT NULL,
    `slug` VARCHAR(300) NOT NULL,
    `description` LONGTEXT DEFAULT NULL,
    `prix_base` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `actif` TINYINT(1) NOT NULL DEFAULT 1,
    `en_vedette` TINYINT(1) NOT NULL DEFAULT 0,
    `personnalisable` TINYINT(1) NOT NULL DEFAULT 0,
    `ordre` INT NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL,
    `updated_at` DATETIME DEFAULT NULL,
    UNIQUE INDEX `UNIQ_articles_slug` (`slug`),
    INDEX `IDX_articles_collection` (`collection_id`),
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Table: article_images
-- ============================================================
CREATE TABLE IF NOT EXISTS `article_images` (
    `id` INT AUTO_INCREMENT NOT NULL,
    `article_id` INT NOT NULL,
    `url` VARCHAR(500) NOT NULL,
    `alt` VARCHAR(300) DEFAULT NULL,
    `ordre` INT NOT NULL DEFAULT 0,
    INDEX `IDX_ai_article` (`article_id`),
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Table: variantes
-- ============================================================
CREATE TABLE IF NOT EXISTS `variantes` (
    `id` INT AUTO_INCREMENT NOT NULL,
    `article_id` INT NOT NULL,
    `nom` VARCHAR(200) NOT NULL,
    `sku` VARCHAR(100) DEFAULT NULL,
    `prix` DECIMAL(10,2) DEFAULT NULL,
    `stock` INT NOT NULL DEFAULT 0,
    `actif` TINYINT(1) NOT NULL DEFAULT 1,
    INDEX `IDX_var_article` (`article_id`),
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Table: commandes
-- ============================================================
CREATE TABLE IF NOT EXISTS `commandes` (
    `id` INT AUTO_INCREMENT NOT NULL,
    `numero` VARCHAR(50) NOT NULL,
    `client` JSON NOT NULL,
    `adresse_livraison` JSON NOT NULL,
    `articles` JSON NOT NULL,
    `total` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `mode_paiement` VARCHAR(50) DEFAULT NULL,
    `notes` LONGTEXT DEFAULT NULL,
    `statut` VARCHAR(50) NOT NULL DEFAULT 'en_attente',
    `created_at` DATETIME NOT NULL,
    `updated_at` DATETIME DEFAULT NULL,
    UNIQUE INDEX `UNIQ_commandes_numero` (`numero`),
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Table: messages
-- ============================================================
CREATE TABLE IF NOT EXISTS `messages` (
    `id` INT AUTO_INCREMENT NOT NULL,
    `nom` VARCHAR(200) NOT NULL,
    `email` VARCHAR(200) NOT NULL,
    `sujet` VARCHAR(300) DEFAULT NULL,
    `message` LONGTEXT NOT NULL,
    `lu` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Table: caracteristiques
-- ============================================================
CREATE TABLE IF NOT EXISTS `caracteristiques` (
    `id` INT AUTO_INCREMENT NOT NULL,
    `nom` VARCHAR(200) NOT NULL,
    `type` VARCHAR(50) NOT NULL DEFAULT 'text',
    `obligatoire` TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Table: caracteristique_valeurs
-- ============================================================
CREATE TABLE IF NOT EXISTS `caracteristique_valeurs` (
    `id` INT AUTO_INCREMENT NOT NULL,
    `caracteristique_id` INT NOT NULL,
    `valeur` VARCHAR(200) NOT NULL,
    INDEX `IDX_cv_carac` (`caracteristique_id`),
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Table: variante_templates
-- ============================================================
CREATE TABLE IF NOT EXISTS `variante_templates` (
    `id` INT AUTO_INCREMENT NOT NULL,
    `nom` VARCHAR(200) NOT NULL,
    `description` LONGTEXT DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Table: template_caracteristiques (join table)
-- ============================================================
CREATE TABLE IF NOT EXISTS `template_caracteristiques` (
    `variante_template_id` INT NOT NULL,
    `caracteristique_id` INT NOT NULL,
    INDEX `IDX_tc_template` (`variante_template_id`),
    INDEX `IDX_tc_carac` (`caracteristique_id`),
    PRIMARY KEY (`variante_template_id`, `caracteristique_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Table: doctrine_migration_versions (suivi migrations)
-- ============================================================
CREATE TABLE IF NOT EXISTS `doctrine_migration_versions` (
    `version` VARCHAR(191) NOT NULL,
    `executed_at` DATETIME DEFAULT NULL,
    `execution_time` INT DEFAULT NULL,
    PRIMARY KEY (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Clés étrangères
-- ============================================================

ALTER TABLE `product_collections`
    ADD CONSTRAINT `FK_pc_categorie`
    FOREIGN KEY (`categorie_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

ALTER TABLE `articles`
    ADD CONSTRAINT `FK_art_collection`
    FOREIGN KEY (`collection_id`) REFERENCES `product_collections` (`id`) ON DELETE SET NULL;

ALTER TABLE `article_images`
    ADD CONSTRAINT `FK_ai_article`
    FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`) ON DELETE CASCADE;

ALTER TABLE `variantes`
    ADD CONSTRAINT `FK_var_article`
    FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`) ON DELETE CASCADE;

ALTER TABLE `caracteristique_valeurs`
    ADD CONSTRAINT `FK_cv_carac`
    FOREIGN KEY (`caracteristique_id`) REFERENCES `caracteristiques` (`id`) ON DELETE CASCADE;

ALTER TABLE `template_caracteristiques`
    ADD CONSTRAINT `FK_tc_template`
    FOREIGN KEY (`variante_template_id`) REFERENCES `variante_templates` (`id`) ON DELETE CASCADE,
    ADD CONSTRAINT `FK_tc_carac`
    FOREIGN KEY (`caracteristique_id`) REFERENCES `caracteristiques` (`id`) ON DELETE CASCADE;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- Marquer la migration comme exécutée
-- ============================================================
INSERT INTO `doctrine_migration_versions` (`version`, `executed_at`, `execution_time`)
VALUES ('DoctrineMigrations\\Version20260217000000', NOW(), 0)
ON DUPLICATE KEY UPDATE `executed_at` = NOW();

-- ============================================================
-- TDBR - Données migrées depuis MongoDB
-- Généré le 2026-02-17 18:32:27
-- À exécuter APRÈS tdbr_schema.sql
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;
SET NAMES utf8mb4;

-- ============================================================
-- Catégories
-- ============================================================

INSERT INTO categories (id, nom, slug, description, image, ordre, actif, created_at) VALUES
(1, 'amuse-bouche', 'amuse-bouche', 'Miam miam miam', '/uploads/general/medium-LzH4RNxFmeiven0mGZi8t-1770932709205.webp', 0, 1, '2026-02-12 21:34:01'),
(2, 'Jouer le jeu', 'jouer-le-jeu', 'Des goodies sur le thème  du jeu de société !', '/uploads/general/medium-JT6KULaDHjsLKuvsqNizM-1771000172414.webp', 0, 1, '2026-02-13 16:29:32');

-- ============================================================
-- Collections
-- ============================================================

INSERT INTO product_collections (id, categorie_id, nom, slug, description, image, ordre, actif, created_at) VALUES
(1, 1, 'fromage', 'fromage', 'On va pas en faire tout un fromage', '/uploads/collections/medium-V9M-1AKgBUYJQxqLxGtcv-1770937936176.webp', 0, 1, '2026-02-12 21:56:42'),
(2, 2, 'Meeple Propaganda', 'meeple-propaganda', 'Une collection basé sur des images de meeples en style proche de vielles affiches de propagande.
Basé sur les addicts des couleurs ou des comportements typiques de joueurs', '/uploads/collections/medium-tZ0v8shmwy2WJLXVAHZzu-1771000933169.webp', 0, 1, '2026-02-13 16:42:32');

-- ============================================================
-- Articles
-- ============================================================

INSERT INTO articles (id, collection_id, nom, slug, description, prix_base, actif, en_vedette, personnalisable, ordre, created_at, updated_at) VALUES
(1, 1, 'T-shirt "Touche pas à ma meule !"', 't-shirt-touche-pas-a-ma-meule', 'Inspiré par les bonnes vieilles mobylettes Peugeot 103 de mon adolescence', 20, 1, 1, 0, 0, '2026-02-13 07:43:04', '2026-02-13 07:45:16'),
(2, 1, 'T-shirt "Tomme Rider"', 't-shirt-tomme-rider', 'Un hommage à la célèbre aventurière des jeux vidéos et des films.', 20, 1, 1, 0, 0, '2026-02-13 16:03:27', '2026-02-13 16:15:08'),
(3, 2, 'Mug "Meeple propaganda" blanc', 'mug-meeple-propaganda-blanc', 'Mug pour les joueurs qui aiment jouer planc existe en blanc ou en blanc/noir', 13, 1, 1, 0, 0, '2026-02-13 16:48:23', '2026-02-13 20:58:29'),
(4, 2, 'Mug-Meeple-propaganda-noir', 'mug-meeple-propaganda-noir', 'Mug pour les joueurs qui aiment jouer les pions noirs existe en blanc/noir', 13, 1, 0, 0, 0, '2026-02-13 20:39:50', '2026-02-13 21:17:16'),
(5, 2, 'Mug "Meeple propaganda" bleu', 'mug-meeple-propaganda-bleu', 'Mug pour les joueurs qui aiment jouer les pions bleu en blanc/bleu', 13, 1, 1, 0, 0, '2026-02-13 20:50:54', '2026-02-13 21:35:12'),
(6, 2, 'Mug "Meeple propaganda" vert', 'mug-meeple-propaganda-vert', 'Mug pour les joueurs qui aiment jouer vert existe en blanc ou en blanc/vert', 13, 1, 0, 0, 0, '2026-02-13 20:58:42', '2026-02-13 21:17:14'),
(7, 2, 'Mug "Meeple propaganda" jaune', 'mug-meeple-propaganda-jaune', 'Mug pour les joueurs qui aiment jouer jaune existe en blanc/jaune', 13, 1, 1, 0, 0, '2026-02-13 21:02:58', '2026-02-13 21:17:37'),
(8, 2, 'Mug "Meeple propaganda" LIcorne (toutes les couleurs)', 'mug-meeple-propaganda-licorne', 'Mug pour les joueurs qui n\'apportent pas d\'attention à la couleur ou qui aiment les licornes', 13, 1, 1, 0, 0, '2026-02-13 21:09:24', '2026-02-13 21:17:28'),
(9, 2, 'Mug "Meeple propaganda" rouge', 'mug-meeple-propaganda-rouge', 'Mug pour les joueurs qui aiment jouer les pions rouge en blanc/rouge', 13, 1, 1, 0, 0, '2026-02-13 21:33:20', '2026-02-13 21:35:18'),
(10, 2, 'Mug "Meeple propaganda" Daltonien', 'mug-meeple-propaganda-daltonien', 'Mug pour les joueurs daltoniens au sens du cowboy le plus rapide de l\'ouest', 13, 1, 0, 0, 0, '2026-02-13 21:35:28', '2026-02-13 21:38:39');

INSERT INTO article_images (id, article_id, url, alt, ordre) VALUES
(1, 1, '/uploads/articles/medium-C4mKmrN4mF7wmGmi62YiQ-1770940567508.webp', 'fgdsgdgdfgfdg', 0),
(2, 1, '/uploads/articles/medium-3fcuC9lQ6jxf4miZdqQay-1770940567799.webp', 'fgdsgdgdfgfdg', 1),
(3, 1, '/uploads/articles/medium-OlMRXl_yZsofeQK6wYfvZ-1770940568054.webp', 'fgdsgdgdfgfdg', 2),
(4, 1, '/uploads/articles/medium-OAF_kOwh2Rf32ORJg1ZOK-1770940568321.webp', 'fgdsgdgdfgfdg', 3),
(5, 1, '/uploads/articles/medium-r0SQOKLyg4I1ElV5nQ-o6-1770940568575.webp', 'fgdsgdgdfgfdg', 4),
(6, 1, '/uploads/articles/medium-ERmizk8OKdXb4HLwKQCRg-1770940568828.webp', 'fgdsgdgdfgfdg', 5),
(7, 2, '/uploads/articles/medium-7AerZtEdwXMyBOhoC0ry6-1770998925766.webp', 'T-shirt "Tomme Rider"', 0),
(8, 2, '/uploads/articles/medium-9i0KZjxguAh5kSdXQTtxw-1770998926169.webp', 'T-shirt "Tomme Rider"', 1),
(9, 2, '/uploads/articles/medium-rP7BtxHhKVOe5cCE9Mx76-1770998926581.webp', 'T-shirt "Tomme Rider"', 2),
(10, 2, '/uploads/articles/medium-LI4oBPjdED0AoYRHnKADQ-1770998927016.webp', 'T-shirt "Tomme Rider"', 3),
(11, 2, '/uploads/articles/medium-v-VX_gZHTEuXldiBc02fj-1770998927582.webp', 'T-shirt "Tomme Rider"', 4),
(12, 2, '/uploads/articles/medium-nsOVzLBBXRMIIgEuTS8Eb-1770998928192.webp', 'T-shirt "Tomme Rider"', 5),
(13, 2, '/uploads/articles/medium-D-FBhmJkoIJ0pxAGYw00y-1770998928567.webp', 'T-shirt "Tomme Rider"', 6),
(14, 3, '/uploads/articles/medium-25MmrmcFm8fcJTOXs1XtQ-1771001287798.webp', 'Mug-Meeple-propaganda-blanc', 0),
(15, 4, '/uploads/articles/medium-inO2RtBG0FPIQklAvvjBb-1771015806067.webp', 'Mug-Meeple-propaganda-noir', 0),
(16, 5, '/uploads/articles/medium-S6zH4090oByBWJZjqwnuO-1771016207120.webp', 'Mug Meeple propaganda bleu(Copie)', 0),
(17, 6, '/uploads/articles/medium-zg0VwWNXZyoq3KNzlwGXL-1771016505719.webp', 'Mug "Meeple propaganda" vert', 0),
(18, 7, '/uploads/articles/medium-ZiOffAC5wgFQNsq2B5_bF-1771016844471.webp', 'Mug "Meeple propaganda" jaune', 0),
(19, 8, '/uploads/articles/medium-huA55mghRemfd5gIptTZS-1771017040956.webp', 'Mug "Meeple propaganda" LIcorne (toutes les couleurs)', 0),
(20, 9, '/uploads/articles/medium-Ni7_j-pV32Z0pT2oZRslY-1771018441887.webp', 'Mug "Meeple propaganda" rouge', 0),
(21, 10, '/uploads/articles/medium-FdYjihL9OzAlZN5DS-hH--1771018692609.webp', 'Mug "Meeple propaganda" Daltonien', 0);

INSERT INTO variantes (id, article_id, nom, sku, prix, stock, actif) VALUES
(1, 1, 'S Sable', 'FGDSGDGDFGFDG-CPY-584087-0', 20, 0, 1),
(2, 1, 'M Sable', 'FGDSGDGDFGFDG-CPY-584087-1', 20, 0, 1),
(3, 1, 'L Sable', 'FGDSGDGDFGFDG-CPY-584087-2', 20, 0, 1),
(4, 1, 'XL Sable', 'FGDSGDGDFGFDG-CPY-584087-3', 20, 0, 1),
(5, 1, '2XL Sable', 'FGDSGDGDFGFDG-CPY-584087-4', 20, 0, 1),
(6, 2, 'S Brown Savana', 'T-SHIRT-TOMME-RIDER-S-BROWN-SAVANA-001', 20, 0, 1),
(7, 2, 'M Brown Savana', 'T-SHIRT-TOMME-RIDER-M-BROWN-SAVANA-002', 20, 0, 1),
(8, 2, 'L Brown Savana', 'T-SHIRT-TOMME-RIDER-L-BROWN-SAVANA-003', 20, 0, 1),
(9, 2, 'XL Brown Savana', 'T-SHIRT-TOMME-RIDER-XL-BROWN-SAVANA-004', 20, 0, 1),
(10, 2, '2XL Brown Savana', 'T-SHIRT-TOMME-RIDER-2XL-BROWN-SAVANA-005', 20, 0, 1),
(11, 2, '3XL Brown Savana', 'T-SHIRT-TOMME-RIDER-3XL-BROWN-SAVANA-006', 20, 0, 1),
(12, 3, 'Blanc', 'MUG-MEEPLE-PROPAGANDA-BLANC-BLANC-001', 13, 0, 1),
(13, 3, 'Bicolore Blanc/noir', 'MUG-MEEPLE-PROPAGANDA-BLANC-BICOLORE-BLANC-NOIR-002', 13, 0, 1),
(14, 4, 'Bicolore Blanc/noir', 'MUG-MEEPLE-PROPAGANDA-BLANC-COPIE-BICOLORE-BLANC-NOIR-001', 13, 0, 1),
(15, 5, 'Bicolore Blanc/bleu', 'MUG-MEEPLE-PROPAGANDA-BLEU-BICOLORE-BLANC-BLEU-001', 13, 0, 1),
(16, 6, 'Bicolore Blanc/vert', 'MUG-MEEPLE-PROPAGANDA-VERT-BICOLORE-BLANC-VERT-001', 13, 0, 1),
(17, 7, 'Bicolore Blanc/jaune', 'MUG-MEEPLE-PROPAGANDA-JAUNE-BICOLORE-BLANC-JAUNE-001', 13, 0, 1),
(18, 8, 'Bicolore Blanc/rose', 'MUG-MEEPLE-PROPAGANDA-LICORNE-BICOLORE-BLANC-ROSE-001', 13, 0, 1),
(19, 9, 'Bicolore Blanc/rouge', 'MUG-MEEPLE-PROPAGANDA-ROUGE-BICOLORE-BLANC-ROUGE-001', 13, 0, 1),
(20, 10, 'Bicolore Blanc/jaune', 'MUG-MEEPLE-PROPAGANDA-DALTONIEN-BICOLORE-BLANC-JAUNE-001', 13, 0, 1),
(21, 10, 'Bicolore Blanc/noir', 'MUG-MEEPLE-PROPAGANDA-DALTONIEN-BICOLORE-BLANC-NOIR-002', 13, 0, 1);

-- ============================================================
-- Commandes
-- ============================================================

INSERT INTO commandes (id, numero, client, adresse_livraison, articles, total, mode_paiement, notes, statut, created_at, updated_at) VALUES
(1, 'CMD-28748EE0', '[]', '{"nom":"Chauveau","prenom":"Emmanuel","rue":"39 Bd Victor Hugo","complement":"Res Leopoldine B36","codePostal":"31770","ville":"Colomiers","pays":"France","_id":"698e6ddd1b91914dd423ccd4"}', '[{"article":"698e68aa579a8eb2ffa2c11c","articleNom":"fgdsgdgdfgfdg","articleSlug":"fgdsgdgdfgfdg","variante":{"sku":"FGDSGDGDFGFDG-S-SABLE-001","nom":"S Sable"},"quantite":1,"prixUnitaire":20,"total":20,"image":"\\/uploads\\/articles\\/medium-C4mKmrN4mF7wmGmi62YiQ-1770940567508.webp","_id":"698e6ddd1b91914dd423ccd3"}]', 25.9, NULL, NULL, 'en_attente', '2026-02-13 00:18:37', '2026-02-13 00:18:37');

-- ============================================================
-- Messages
-- ============================================================

INSERT INTO messages (id, nom, email, sujet, message, lu, created_at) VALUES
(1, 'Emmanuel Chauveau', 'emmanuel.chauveau@gmail.com', 'Information produit', 'xvcvcvcbvcx', 0, '2026-02-10 05:22:16');

-- ============================================================
-- Caractéristiques
-- ============================================================

INSERT INTO caracteristiques (id, nom, type, obligatoire) VALUES
(1, 'Taille T-shirt', 'text', 0),
(2, 'Couleur T-shirt Vistaprint', 'select', 0),
(3, 'Couleurs Mugs', 'color', 1);

INSERT INTO caracteristique_valeurs (id, caracteristique_id, valeur) VALUES
(1, 1, 'XS'),
(2, 1, 'S'),
(3, 1, 'M'),
(4, 1, 'L'),
(5, 1, 'XL'),
(6, 1, '2XL'),
(7, 1, '3XL'),
(8, 2, 'Blanc'),
(9, 2, 'Noir'),
(10, 2, 'Rouge'),
(11, 2, 'Sable'),
(12, 2, 'Brown Savana'),
(13, 3, 'Blanc'),
(14, 3, 'Noir'),
(15, 3, 'Bicolore Blanc/noir'),
(16, 3, 'Bicolore Blanc/rouge'),
(17, 3, 'Bicolore Blanc/bleu'),
(18, 3, 'Bicolore Blanc/jaune'),
(19, 3, 'Bicolore Blanc/vert'),
(20, 3, 'Bicolore Blanc/orange'),
(21, 3, 'Bicolore Blanc/rose');

-- ============================================================
-- Utilisateurs
-- ============================================================

INSERT INTO `users` (id, email, roles, password, prenom, nom, telephone, created_at) VALUES
(1, 'admin@tdbr.fr', '["ROLE_USER"]', '$2y$13$bEln2gPrgbKmL2RDo/udMe2DJeS7epIIzK3Nh0C2zhBvg7Dkou0cm', 'Admin', 'TDBR', NULL, '2026-02-17 05:17:24');

-- ============================================================
-- Templates variantes
-- ============================================================

SET FOREIGN_KEY_CHECKS = 1;

-- Migration terminée le 2026-02-17 18:32:27

<?php
/**
 * Script de génération des INSERTs MySQL depuis MongoDB
 * Usage: php generate_inserts.php
 * Sortie: tdbr_data.sql
 */

require_once __DIR__ . '/vendor/autoload.php';

$mongoUri   = 'mongodb+srv://tdbr_db_user:tTQw17RApRlMPjkf@tdbr.x5g60ng.mongodb.net/?appName=TDBR&serverSelectionTimeoutMS=30000&connectTimeoutMS=30000';
$dbName     = 'test';
$outputFile = __DIR__ . '/tdbr_data.sql';

echo "Connexion à MongoDB...\n";

try {
    $client = new MongoDB\Client($mongoUri);
    $db     = $client->selectDatabase($dbName);
    $client->selectDatabase('admin')->command(['ping' => 1]);
    echo "Connecté !\n";
} catch (Exception $e) {
    echo "Erreur MongoDB : " . $e->getMessage() . "\n";
    exit(1);
}

$out = fopen($outputFile, 'w');

fwrite($out, "-- ============================================================\n");
fwrite($out, "-- TDBR - Données migrées depuis MongoDB\n");
fwrite($out, "-- Généré le " . date('Y-m-d H:i:s') . "\n");
fwrite($out, "-- À exécuter APRÈS tdbr_schema.sql\n");
fwrite($out, "-- ============================================================\n\n");

fwrite($out, "SET FOREIGN_KEY_CHECKS = 0;\n");
fwrite($out, "SET NAMES utf8mb4;\n\n");

// ─── Helpers ───────────────────────────────────────────────────────────────

function esc(mixed $val): string
{
    if ($val === null) return 'NULL';
    if (is_bool($val)) return $val ? '1' : '0';
    if (is_int($val) || is_float($val)) return (string)$val;
    $str = (string)$val;
    $str = str_replace("\\", "\\\\", $str);
    $str = str_replace("'",  "\\'",  $str);
    $str = str_replace("\0", "",     $str);
    return "'" . $str . "'";
}

function escJson(mixed $val): string
{
    if ($val === null) return "'{}'";
    $arr = bsonToArray($val);
    return esc(json_encode($arr, JSON_UNESCAPED_UNICODE));
}

function toDatetime(mixed $val): string
{
    if ($val === null) return esc(date('Y-m-d H:i:s'));
    if ($val instanceof MongoDB\BSON\UTCDateTime) {
        return esc($val->toDateTime()->format('Y-m-d H:i:s'));
    }
    if (is_string($val)) {
        try { return esc((new DateTime($val))->format('Y-m-d H:i:s')); } catch (Exception $e) {}
    }
    return esc(date('Y-m-d H:i:s'));
}

function toDatetimeOrNull(mixed $val): string
{
    if ($val === null) return 'NULL';
    return toDatetime($val);
}

function bsonToArray(mixed $val): array
{
    if ($val === null) return [];
    if (is_array($val)) return $val;
    if ($val instanceof MongoDB\Model\BSONArray || $val instanceof MongoDB\Model\BSONDocument) {
        $arr = [];
        foreach ($val as $k => $v) {
            if ($v instanceof MongoDB\Model\BSONArray || $v instanceof MongoDB\Model\BSONDocument) {
                $arr[$k] = bsonToArray($v);
            } elseif ($v instanceof MongoDB\BSON\ObjectId) {
                $arr[$k] = (string)$v;
            } elseif ($v instanceof MongoDB\BSON\UTCDateTime) {
                $arr[$k] = $v->toDateTime()->format('Y-m-d H:i:s');
            } else {
                $arr[$k] = $v;
            }
        }
        return $arr;
    }
    return (array)$val;
}

function oid(mixed $val): ?string
{
    if ($val instanceof MongoDB\BSON\ObjectId) return (string)$val;
    if (is_string($val)) return $val;
    return null;
}

function slugify(string $text): string
{
    $text = mb_strtolower($text);
    $map  = ['à'=>'a','á'=>'a','â'=>'a','ä'=>'a','è'=>'e','é'=>'e','ê'=>'e','ë'=>'e',
             'ì'=>'i','í'=>'i','î'=>'i','ï'=>'i','ò'=>'o','ó'=>'o','ô'=>'o','ö'=>'o',
             'ù'=>'u','ú'=>'u','û'=>'u','ü'=>'u','ç'=>'c','ñ'=>'n'];
    $text = strtr($text, $map);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim($text ?? '', '-');
}

// ─── Catégories ────────────────────────────────────────────────────────────

echo "Migration catégories...\n";
fwrite($out, "-- ============================================================\n");
fwrite($out, "-- Catégories\n");
fwrite($out, "-- ============================================================\n");
fwrite($out, "TRUNCATE TABLE categories;\n\n");

$categoriesById = []; // ObjectId string → MySQL int id
$catId = 1;
$catInserts = [];

foreach ($db->selectCollection('categories')->find() as $doc) {
    $mongoId = oid($doc['_id']);
    $nom  = (string)($doc['nom'] ?? '');
    $slug = (string)($doc['slug'] ?? slugify($nom));
    $desc = isset($doc['description']) && $doc['description'] !== '' ? esc((string)$doc['description']) : 'NULL';
    $img  = isset($doc['image']) && $doc['image'] !== '' ? esc((string)$doc['image']) : 'NULL';
    $ord  = (int)($doc['ordre'] ?? 0);
    $act  = ($doc['actif'] ?? true) ? 1 : 0;
    $cat  = toDatetime($doc['createdAt'] ?? null);

    $catInserts[] = "($catId, " . esc($nom) . ", " . esc($slug) . ", $desc, $img, $ord, $act, $cat)";
    $categoriesById[$mongoId] = $catId;
    $catId++;
}

if ($catInserts) {
    fwrite($out, "INSERT INTO categories (id, nom, slug, description, image, ordre, actif, created_at) VALUES\n");
    fwrite($out, implode(",\n", $catInserts) . ";\n\n");
}

echo "  " . ($catId - 1) . " catégorie(s)\n";

// ─── Collections ───────────────────────────────────────────────────────────

echo "Migration collections...\n";
fwrite($out, "-- ============================================================\n");
fwrite($out, "-- Collections\n");
fwrite($out, "-- ============================================================\n");
fwrite($out, "TRUNCATE TABLE product_collections;\n\n");

$collectionsById = []; // ObjectId string → MySQL int id
$collId = 1;
$collInserts = [];

foreach ($db->selectCollection('collections')->find() as $doc) {
    $mongoId  = oid($doc['_id']);
    $nom      = (string)($doc['nom'] ?? '');
    $slug     = (string)($doc['slug'] ?? slugify($nom));
    $desc     = isset($doc['description']) && $doc['description'] !== '' ? esc((string)$doc['description']) : 'NULL';
    $img      = isset($doc['image']) && $doc['image'] !== '' ? esc((string)$doc['image']) : 'NULL';
    $ord      = (int)($doc['ordre'] ?? 0);
    $act      = ($doc['actif'] ?? true) ? 1 : 0;
    $creAt    = toDatetime($doc['createdAt'] ?? null);

    // FK catégorie par ObjectId
    $catFk = 'NULL';
    if (isset($doc['categorie'])) {
        $catOid = oid($doc['categorie']);
        if ($catOid && isset($categoriesById[$catOid])) {
            $catFk = $categoriesById[$catOid];
        }
    }

    $collInserts[] = "($collId, $catFk, " . esc($nom) . ", " . esc($slug) . ", $desc, $img, $ord, $act, $creAt)";
    $collectionsById[$mongoId] = $collId;
    $collId++;
}

if ($collInserts) {
    fwrite($out, "INSERT INTO product_collections (id, categorie_id, nom, slug, description, image, ordre, actif, created_at) VALUES\n");
    fwrite($out, implode(",\n", $collInserts) . ";\n\n");
}

echo "  " . ($collId - 1) . " collection(s)\n";

// ─── Articles ──────────────────────────────────────────────────────────────

echo "Migration articles...\n";
fwrite($out, "-- ============================================================\n");
fwrite($out, "-- Articles\n");
fwrite($out, "-- ============================================================\n");
fwrite($out, "TRUNCATE TABLE article_images;\n");
fwrite($out, "TRUNCATE TABLE variantes;\n");
fwrite($out, "TRUNCATE TABLE articles;\n\n");

$artId  = 1;
$imgId  = 1;
$varId  = 1;

$articleInserts  = [];
$imageInserts    = [];
$varianteInserts = [];

foreach ($db->selectCollection('articles')->find() as $doc) {
    $nom   = (string)($doc['nom'] ?? '');
    $slug  = (string)($doc['slug'] ?? slugify($nom));
    $desc  = isset($doc['description']) && $doc['description'] !== '' ? esc((string)$doc['description']) : 'NULL';
    $prix  = (float)($doc['prixBase'] ?? $doc['prix'] ?? 0);
    $act   = ($doc['actif'] ?? true) ? 1 : 0;
    $ved   = ($doc['enVedette'] ?? false) ? 1 : 0;
    $pers  = ($doc['personnalisable'] ?? false) ? 1 : 0;
    $ord   = (int)($doc['ordre'] ?? 0);
    $creAt = toDatetime($doc['createdAt'] ?? null);
    $upAt  = toDatetimeOrNull($doc['updatedAt'] ?? null);

    // FK collection par ObjectId
    $collFk = 'NULL';
    if (isset($doc['collection'])) {
        $collOid = oid($doc['collection']);
        if ($collOid && isset($collectionsById[$collOid])) {
            $collFk = $collectionsById[$collOid];
        }
    }

    $articleInserts[] = "($artId, $collFk, " . esc($nom) . ", " . esc($slug) . ", $desc, $prix, $act, $ved, $pers, $ord, $creAt, $upAt)";

    // Images
    $images = bsonToArray($doc['images'] ?? null);
    $imgOrd = 0;
    foreach ($images as $imgData) {
        if (is_string($imgData) && $imgData !== '') {
            $url = $imgData;
            $alt = $nom;
        } elseif (is_array($imgData)) {
            $url = (string)($imgData['url'] ?? $imgData[0] ?? '');
            $alt = (string)($imgData['alt'] ?? $nom);
        } else {
            continue;
        }
        if (!$url) continue;
        $imageInserts[] = "($imgId, $artId, " . esc($url) . ", " . esc($alt) . ", $imgOrd)";
        $imgId++;
        $imgOrd++;
    }

    // Variantes
    $variantes = bsonToArray($doc['variantes'] ?? null);
    foreach ($variantes as $varData) {
        if (!is_array($varData)) continue;
        $vNom  = (string)($varData['nom'] ?? 'Standard');
        $vSku  = isset($varData['sku']) && $varData['sku'] !== '' ? esc((string)$varData['sku']) : 'NULL';
        $vPrix = isset($varData['prix']) && is_numeric($varData['prix']) ? (float)$varData['prix'] : 'NULL';
        $vSto  = (int)($varData['stock'] ?? 0);
        $vAct  = ($varData['actif'] ?? true) ? 1 : 0;
        $varianteInserts[] = "($varId, $artId, " . esc($vNom) . ", $vSku, $vPrix, $vSto, $vAct)";
        $varId++;
    }

    $artId++;

    // Flush par batch de 50
    if (count($articleInserts) >= 50) {
        fwrite($out, "INSERT INTO articles (id, collection_id, nom, slug, description, prix_base, actif, en_vedette, personnalisable, ordre, created_at, updated_at) VALUES\n");
        fwrite($out, implode(",\n", $articleInserts) . ";\n\n");
        $articleInserts = [];

        if ($imageInserts) {
            fwrite($out, "INSERT INTO article_images (id, article_id, url, alt, ordre) VALUES\n");
            fwrite($out, implode(",\n", $imageInserts) . ";\n\n");
            $imageInserts = [];
        }

        if ($varianteInserts) {
            fwrite($out, "INSERT INTO variantes (id, article_id, nom, sku, prix, stock, actif) VALUES\n");
            fwrite($out, implode(",\n", $varianteInserts) . ";\n\n");
            $varianteInserts = [];
        }
    }
}

// Flush restants
if ($articleInserts) {
    fwrite($out, "INSERT INTO articles (id, collection_id, nom, slug, description, prix_base, actif, en_vedette, personnalisable, ordre, created_at, updated_at) VALUES\n");
    fwrite($out, implode(",\n", $articleInserts) . ";\n\n");
}
if ($imageInserts) {
    fwrite($out, "INSERT INTO article_images (id, article_id, url, alt, ordre) VALUES\n");
    fwrite($out, implode(",\n", $imageInserts) . ";\n\n");
}
if ($varianteInserts) {
    fwrite($out, "INSERT INTO variantes (id, article_id, nom, sku, prix, stock, actif) VALUES\n");
    fwrite($out, implode(",\n", $varianteInserts) . ";\n\n");
}

echo "  " . ($artId - 1) . " article(s), " . ($imgId - 1) . " image(s), " . ($varId - 1) . " variante(s)\n";

// ─── Commandes ─────────────────────────────────────────────────────────────

echo "Migration commandes...\n";
fwrite($out, "-- ============================================================\n");
fwrite($out, "-- Commandes\n");
fwrite($out, "-- ============================================================\n");
fwrite($out, "TRUNCATE TABLE commandes;\n\n");

$cmdId = 1;
$cmdInserts = [];

foreach ($db->selectCollection('commandes')->find() as $doc) {
    $num    = esc((string)($doc['numero'] ?? 'CMD-' . strtoupper(substr(md5((string)$doc['_id']), 0, 8))));
    $client = escJson($doc['client'] ?? []);
    $addr   = escJson($doc['adresseLivraison'] ?? $doc['adresse'] ?? []);
    $arts   = escJson($doc['articles'] ?? $doc['lignes'] ?? []);
    $total  = (float)($doc['total'] ?? $doc['montantTotal'] ?? 0);
    $mode   = isset($doc['modePaiement']) && $doc['modePaiement'] !== '' ? esc((string)$doc['modePaiement']) : 'NULL';
    $notes  = isset($doc['notes']) && $doc['notes'] !== '' ? esc((string)$doc['notes']) : 'NULL';
    $stat   = esc((string)($doc['statut'] ?? 'en_attente'));
    $creAt  = toDatetime($doc['createdAt'] ?? null);
    $upAt   = toDatetimeOrNull($doc['updatedAt'] ?? null);

    $cmdInserts[] = "($cmdId, $num, $client, $addr, $arts, $total, $mode, $notes, $stat, $creAt, $upAt)";
    $cmdId++;
}

if ($cmdInserts) {
    fwrite($out, "INSERT INTO commandes (id, numero, client, adresse_livraison, articles, total, mode_paiement, notes, statut, created_at, updated_at) VALUES\n");
    fwrite($out, implode(",\n", $cmdInserts) . ";\n\n");
}

echo "  " . ($cmdId - 1) . " commande(s)\n";

// ─── Messages ──────────────────────────────────────────────────────────────

echo "Migration messages...\n";
fwrite($out, "-- ============================================================\n");
fwrite($out, "-- Messages\n");
fwrite($out, "-- ============================================================\n");
fwrite($out, "TRUNCATE TABLE messages;\n\n");

$msgId = 1;
$msgInserts = [];

foreach ($db->selectCollection('messages')->find() as $doc) {
    $nom   = esc((string)($doc['nom'] ?? ''));
    $email = esc((string)($doc['email'] ?? ''));
    $sujet = isset($doc['sujet']) && $doc['sujet'] !== '' ? esc((string)$doc['sujet']) : 'NULL';
    $msg   = esc((string)($doc['message'] ?? ''));
    $lu    = ($doc['lu'] ?? false) ? 1 : 0;
    $creAt = toDatetime($doc['createdAt'] ?? null);

    $msgInserts[] = "($msgId, $nom, $email, $sujet, $msg, $lu, $creAt)";
    $msgId++;
}

if ($msgInserts) {
    fwrite($out, "INSERT INTO messages (id, nom, email, sujet, message, lu, created_at) VALUES\n");
    fwrite($out, implode(",\n", $msgInserts) . ";\n\n");
}

echo "  " . ($msgId - 1) . " message(s)\n";

// ─── Caractéristiques ──────────────────────────────────────────────────────

echo "Migration caractéristiques...\n";
fwrite($out, "-- ============================================================\n");
fwrite($out, "-- Caractéristiques\n");
fwrite($out, "-- ============================================================\n");
fwrite($out, "TRUNCATE TABLE caracteristique_valeurs;\n");
fwrite($out, "TRUNCATE TABLE caracteristiques;\n\n");

$caracId  = 1;
$valId    = 1;
$caracMap = []; // nom → MySQL id (pour templates)

$caracInserts = [];
$valInserts   = [];

foreach ($db->selectCollection('caracteristiques')->find() as $doc) {
    $nom  = (string)($doc['nom'] ?? '');
    $type = (string)($doc['type'] ?? 'text');
    $obl  = ($doc['obligatoire'] ?? false) ? 1 : 0;

    $caracInserts[] = "($caracId, " . esc($nom) . ", " . esc($type) . ", $obl)";
    $caracMap[$nom] = $caracId;

    $valeurs = bsonToArray($doc['valeurs'] ?? null);
    foreach ($valeurs as $v) {
        if (!is_string($v) && !is_numeric($v)) continue;
        $valInserts[] = "($valId, $caracId, " . esc((string)$v) . ")";
        $valId++;
    }

    $caracId++;
}

if ($caracInserts) {
    fwrite($out, "INSERT INTO caracteristiques (id, nom, type, obligatoire) VALUES\n");
    fwrite($out, implode(",\n", $caracInserts) . ";\n\n");
}
if ($valInserts) {
    fwrite($out, "INSERT INTO caracteristique_valeurs (id, caracteristique_id, valeur) VALUES\n");
    fwrite($out, implode(",\n", $valInserts) . ";\n\n");
}

echo "  " . ($caracId - 1) . " caractéristique(s), " . ($valId - 1) . " valeur(s)\n";

// ─── Utilisateurs ──────────────────────────────────────────────────────────

echo "Migration utilisateurs...\n";
fwrite($out, "-- ============================================================\n");
fwrite($out, "-- Utilisateurs\n");
fwrite($out, "-- ============================================================\n");
fwrite($out, "TRUNCATE TABLE `users`;\n\n");

$usrId = 1;
$usrInserts = [];

foreach (['users', 'utilisateurs'] as $collName) {
    try {
        foreach ($db->selectCollection($collName)->find() as $doc) {
            $email  = esc((string)($doc['email'] ?? ''));
            $pwd    = esc((string)($doc['password'] ?? ''));
            $prenom = isset($doc['prenom']) && $doc['prenom'] !== '' ? esc((string)$doc['prenom']) : 'NULL';
            $nom    = isset($doc['nom']) && $doc['nom'] !== '' ? esc((string)$doc['nom']) : 'NULL';
            $tel    = isset($doc['telephone']) && $doc['telephone'] !== '' ? esc((string)$doc['telephone']) : 'NULL';
            $role   = (string)($doc['role'] ?? 'ROLE_USER');
            $roles  = esc(json_encode([$role]));
            $creAt  = toDatetime($doc['createdAt'] ?? null);

            $usrInserts[] = "($usrId, $email, $roles, $pwd, $prenom, $nom, $tel, $creAt)";
            $usrId++;
        }
    } catch (Exception $e) { /* Collection n'existe pas */ }
}

if ($usrInserts) {
    fwrite($out, "INSERT INTO `users` (id, email, roles, password, prenom, nom, telephone, created_at) VALUES\n");
    fwrite($out, implode(",\n", $usrInserts) . ";\n\n");
}

echo "  " . ($usrId - 1) . " utilisateur(s)\n";

// ─── Templates variantes ───────────────────────────────────────────────────

echo "Migration templates variantes...\n";
fwrite($out, "-- ============================================================\n");
fwrite($out, "-- Templates variantes\n");
fwrite($out, "-- ============================================================\n");
fwrite($out, "TRUNCATE TABLE template_caracteristiques;\n");
fwrite($out, "TRUNCATE TABLE variante_templates;\n\n");

$tplId = 1;
$tplInserts  = [];
$joinInserts = [];

foreach ($db->selectCollection('variante_templates')->find() as $doc) {
    $nom  = (string)($doc['nom'] ?? '');
    $desc = isset($doc['description']) && $doc['description'] !== '' ? esc((string)$doc['description']) : 'NULL';
    $tplInserts[] = "($tplId, " . esc($nom) . ", $desc)";

    $caracs = bsonToArray($doc['caracteristiques'] ?? null);
    foreach ($caracs as $cNom) {
        if (isset($caracMap[(string)$cNom])) {
            $joinInserts[] = "($tplId, " . $caracMap[(string)$cNom] . ")";
        }
    }

    $tplId++;
}

if ($tplInserts) {
    fwrite($out, "INSERT INTO variante_templates (id, nom, description) VALUES\n");
    fwrite($out, implode(",\n", $tplInserts) . ";\n\n");
}
if ($joinInserts) {
    fwrite($out, "INSERT INTO template_caracteristiques (variante_template_id, caracteristique_id) VALUES\n");
    fwrite($out, implode(",\n", $joinInserts) . ";\n\n");
}

echo "  " . ($tplId - 1) . " template(s)\n";

// ─── Fin ───────────────────────────────────────────────────────────────────

fwrite($out, "SET FOREIGN_KEY_CHECKS = 1;\n\n");
fwrite($out, "-- Migration terminée le " . date('Y-m-d H:i:s') . "\n");

fclose($out);

echo "\nFichier généré : tdbr_data.sql\n";
echo "Résumé : " . ($catId-1) . " cat | " . ($collId-1) . " coll | " . ($artId-1) . " art | " . ($imgId-1) . " img | " . ($varId-1) . " var | " . ($cmdId-1) . " cmd | " . ($msgId-1) . " msg | " . ($caracId-1) . " carac | " . ($usrId-1) . " users | " . ($tplId-1) . " tpl\n";

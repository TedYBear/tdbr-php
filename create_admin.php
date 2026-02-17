<?php

require __DIR__ . '/vendor/autoload.php';

use MongoDB\Client;

// Configuration depuis .env.local
$mongoUri = 'mongodb+srv://tdbr_db_user:tTQw17RApRlMPjkf@tdbr.x5g60ng.mongodb.net/?appName=TDBR';
$dbName = 'test';

// Hash du mot de passe Admin123!
$passwordHash = '$2y$13$ulxaXzDckSgCynav9DaVfOQvDJfIR8KYjCeThSofIR2g1UsUB7JxS';

echo "Connexion à MongoDB...\n";
$client = new Client($mongoUri);
$database = $client->selectDatabase($dbName);
$collection = $database->selectCollection('utilisateurs');

// Vérifier si l'utilisateur existe déjà
$existingUser = $collection->findOne(['email' => 'admin@tdbr.fr']);

if ($existingUser) {
    echo "⚠️  L'utilisateur admin@tdbr.fr existe déjà.\n";
    echo "Mise à jour du mot de passe...\n";

    $result = $collection->updateOne(
        ['email' => 'admin@tdbr.fr'],
        [
            '$set' => [
                'password' => $passwordHash,
                'role' => 'admin',
                'prenom' => 'Admin',
                'nom' => 'TDBR',
                'updatedAt' => new MongoDB\BSON\UTCDateTime()
            ]
        ]
    );

    echo "✅ Utilisateur mis à jour avec succès!\n";
} else {
    echo "Création de l'utilisateur admin...\n";

    $result = $collection->insertOne([
        'email' => 'admin@tdbr.fr',
        'password' => $passwordHash,
        'role' => 'admin',
        'prenom' => 'Admin',
        'nom' => 'TDBR',
        'createdAt' => new MongoDB\BSON\UTCDateTime(),
    ]);

    echo "✅ Utilisateur créé avec succès!\n";
}

echo "\n";
echo "📧 Email: admin@tdbr.fr\n";
echo "🔑 Mot de passe: Admin123!\n";
echo "👤 Rôle: ROLE_ADMIN\n";
echo "\nVous pouvez maintenant vous connecter sur http://localhost:8000/connexion\n";

<?php

$password = 'Admin123!';
$hash = '$2y$13$ulxaXzDckSgCynav9DaVfOQvDJfIR8KYjCeThSofIR2g1UsUB7JxS';

echo "Test de vérification du mot de passe\n";
echo "=====================================\n\n";

echo "Mot de passe testé: '$password'\n";
echo "Hash dans MongoDB: $hash\n\n";

if (password_verify($password, $hash)) {
    echo "✅ Le mot de passe correspond au hash!\n";
    echo "   → Le problème n'est pas le mot de passe\n";
} else {
    echo "❌ Le mot de passe ne correspond PAS au hash!\n";
    echo "   → Soit le mot de passe est incorrect\n";
    echo "   → Soit le hash est incorrect\n";
}

echo "\n";
echo "Génération d'un nouveau hash pour Admin123!:\n";
$newHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 13]);
echo "$newHash\n";

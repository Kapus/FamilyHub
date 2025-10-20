<?php
// Temporärt diagnostiskt skript för att kontrollera admin-lösenordets hash
// Använd: öppna i webbläsaren t.ex. http://localhost/FamilyHub/tools/check_password.php
// OBS: Ta bort denna fil direkt efter användning för säkerhet.

require_once __DIR__ . '/../config.php';

echo "<pre>";
$email = 'admin@example.com';
$testPassword = 'Admin123!'; // Byt här om du testat ett annat lösenord

$stmt = $pdo->prepare('SELECT id, namn, email, losenord, roll FROM users WHERE email = ? LIMIT 1');
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user) {
    echo "Användare med e-post $email hittades inte. Kontrollera att raden finns i users-tabellen.\n";
    echo "\nKontrollerade SELECT-resultat:\n";
    var_export($user);
    echo "</pre>";
    exit;
}

echo "Hittade användare: " . $user['namn'] . " (role=" . $user['roll'] . ")\n";
echo "Hash (kolumn losenord): " . $user['losenord'] . "\n\n";

$verified = password_verify($testPassword, $user['losenord']);
echo "password_verify('$testPassword', hash) => " . ($verified ? 'true' : 'false') . "\n";

if (!$verified) {
    echo "\nOm resultatet är false: skapa en ny hash med password_hash() i PHP och uppdatera raden i phpMyAdmin eller använd adminpanelen för att skapa en ny admin.\n";
}

echo "</pre>";

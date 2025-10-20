<?php
// config.php
// Centraliserad databasanslutning med PDO
session_start();

define('DB_HOST', 'localhost');
define('DB_NAME', 'test');
define('DB_USER', 'root');
define('DB_PASS', '');

try {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die('Kunde inte ansluta till databasen: ' . $e->getMessage());
}

// Hjälpfunktion för att säkra att användaren är inloggad
function require_login(): void
{
    if (empty($_SESSION['user'])) {
        header('Location: index.php');
        exit;
    }
}

// Hjälpfunktion för att säkra att användaren är admin
function require_admin(): void
{
    require_login();
    if (($_SESSION['user']['role'] ?? '') !== 'ADMIN') {
        header('Location: dashboard.php');
        exit;
    }
}

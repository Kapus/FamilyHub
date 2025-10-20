<?php
require_once __DIR__ . '/config.php';

// Avslutar sessionen och skickar tillbaka användaren till inloggningen
$_SESSION = [];
session_destroy();

header('Location: index.php');
exit;

<?php
require_once __DIR__ . '/../config.php';
require_login();

$module = $_GET['module'] ?? '';
$allowedModules = [
    'calendar' => __DIR__ . '/../modules/calendar.php',
    'tasks' => __DIR__ . '/../modules/tasks.php',
    'meals' => __DIR__ . '/../modules/meals.php',
    'chat' => __DIR__ . '/../modules/chat.php',
    'photos' => __DIR__ . '/../modules/photos.php',
];

if (!array_key_exists($module, $allowedModules)) {
    http_response_code(400);
    echo '<div class="alert alert-warning">Okänd modul.</div>';
    exit;
}

$user = $_SESSION['user'];

ob_start();
require $allowedModules[$module];
$html = ob_get_clean();

echo $html;

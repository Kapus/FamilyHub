<?php
require_once __DIR__ . '/config.php';
require_login();

$user = $_SESSION['user'];

$availableModules = ['calendar', 'tasks', 'meals', 'chat', 'photos'];
$defaultModule = $_GET['module'] ?? 'calendar';
if (!in_array($defaultModule, $availableModules, true)) {
    $defaultModule = 'calendar';
}
?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Familyhub | Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body class="bg-light">
<div class="dashboard d-flex">
    <aside class="dashboard-sidebar d-flex flex-column bg-white border-end">
        <div class="p-4">
            <a class="navbar-brand fw-semibold text-primary" href="dashboard.php">Familyhub</a>
        </div>
        <nav class="nav flex-column nav-pills p-3 gap-1">
            <a class="nav-link<?= $defaultModule === 'calendar' ? ' active' : '' ?> d-flex align-items-center gap-2" data-module="calendar" href="#">
                <span class="bi bi-calendar3"></span><span>Kalender</span>
            </a>
            <a class="nav-link<?= $defaultModule === 'tasks' ? ' active' : '' ?> d-flex align-items-center gap-2" data-module="tasks" href="#">
                <span class="bi bi-check2-square"></span><span>Att göra-listor</span>
            </a>
            <a class="nav-link<?= $defaultModule === 'meals' ? ' active' : '' ?> d-flex align-items-center gap-2" data-module="meals" href="#">
                <span class="bi bi-egg-fried"></span><span>Middagsplanering</span>
            </a>
            <a class="nav-link<?= $defaultModule === 'chat' ? ' active' : '' ?> d-flex align-items-center gap-2" data-module="chat" href="#">
                <span class="bi bi-chat-dots"></span><span>Familjechatt</span>
            </a>
            <a class="nav-link<?= $defaultModule === 'photos' ? ' active' : '' ?> d-flex align-items-center gap-2" data-module="photos" href="#">
                <span class="bi bi-images"></span><span>Minnen</span>
            </a>
            <a class="nav-link d-flex align-items-center gap-2" href="profile.php">
                <span class="bi bi-person-circle"></span><span>Min profil</span>
            </a>
        </nav>
        <div class="mt-auto p-3 border-top small text-muted">
            <div><?= htmlspecialchars($user['email']) ?></div>
            <div><?= htmlspecialchars($user['role']) ?></div>
        </div>
    </aside>
    <div class="dashboard-main flex-grow-1 d-flex flex-column">
        <header class="dashboard-topbar d-flex justify-content-between align-items-center px-4 py-3 bg-white border-bottom">
            <div>
                <h1 class="h5 mb-0">Hej, <?= htmlspecialchars($user['name']) ?></h1>
                <p class="text-muted mb-0">Välj modul i menyn för att börja</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <?php if ($user['role'] === 'ADMIN'): ?>
                    <a class="btn btn-outline-primary btn-sm" href="admin.php">Adminpanel</a>
                <?php endif; ?>
                <a class="btn btn-primary btn-sm" href="logout.php">Logga ut</a>
            </div>
        </header>
        <main class="dashboard-content flex-grow-1 overflow-auto">
            <div id="module-container" class="module-container p-4"><!-- Innehållet laddas via AJAX --></div>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
<script src="assets/js/calendar-module.js"></script>
<script src="assets/js/chat-module.js"></script>
<script src="assets/js/dashboard.js"></script>
<script>
window.addEventListener('DOMContentLoaded', () => {
    const defaultModule = <?= json_encode($defaultModule) ?>;
    window.FamilyHubDashboard.loadModule(defaultModule);
});
</script>
</body>
</html>

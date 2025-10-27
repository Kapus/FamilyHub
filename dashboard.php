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
<body class="dashboard-body">
<nav class="navbar navbar-expand-lg navbar-dark dashboard-navbar py-3">
    <div class="container-fluid">
        <a class="navbar-brand" href="dashboard.php?module=calendar">Familyhub</a>
        <div class="ms-auto d-flex align-items-center gap-2">
            <span class="navbar-text text-white-50 small d-none d-md-inline">Inloggad som <?= htmlspecialchars($user['name']) ?></span>
            <?php if ($user['role'] === 'ADMIN'): ?>
                <a class="btn btn-outline-light btn-sm" href="admin.php">Adminpanel</a>
            <?php endif; ?>
            <a class="btn btn-light btn-sm" href="logout.php">Logga ut</a>
        </div>
    </div>
</nav>

<main class="dashboard-wrapper py-4">
    <div class="container-fluid">
        <div class="row g-4">
            <div class="col-12 col-xl-3 col-xxl-2">
                <div class="card dashboard-panel shadow-sm border-0 h-100">
                    <div class="card-body">
                        <h2 class="h6 text-uppercase text-muted fw-semibold mb-3">Navigering</h2>
                        <div class="list-group dashboard-menu">
                            <a class="list-group-item list-group-item-action d-flex align-items-center gap-3<?= $defaultModule === 'calendar' ? ' active' : '' ?>" data-module="calendar" href="#">
                                <span class="bi bi-calendar3"></span><span>Kalender</span>
                            </a>
                            <a class="list-group-item list-group-item-action d-flex align-items-center gap-3<?= $defaultModule === 'tasks' ? ' active' : '' ?>" data-module="tasks" href="#">
                                <span class="bi bi-check2-square"></span><span>Att göra-listor</span>
                            </a>
                            <a class="list-group-item list-group-item-action d-flex align-items-center gap-3<?= $defaultModule === 'meals' ? ' active' : '' ?>" data-module="meals" href="#">
                                <span class="bi bi-egg-fried"></span><span>Middagsplanering</span>
                            </a>
                            <a class="list-group-item list-group-item-action d-flex align-items-center gap-3<?= $defaultModule === 'chat' ? ' active' : '' ?>" data-module="chat" href="#">
                                <span class="bi bi-chat-dots"></span><span>Familjechatt</span>
                            </a>
                            <a class="list-group-item list-group-item-action d-flex align-items-center gap-3<?= $defaultModule === 'photos' ? ' active' : '' ?>" data-module="photos" href="#">
                                <span class="bi bi-images"></span><span>Minnen</span>
                            </a>
                            <a class="list-group-item list-group-item-action d-flex align-items-center gap-3" href="profile.php">
                                <span class="bi bi-person-circle"></span><span>Min profil</span>
                            </a>
                        </div>
                    </div>
                    <div id="bitcoin-ticker" class="d-flex flex-column gap-1" data-endpoint="https://api.coingecko.com/api/v3/simple/price?ids=bitcoin&vs_currencies=usd&include_24hr_change=true">
                        <div class="small text-muted">Bitcoin (BTC)</div>
                        <div class="h6 mb-0" id="bitcoin-price">Laddar pris…</div>
                        <div class="small" id="bitcoin-change"></div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-9 col-xxl-10">
                <div class="card dashboard-main-card shadow-lg border-0">
                    <div class="dashboard-header d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                        <div>
                            <h1 class="h4 mb-1">Hej, <?= htmlspecialchars($user['name']) ?></h1>
                            <p class="mb-0">Välj en modul till vänster för att hantera familjens vardag.</p>
                        </div>
                        <div class="text-md-end">
                            <span class="badge text-bg-light text-primary-emphasis"><?= htmlspecialchars(strtoupper($user['role'])) ?></span>
                        </div>
                    </div>
                    <div id="module-container" class="module-container"><!-- Innehållet laddas via AJAX --></div>
                </div>
            </div>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
<script src="assets/js/calendar-module.js"></script>
<script src="assets/js/chat-module.js"></script>
<script src="assets/js/dashboard.js"></script>
<script src="assets/js/bitcoin-ticker.js"></script>
<script>
window.addEventListener('DOMContentLoaded', () => {
    const defaultModule = <?= json_encode($defaultModule) ?>;
    window.FamilyHubDashboard.loadModule(defaultModule);
});
</script>
</body>
</html>

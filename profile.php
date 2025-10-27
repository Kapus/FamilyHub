<?php
require_once __DIR__ . '/config.php';
require_login();

$userId = (int) ($_SESSION['user']['id'] ?? 0);

$stmt = $pdo->prepare('SELECT namn, email, roll, profiltext, profilbild, poang FROM users WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $userId]);
$profile = $stmt->fetch();

if (!$profile) {
    header('Location: logout.php');
    exit;
}

$roleLabels = [
    'ADMIN' => 'Administratör',
    'USER' => 'Familjemedlem',
];

$profileText = $profile['profiltext'] ?? '';
$profilePoints = isset($profile['poang']) ? (int) $profile['poang'] : 0;
$profileImage = null;

if (!empty($profile['profilbild'])) {
    $imageName = basename($profile['profilbild']);
    $imagePath = __DIR__ . '/uploads/' . $imageName;
    if (is_file($imagePath)) {
        $profileImage = 'uploads/' . rawurlencode($imageName);
    }
}

if ($profileImage === null) {
    $profileImage = 'assets/img/default-profile.svg';
}

$successMessage = $_SESSION['profile_success'] ?? '';
unset($_SESSION['profile_success']);

$currentUser = $_SESSION['user'];
?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Familyhub | Min profil</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body class="dashboard-body">
<nav class="navbar navbar-expand-lg navbar-dark dashboard-navbar py-3">
    <div class="container-fluid">
        <a class="navbar-brand" href="dashboard.php?module=calendar">Familyhub</a>
        <div class="ms-auto d-flex align-items-center gap-2">
            <span class="navbar-text text-white-50 small d-none d-md-inline">Inloggad som <?= htmlspecialchars($currentUser['email']) ?></span>
            <?php if ($currentUser['role'] === 'ADMIN'): ?>
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
                            <a class="list-group-item list-group-item-action d-flex align-items-center gap-3" href="dashboard.php?module=calendar">
                                <span class="bi bi-calendar3"></span><span>Kalender</span>
                            </a>
                            <a class="list-group-item list-group-item-action d-flex align-items-center gap-3" href="dashboard.php?module=tasks">
                                <span class="bi bi-check2-square"></span><span>Att göra-listor</span>
                            </a>
                            <a class="list-group-item list-group-item-action d-flex align-items-center gap-3" href="dashboard.php?module=meals">
                                <span class="bi bi-egg-fried"></span><span>Middagsplanering</span>
                            </a>
                            <a class="list-group-item list-group-item-action d-flex align-items-center gap-3" href="dashboard.php?module=chat">
                                <span class="bi bi-chat-dots"></span><span>Familjechatt</span>
                            </a>
                            <a class="list-group-item list-group-item-action d-flex align-items-center gap-3" href="dashboard.php?module=photos">
                                <span class="bi bi-images"></span><span>Minnen</span>
                            </a>
                            <a class="list-group-item list-group-item-action d-flex align-items-center gap-3 active" href="profile.php">
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
                            <h1 class="h4 mb-1">Min profil</h1>
                            <p class="mb-0">Hantera din information och följ dina familjepoäng.</p>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <a class="btn btn-outline-light btn-sm" href="dashboard.php?module=calendar">Till dashboard</a>
                            <a class="btn btn-light btn-sm" href="edit_profile.php">Redigera profil</a>
                        </div>
                    </div>
                    <div class="module-container">
                        <?php if ($successMessage !== ''): ?>
                            <div class="alert alert-success" role="alert">
                                <?= htmlspecialchars($successMessage) ?>
                            </div>
                        <?php endif; ?>
                        <div class="row g-4">
                            <div class="col-lg-6">
                                <div class="card module-surface shadow-sm border-0 h-100">
                                    <div class="card-body text-center p-4">
                                        <div class="mb-3">
                                            <img src="<?= htmlspecialchars($profileImage) ?>" class="rounded-circle" alt="Profilbild" style="width: 160px; height: 160px; object-fit: cover;">
                                        </div>
                                        <h2 class="h4 mb-1"><?= htmlspecialchars($profile['namn']) ?></h2>
                                        <p class="text-muted mb-2"><?= htmlspecialchars($roleLabels[$profile['roll']] ?? $profile['roll']) ?></p>
                                        <p class="text-muted">Poäng: <strong><?= number_format($profilePoints, 0, ',', ' ') ?></strong></p>
                                        <hr>
                                        <div class="text-start">
                                            <h3 class="h6 text-uppercase text-muted">Om mig</h3>
                                            <?php if ($profileText !== ''): ?>
                                                <p><?= nl2br(htmlspecialchars($profileText)) ?></p>
                                            <?php else: ?>
                                                <p class="text-muted">Ingen profiltext ännu.</p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="card module-surface shadow-sm border-0 h-100">
                                    <div class="card-body p-4">
                                        <h2 class="h5 mb-3">Prestationer</h2>
                                        <p class="text-muted">Här kan du samla familjens milstolpar. Lägg till uppgifter i dashboarden för att se dem här.</p>
                                        <ul class="list-group list-group-flush">
                                            <li class="list-group-item px-0 text-muted">Inga prestationer registrerade ännu.</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/bitcoin-ticker.js"></script>
</body>
</html>

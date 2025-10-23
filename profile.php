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
<body class="bg-light">
<div class="dashboard d-flex">
    <aside class="dashboard-sidebar d-flex flex-column bg-white border-end">
        <div class="p-4">
            <a class="navbar-brand fw-semibold text-primary" href="dashboard.php">Familyhub</a>
        </div>
        <nav class="nav flex-column nav-pills p-3 gap-1">
            <a class="nav-link d-flex align-items-center gap-2" href="dashboard.php?module=calendar">
                <span class="bi bi-calendar3"></span><span>Kalender</span>
            </a>
            <a class="nav-link d-flex align-items-center gap-2" href="dashboard.php?module=tasks">
                <span class="bi bi-check2-square"></span><span>Att göra-listor</span>
            </a>
            <a class="nav-link d-flex align-items-center gap-2" href="dashboard.php?module=meals">
                <span class="bi bi-egg-fried"></span><span>Middagsplanering</span>
            </a>
            <a class="nav-link d-flex align-items-center gap-2" href="dashboard.php?module=chat">
                <span class="bi bi-chat-dots"></span><span>Familjechatt</span>
            </a>
            <a class="nav-link d-flex align-items-center gap-2" href="dashboard.php?module=photos">
                <span class="bi bi-images"></span><span>Minnen</span>
            </a>
            <a class="nav-link active d-flex align-items-center gap-2" href="profile.php">
                <span class="bi bi-person-circle"></span><span>Min profil</span>
            </a>
        </nav>
        <div class="mt-auto p-3 border-top small text-muted">
            <div><?= htmlspecialchars($currentUser['email']) ?></div>
            <div><?= htmlspecialchars($currentUser['role']) ?></div>
        </div>
    </aside>
    <div class="dashboard-main flex-grow-1 d-flex flex-column">
        <header class="dashboard-topbar d-flex justify-content-between align-items-center px-4 py-3 bg-white border-bottom">
            <div>
                <h1 class="h5 mb-0">Min profil</h1>
                <p class="text-muted mb-0">Hantera din information och profilbild.</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a class="btn btn-outline-secondary btn-sm" href="dashboard.php">Till dashboard</a>
                <a class="btn btn-outline-primary btn-sm" href="edit_profile.php">Redigera profil</a>
                <?php if ($currentUser['role'] === 'ADMIN'): ?>
                    <a class="btn btn-outline-primary btn-sm" href="admin.php">Adminpanel</a>
                <?php endif; ?>
                <a class="btn btn-primary btn-sm" href="logout.php">Logga ut</a>
            </div>
        </header>
        <main class="dashboard-content flex-grow-1 overflow-auto">
            <div class="module-container p-4">
                <?php if ($successMessage !== ''): ?>
                    <div class="alert alert-success" role="alert">
                        <?= htmlspecialchars($successMessage) ?>
                    </div>
                <?php endif; ?>
                <div class="row g-4">
                    <div class="col-lg-6">
                        <div class="card shadow-sm h-100">
                            <div class="card-body text-center p-4">
                                <div class="mb-3">
                                    <img src="<?= htmlspecialchars($profileImage) ?>" class="rounded-circle" alt="Profilbild" style="width: 160px; height: 160px; object-fit: cover;">
                                </div>
                                <h1 class="h4 mb-1"><?= htmlspecialchars($profile['namn']) ?></h1>
                                <p class="text-muted mb-2"><?= htmlspecialchars($roleLabels[$profile['roll']] ?? $profile['roll']) ?></p>
                                <p class="text-muted">Poäng: <strong><?= number_format($profilePoints, 0, ',', ' ') ?></strong></p>
                                <hr>
                                <div class="text-start">
                                    <h2 class="h6 text-uppercase text-muted">Om mig</h2>
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
                        <div class="card shadow-sm h-100">
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
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

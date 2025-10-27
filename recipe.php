<?php
require_once __DIR__ . '/config.php';
require_login();

$recipeId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$recipeId) {
    header('Location: dashboard.php?module=meals');
    exit;
}

$columnStmt = $pdo->query('SHOW COLUMNS FROM recipes');
$recipeColumns = [];
foreach ($columnStmt->fetchAll(PDO::FETCH_ASSOC) as $columnInfo) {
    $recipeColumns[$columnInfo['Field']] = true;
}

$hasCategoryColumn = isset($recipeColumns['kategori']);
$hasCookTimeColumn = isset($recipeColumns['tillagningstid']);
$hasDifficultyColumn = isset($recipeColumns['svarighetsgrad']);
$hasRatingColumn = isset($recipeColumns['betyg']);
$hasImageColumn = isset($recipeColumns['bild_url']);
$hasIngredientsColumn = isset($recipeColumns['ingredienser']);
$hasInstructionsColumn = isset($recipeColumns['instruktioner']);
$hasNotesColumn = isset($recipeColumns['anteckningar']);

$selectFields = ['id', 'namn', 'beskrivning', 'url', 'skapad_at'];
if ($hasCategoryColumn) {
    $selectFields[] = 'kategori';
}
if ($hasCookTimeColumn) {
    $selectFields[] = 'tillagningstid';
}
if ($hasDifficultyColumn) {
    $selectFields[] = 'svarighetsgrad';
}
if ($hasRatingColumn) {
    $selectFields[] = 'betyg';
}
if ($hasImageColumn) {
    $selectFields[] = 'bild_url';
}
if ($hasIngredientsColumn) {
    $selectFields[] = 'ingredienser';
}
if ($hasInstructionsColumn) {
    $selectFields[] = 'instruktioner';
}
if ($hasNotesColumn) {
    $selectFields[] = 'anteckningar';
}

$recipeStmt = $pdo->prepare('SELECT ' . implode(', ', $selectFields) . ' FROM recipes WHERE id = :id LIMIT 1');
$recipeStmt->execute(['id' => $recipeId]);
$recipe = $recipeStmt->fetch(PDO::FETCH_ASSOC);

if (!$recipe) {
    http_response_code(404);
    $pageTitle = 'Recept saknas';
} else {
    $pageTitle = $recipe['namn'];
}

$normalizeString = static function (?string $value): string {
    $value = trim((string) $value);
    if ($value === '') {
        return '';
    }
    if (function_exists('iconv')) {
        $transliterated = iconv('UTF-8', 'ASCII//TRANSLIT', $value);
        if ($transliterated !== false) {
            $value = $transliterated;
        }
    }
    $value = strtolower($value);
    $value = preg_replace('/[^a-z0-9]+/', ' ', $value);
    return trim($value);
};

$categorySynonyms = [
    'breakfast' => 'Frukost',
    'frukost' => 'Frukost',
    'morgon' => 'Frukost',
    'lunch' => 'Lunch',
    'middag' => 'Middag',
    'kvalls mat' => 'Middag',
    'kvalls' => 'Middag',
    'dinner' => 'Middag',
    'snack' => 'Mellanmål',
    'mellanmal' => 'Mellanmål',
    'mellanmål' => 'Mellanmål',
    'dessert' => 'Efterrätt',
    'efterratt' => 'Efterrätt',
    'efterrätt' => 'Efterrätt',
];

$deriveCategoryLabel = static function (array $recipeRow, bool $hasCategory, callable $normalizer, array $synonyms): string {
    $candidate = '';
    if ($hasCategory) {
        $candidate = $normalizer($recipeRow['kategori'] ?? '');
    }
    if ($candidate === '') {
        $candidate = $normalizer($recipeRow['namn'] ?? '');
    }
    if ($candidate === '') {
        return 'Övrigt';
    }
    foreach ($synonyms as $search => $label) {
        if (str_contains($candidate, $search)) {
            return $label;
        }
    }
    return 'Övrigt';
};

$usageStmt = $pdo->prepare('SELECT dag, ratt FROM meals WHERE recipe_id = :id ORDER BY FIELD(dag, "Måndag", "Tisdag", "Onsdag", "Torsdag", "Fredag", "Lördag", "Söndag")');
$usageStmt->execute(['id' => $recipeId]);
$usageRows = $usageStmt->fetchAll(PDO::FETCH_ASSOC);

$currentUser = $_SESSION['user'];
?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Familyhub | <?= htmlspecialchars($pageTitle) ?></title>
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
                            <a class="list-group-item list-group-item-action d-flex align-items-center gap-3 active" href="dashboard.php?module=meals">
                                <span class="bi bi-egg-fried"></span><span>Middagsplanering</span>
                            </a>
                            <a class="list-group-item list-group-item-action d-flex align-items-center gap-3" href="dashboard.php?module=chat">
                                <span class="bi bi-chat-dots"></span><span>Familjechatt</span>
                            </a>
                            <a class="list-group-item list-group-item-action d-flex align-items-center gap-3" href="dashboard.php?module=photos">
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
                            <h1 class="h4 mb-1"><?= htmlspecialchars($pageTitle) ?></h1>
                            <p class="mb-0">Detaljerad receptvy för familjens middagsplanering.</p>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <a class="btn btn-outline-light btn-sm" href="dashboard.php?module=meals"><i class="bi bi-arrow-left-short me-1"></i>Tillbaka till recepten</a>
                            <?php if ($recipe && !empty($recipe['url'])): ?>
                                <a class="btn btn-light btn-sm" href="<?= htmlspecialchars($recipe['url']) ?>" target="_blank" rel="noopener"><i class="bi bi-box-arrow-up-right me-1"></i>Extern länk</a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="module-container">
                        <?php if (!$recipe): ?>
                            <div class="alert alert-warning" role="alert">
                                Receptet du försöker visa finns inte längre. Gå tillbaka till <a class="alert-link" href="dashboard.php?module=meals">Middagsplanering</a> och välj ett annat recept.
                            </div>
                        <?php else: ?>
                            <?php
                            $categoryLabel = $deriveCategoryLabel($recipe, $hasCategoryColumn, $normalizeString, $categorySynonyms);
                            $cookTime = $hasCookTimeColumn ? (int) ($recipe['tillagningstid'] ?? 0) : 0;
                            $cookTimeDisplay = $cookTime > 0 ? $cookTime . ' min' : '30 min';
                            $difficulty = $hasDifficultyColumn ? trim((string) ($recipe['svarighetsgrad'] ?? '')) : '';
                            if ($difficulty === '') {
                                $difficulty = 'Medel';
                            }
                            $rating = $hasRatingColumn ? (float) ($recipe['betyg'] ?? 0) : 4.0;
                            if ($rating <= 0) {
                                $rating = 4.0;
                            }
                            $createdAt = !empty($recipe['skapad_at']) ? new DateTime($recipe['skapad_at']) : null;
                            $imageUrl = null;
                            if ($hasImageColumn) {
                                $imageCandidate = trim((string) ($recipe['bild_url'] ?? ''));
                                if ($imageCandidate !== '') {
                                    $imageUrl = $imageCandidate;
                                }
                            }
                            if ($imageUrl === null) {
                                $imageUrl = 'https://source.unsplash.com/collection/928423/960x640?sig=' . ($recipe['id'] % 50 + 1);
                            }
                            $ingredients = $hasIngredientsColumn ? trim((string) ($recipe['ingredienser'] ?? '')) : '';
                            $instructions = $hasInstructionsColumn ? trim((string) ($recipe['instruktioner'] ?? '')) : '';
                            $notes = $hasNotesColumn ? trim((string) ($recipe['anteckningar'] ?? '')) : '';

                            $formatMultiline = static function (string $text): string {
                                $lines = preg_split('/\r\n|\r|\n/', $text);
                                $items = array_filter(array_map('trim', $lines), static fn($line) => $line !== '');
                                if (empty($items)) {
                                    return '<p class="text-muted mb-0">Ingen information angiven.</p>';
                                }
                                $html = '<ul class="list-unstyled mb-0">';
                                foreach ($items as $item) {
                                    $html .= '<li class="mb-1"><i class="bi bi-dot text-primary"></i> ' . htmlspecialchars($item) . '</li>';
                                }
                                $html .= '</ul>';
                                return $html;
                            };
                            ?>
                            <div class="row g-4">
                                <div class="col-lg-5">
                                    <div class="card module-surface shadow-sm border-0 h-100 overflow-hidden">
                                        <div class="ratio ratio-4x3">
                                            <img src="<?= htmlspecialchars($imageUrl) ?>" class="object-fit-cover" alt="<?= htmlspecialchars($recipe['namn']) ?>">
                                        </div>
                                        <div class="card-body">
                                            <span class="badge recipe-category-badge mb-3"><?= htmlspecialchars($categoryLabel) ?></span>
                                            <h2 class="h5 mb-3">Snabbinfo</h2>
                                            <div class="d-flex flex-wrap gap-3 text-muted small">
                                                <span><i class="bi bi-clock me-1"></i><?= htmlspecialchars($cookTimeDisplay) ?></span>
                                                <span><i class="bi bi-bar-chart me-1"></i><?= htmlspecialchars($difficulty) ?></span>
                                                <span><i class="bi bi-star me-1 text-warning"></i><?= htmlspecialchars(number_format($rating, 1)) ?></span>
                                            </div>
                                            <?php if ($createdAt !== null): ?>
                                                <p class="text-muted small mt-3 mb-0">Tillagt <?= htmlspecialchars($createdAt->format('Y-m-d')) ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-7">
                                    <div class="card module-surface shadow-sm border-0 h-100">
                                        <div class="card-body d-flex flex-column gap-4">
                                            <section>
                                                <h2 class="h5 mb-2">Beskrivning</h2>
                                                <?php if (!empty($recipe['beskrivning'])): ?>
                                                    <p class="mb-0"><?= nl2br(htmlspecialchars($recipe['beskrivning'])) ?></p>
                                                <?php else: ?>
                                                    <p class="text-muted mb-0">Ingen beskrivning tillagd ännu.</p>
                                                <?php endif; ?>
                                            </section>

                                            <section>
                                                <h2 class="h5 mb-2">Ingredienser</h2>
                                                <?= $ingredients !== '' ? $formatMultiline($ingredients) : '<p class="text-muted mb-0">Inga ingredienser listade.</p>' ?>
                                            </section>

                                            <section>
                                                <h2 class="h5 mb-2">Tillagning</h2>
                                                <?php if ($instructions !== ''): ?>
                                                    <p class="mb-0"><?= nl2br(htmlspecialchars($instructions)) ?></p>
                                                <?php else: ?>
                                                    <p class="text-muted mb-0">Inga instruktioner angivna.</p>
                                                <?php endif; ?>
                                            </section>

                                            <?php if ($notes !== ''): ?>
                                                <section>
                                                    <h2 class="h5 mb-2">Anteckningar</h2>
                                                    <p class="mb-0"><?= nl2br(htmlspecialchars($notes)) ?></p>
                                                </section>
                                            <?php endif; ?>

                                            <?php if (!empty($usageRows)): ?>
                                                <section>
                                                    <h2 class="h5 mb-2">Planerade dagar</h2>
                                                    <ul class="list-unstyled mb-0">
                                                        <?php foreach ($usageRows as $usage): ?>
                                                            <li class="mb-1"><i class="bi bi-calendar-event text-primary me-2"></i><?= htmlspecialchars($usage['dag']) ?> – <?= htmlspecialchars($usage['ratt']) ?></li>
                                                        <?php endforeach; ?>
                                                    </ul>
                                                </section>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
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

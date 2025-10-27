<?php
// modules/meals.php
// Recept- och middagsmodul med sökning, kategorifilter och veckoplanering

$weekDays = ['Måndag', 'Tisdag', 'Onsdag', 'Torsdag', 'Fredag', 'Lördag', 'Söndag'];

$requiredTables = ['meals', 'recipes'];
$missingTables = [];
$tableCheckStmt = $pdo->prepare('SHOW TABLES LIKE ?');
foreach ($requiredTables as $tableName) {
    $tableCheckStmt->execute([$tableName]);
    if ($tableCheckStmt->fetchColumn() === false) {
        $missingTables[] = $tableName;
    }
}

if (!empty($missingTables)) {
    sort($missingTables);
    ?>
    <div class="alert alert-warning" role="alert">
        <h2 class="h6 mb-2">Databastabeller saknas</h2>
        <p class="mb-1">Följande tabeller saknas i databasen: <strong><?= htmlspecialchars(implode(', ', $missingTables)) ?></strong>.</p>
        <p class="mb-0">Kör den senaste uppdateringen i <code>database.sql</code> via phpMyAdmin eller motsvarande för att skapa tabellerna, och ladda sedan om sidan.</p>
    </div>
    <?php
    return;
}

$planStmt = $pdo->query(
     'SELECT m.dag, m.ratt, m.notering, m.recipe_id, r.namn AS recept_namn, r.beskrivning AS recept_beskrivning, r.url AS recept_url
         FROM meals m
  LEFT JOIN recipes r ON r.id = m.recipe_id
    ORDER BY FIELD(m.dag, "Måndag", "Tisdag", "Onsdag", "Torsdag", "Fredag", "Lördag", "Söndag")'
);

$planRows = $planStmt->fetchAll();
$mealPlan = [];
foreach ($planRows as $row) {
    $mealPlan[$row['dag']] = $row;
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

$selectFields = ['id', 'namn', 'beskrivning', 'url'];
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

$searchTerm = trim((string) ($_GET['recipe_search'] ?? ''));
$selectedCategory = strtolower(trim((string) ($_GET['recipe_category'] ?? 'all')));
if ($selectedCategory === '') {
    $selectedCategory = 'all';
}

$recipesSql = 'SELECT ' . implode(', ', $selectFields) . ' FROM recipes';
$recipesWhere = [];
$recipeParams = [];
if ($searchTerm !== '') {
    $recipesWhere[] = '(namn LIKE :search OR beskrivning LIKE :search)';
    $recipeParams['search'] = '%' . $searchTerm . '%';
}
if (!empty($recipesWhere)) {
    $recipesSql .= ' WHERE ' . implode(' AND ', $recipesWhere);
}
$recipesSql .= ' ORDER BY namn';

$recipesStmt = $pdo->prepare($recipesSql);
$recipesStmt->execute($recipeParams);
$allRecipes = $recipesStmt->fetchAll(PDO::FETCH_ASSOC);

$baseCategories = [
    'all' => 'Alla recept',
    'breakfast' => 'Frukost',
    'lunch' => 'Lunch',
    'dinner' => 'Middag',
    'snacks' => 'Mellanmål',
    'dessert' => 'Efterrätt',
    'other' => 'Övrigt',
];

$categorySynonyms = [
    'breakfast' => 'breakfast',
    'frukost' => 'breakfast',
    'morgon' => 'breakfast',
    'lunch' => 'lunch',
    'middag' => 'dinner',
    'kvalls mat' => 'dinner',
    'kvalls' => 'dinner',
    'middag' => 'dinner',
    'dinner' => 'dinner',
    'snack' => 'snacks',
    'mellanmal' => 'snacks',
    'mellanmål' => 'snacks',
    'dessert' => 'dessert',
    'efterratt' => 'dessert',
    'efterrätt' => 'dessert',
];

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

$determineCategory = static function (array $recipe) use ($hasCategoryColumn, $normalizeString, $categorySynonyms): string {
    $candidate = '';
    if ($hasCategoryColumn) {
        $candidate = $normalizeString($recipe['kategori'] ?? '');
    }

    if ($candidate === '') {
        $candidate = $normalizeString($recipe['namn'] ?? '');
    }

    if ($candidate === '') {
        return 'other';
    }

    foreach ($categorySynonyms as $needle => $mapped) {
        if (str_contains($candidate, $needle)) {
            return $mapped;
        }
    }

    return 'other';
};

$categoryCounts = array_fill_keys(array_keys($baseCategories), 0);
foreach ($allRecipes as $recipeRow) {
    $categoryKey = $determineCategory($recipeRow);
    if (!array_key_exists($categoryKey, $categoryCounts)) {
        $categoryCounts[$categoryKey] = 0;
        $baseCategories[$categoryKey] = ucfirst($categoryKey);
    }
    $categoryCounts[$categoryKey] += 1;
}
$categoryCounts['all'] = count($allRecipes);

$displayRecipes = $allRecipes;
if ($selectedCategory !== 'all') {
    $displayRecipes = array_values(array_filter($allRecipes, static function (array $recipeRow) use ($determineCategory, $selectedCategory) {
        return $determineCategory($recipeRow) === $selectedCategory;
    }));
}

$recipeTotal = count($displayRecipes);

$buildHiddenFields = static function (array $excludedKeys = []) {
    foreach ($_GET as $key => $value) {
        if (in_array($key, $excludedKeys, true)) {
            continue;
        }
        if (is_array($value)) {
            continue;
        }
        echo '<input type="hidden" name="' . htmlspecialchars($key) . '" value="' . htmlspecialchars($value) . '">';
    }
};

$buildCategoryButtonClass = static function (string $categoryKey, string $selectedCategoryKey): string {
    $baseClass = 'btn recipe-filter-btn';
    return $baseClass . ($categoryKey === $selectedCategoryKey ? ' active' : '');
};

$renderRating = static function (float $rating): string {
    $rating = max(0.0, min(5.0, $rating));
    $fullStars = (int) floor($rating);
    $remaining = 5 - $fullStars;
    $html = '';
    for ($i = 0; $i < $fullStars; $i++) {
        $html .= '<i class="bi bi-star-fill text-warning"></i>';
    }
    for ($i = 0; $i < $remaining; $i++) {
        $html .= '<i class="bi bi-star text-warning"></i>';
    }
    return $html;
};
?>
<div class="recipe-module d-flex flex-column gap-4">
    <div class="card module-surface border-0 shadow-sm recipe-search-card">
        <div class="card-body d-flex flex-column gap-4">
            <nav class="recipe-category-navbar">
                <form method="get" class="d-flex flex-wrap gap-2">
                    <input type="hidden" name="module" value="meals">
                    <input type="hidden" name="recipe_search" value="<?= htmlspecialchars($searchTerm) ?>">
                    <?php $buildHiddenFields(['module', 'recipe_search', 'recipe_category']); ?>
                    <?php foreach ($baseCategories as $categoryKey => $categoryLabel): ?>
                        <?php $count = $categoryCounts[$categoryKey] ?? 0; ?>
                        <button type="submit" name="recipe_category" value="<?= htmlspecialchars($categoryKey) ?>" class="<?= htmlspecialchars($buildCategoryButtonClass($categoryKey, $selectedCategory)) ?>">
                            <span><?= htmlspecialchars($categoryLabel) ?></span>
                            <span class="badge rounded-pill text-bg-light ms-2"><?= (int) $count ?></span>
                        </button>
                    <?php endforeach; ?>
                </form>
            </nav>
            <form method="get" class="row g-3 align-items-center recipe-search-row">
                <div class="col-12 col-md-6 col-lg-5">
                    <label for="recipe-search" class="form-label small text-muted mb-1">Sök recept</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-transparent border-end-0 text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" id="recipe-search" name="recipe_search" class="form-control border-start-0" placeholder="Search for recipes..." value="<?= htmlspecialchars($searchTerm) ?>">
                    </div>
                </div>
                <div class="col-12 col-md-3 col-lg-2 d-grid align-content-end">
                    <label class="form-label small text-muted mb-1">&nbsp;</label>
                    <button type="submit" class="btn btn-primary btn-sm">Sök</button>
                </div>
                <input type="hidden" name="module" value="meals">
                <?php $buildHiddenFields(['module', 'recipe_search']); ?>
            </form>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-12 col-xxl-8">
            <div class="card module-surface border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex flex-column flex-md-row flex-wrap justify-content-between gap-2 align-items-start align-items-md-center mb-4">
                        <div>
                            <h2 class="h5 mb-1">Receptöversikt</h2>
                            <p class="text-muted small mb-0"><?= $recipeTotal ?> recept<?= $recipeTotal === 1 ? '' : 'er' ?> matchar dina filter.</p>
                        </div>
                        <div class="d-flex align-items-center gap-2 text-muted small">
                            <span><i class="bi bi-clock"></i> Tillagningstid</span>
                            <span><i class="bi bi-bar-chart"></i> Svårighetsgrad</span>
                            <span><i class="bi bi-star"></i> Betyg</span>
                        </div>
                    </div>

                    <?php if (!empty($displayRecipes)): ?>
                        <div class="row g-4 row-cols-1 row-cols-md-2 row-cols-xxl-3">
                            <?php foreach ($displayRecipes as $recipe): ?>
                                <?php
                                $recipeId = (int) ($recipe['id'] ?? 0);
                                $recipeLink = $recipeId > 0 ? 'recipe.php?id=' . urlencode((string) $recipeId) : '#';
                                $categoryKey = $determineCategory($recipe);
                                $categoryLabel = $baseCategories[$categoryKey] ?? ucfirst($categoryKey);
                                $cookTime = $hasCookTimeColumn ? (int) ($recipe['tillagningstid'] ?? 0) : 0;
                                $cookTimeDisplay = $cookTime > 0 ? $cookTime . ' min' : '30 min';
                                $difficulty = $hasDifficultyColumn ? trim((string) ($recipe['svarighetsgrad'] ?? '')) : '';
                                if ($difficulty === '') {
                                    $difficulty = 'Medel';
                                }
                                $ratingValue = $hasRatingColumn ? (float) ($recipe['betyg'] ?? 0) : 4.0;
                                if ($ratingValue <= 0) {
                                    $ratingValue = 4.0;
                                }
                                $imageUrl = null;
                                if ($hasImageColumn) {
                                    $imageCandidate = trim((string) ($recipe['bild_url'] ?? ''));
                                    if ($imageCandidate !== '') {
                                        $imageUrl = $imageCandidate;
                                    }
                                }
                                if ($imageUrl === null) {
                                    $imageUrl = 'https://source.unsplash.com/collection/928423/480x320?sig=' . ((int) $recipe['id'] % 50 + 1);
                                }
                                ?>
                                <div class="col">
                                    <a class="recipe-card-modern h-100 d-flex flex-column text-decoration-none" href="<?= htmlspecialchars($recipeLink) ?>">
                                        <div class="recipe-card-media" style="background-image: url('<?= htmlspecialchars($imageUrl) ?>');"></div>
                                        <div class="recipe-card-body d-flex flex-column flex-grow-1">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <span class="badge recipe-category-badge"><?= htmlspecialchars($categoryLabel) ?></span>
                                                <span class="recipe-rating" title="Betyg: <?= htmlspecialchars(number_format($ratingValue, 1)) ?>"><?= $renderRating($ratingValue) ?></span>
                                            </div>
                                            <h3 class="h6 mb-2 text-body-emphasis"><?= htmlspecialchars($recipe['namn']) ?></h3>
                                            <p class="text-muted small flex-grow-1 mb-3">
                                                <?php if (!empty($recipe['beskrivning'])): ?>
                                                    <?= htmlspecialchars(mb_strimwidth($recipe['beskrivning'], 0, 120, '…')) ?>
                                                <?php else: ?>
                                                    Ingen beskrivning tillagd ännu.
                                                <?php endif; ?>
                                            </p>
                                            <div class="recipe-card-meta d-flex flex-wrap gap-3 text-muted small mb-3">
                                                <span><i class="bi bi-clock me-1"></i><?= htmlspecialchars($cookTimeDisplay) ?></span>
                                                <span><i class="bi bi-bar-chart me-1"></i><?= htmlspecialchars($difficulty) ?></span>
                                                <span><i class="bi bi-star me-1"></i><?= htmlspecialchars(number_format($ratingValue, 1)) ?></span>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center mt-auto">
                                                <span class="text-muted small"><i class="bi bi-journal-text me-1"></i><?= htmlspecialchars($categoryLabel) ?></span>
                                                <span class="text-muted small"><i class="bi bi-arrow-up-right"></i> Visa recept</span>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <div class="display-6 text-muted mb-3"><i class="bi bi-emoji-frown"></i></div>
                            <p class="text-muted mb-0">Inga recept matchar din sökning just nu. Prova att ändra sökord eller kategori.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    <div class="col-12 col-xxl-4">
            <div class="card module-surface border-0 shadow-sm meal-planner-panel">
                <div class="card-body">
                    <h2 class="h5 mb-3">Veckans middagar</h2>
                    <p class="text-muted small">Planeringen uppdateras via adminpanelen.</p>
                    <div class="list-group list-group-flush meal-planner-list">
                        <?php foreach ($weekDays as $dayLabel): ?>
                            <?php
                            $data = $mealPlan[$dayLabel] ?? null;
                            $hasMeal = $data !== null && trim((string) ($data['ratt'] ?? '')) !== '';
                            $dish = $hasMeal ? trim((string) $data['ratt']) : 'Ingen rätt planerad';
                            $note = $hasMeal ? trim((string) ($data['notering'] ?? '')) : '';
                            $recipeName = $hasMeal ? trim((string) ($data['recept_namn'] ?? '')) : '';
                            $recipeUrl = $hasMeal ? trim((string) ($data['recept_url'] ?? '')) : '';
                            $recipeIdForPlan = $hasMeal ? (int) ($data['recipe_id'] ?? 0) : 0;
                            $internalRecipeLink = $recipeIdForPlan > 0 ? 'recipe.php?id=' . urlencode((string) $recipeIdForPlan) : '';
                            ?>
                            <div class="list-group-item meal-planner-item">
                                <div class="d-flex justify-content-between align-items-start gap-3">
                                    <div>
                                        <div class="d-flex align-items-center gap-2 mb-1">
                                            <span class="badge text-bg-light"><?= htmlspecialchars($dayLabel) ?></span>
                                            <?php if ($hasMeal && $recipeName !== ''): ?>
                                                <i class="bi bi-check-circle-fill text-success"></i>
                                            <?php endif; ?>
                                        </div>
                                        <div class="fw-semibold text-body-emphasis"><?= htmlspecialchars($dish) ?></div>
                                        <?php if ($note !== ''): ?>
                                            <div class="text-muted small mt-1"><?= nl2br(htmlspecialchars($note)) ?></div>
                                        <?php endif; ?>
                                        <?php if ($recipeName !== ''): ?>
                                            <div class="small mt-2 d-flex align-items-center gap-2">
                                                <span class="text-muted"><i class="bi bi-journal-text"></i></span>
                                                <?php if ($internalRecipeLink !== ''): ?>
                                                    <a class="recipe-planner-link" href="<?= htmlspecialchars($internalRecipeLink) ?>"><?= htmlspecialchars($recipeName) ?></a>
                                                <?php else: ?>
                                                    <?= htmlspecialchars($recipeName) ?>
                                                <?php endif; ?>
                                                <?php if ($recipeUrl !== ''): ?>
                                                    <a class="text-muted" href="<?= htmlspecialchars($recipeUrl) ?>" target="_blank" rel="noopener" title="Öppna extern länk"><i class="bi bi-box-arrow-up-right"></i></a>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

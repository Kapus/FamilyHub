<?php
// modules/meals.php
// Visar veckans middagsplanering och receptbank

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

$recipesStmt = $pdo->query('SELECT id, namn, beskrivning, url FROM recipes ORDER BY namn');
$recipes = $recipesStmt->fetchAll();
?>

<div class="row g-4">
    <div class="col-12 col-xl-6">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h2 class="h5 mb-3">Veckans meny</h2>
                <p class="text-muted small">Planen uppdateras via adminpanelen.</p>
                <div class="list-group list-group-flush">
                    <?php foreach ($weekDays as $day): ?>
                        <?php
                        $data = $mealPlan[$day] ?? null;
                        $hasMeal = $data !== null;
                        $dish = $hasMeal ? trim($data['ratt']) : '';
                        $note = $hasMeal ? trim((string) ($data['notering'] ?? '')) : '';
                        $recipeName = $hasMeal ? trim((string) ($data['recept_namn'] ?? '')) : '';
                        $recipeUrl = $hasMeal ? trim((string) ($data['recept_url'] ?? '')) : '';
                        ?>
                        <div class="list-group-item px-0">
                            <div class="d-flex justify-content-between align-items-start gap-3">
                                <div>
                                    <div class="fw-semibold text-primary mb-1"><?= htmlspecialchars($day) ?></div>
                                    <?php if ($hasMeal && $dish !== ''): ?>
                                        <div class="text-body">
                                            <?= htmlspecialchars($dish) ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-muted fst-italic">Ingen rätt planerad</div>
                                    <?php endif; ?>
                                    <?php if ($note !== ''): ?>
                                        <div class="text-muted small mt-1"><?= nl2br(htmlspecialchars($note)) ?></div>
                                    <?php endif; ?>
                                    <?php if ($recipeName !== ''): ?>
                                        <div class="small mt-2">
                                            <span class="text-muted">Recept:</span>
                                            <?php if ($recipeUrl !== ''): ?>
                                                <a href="<?= htmlspecialchars($recipeUrl) ?>" target="_blank" rel="noopener"><?= htmlspecialchars($recipeName) ?></a>
                                            <?php else: ?>
                                                <span><?= htmlspecialchars($recipeName) ?></span>
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

    <div class="col-12 col-xl-6">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h2 class="h5 mb-3">Receptbanken</h2>
                <?php if (!empty($recipes)): ?>
                    <div class="row g-3">
                        <?php foreach ($recipes as $recipe): ?>
                            <div class="col-12 col-md-6">
                                <div class="border rounded-3 p-3 h-100 d-flex flex-column">
                                    <h3 class="h6 mb-2 text-body-emphasis"><?= htmlspecialchars($recipe['namn']) ?></h3>
                                    <?php if (!empty($recipe['beskrivning'])): ?>
                                        <p class="text-muted small mb-3 flex-grow-1"><?= nl2br(htmlspecialchars($recipe['beskrivning'])) ?></p>
                                    <?php else: ?>
                                        <p class="text-muted small mb-3 flex-grow-1 fst-italic">Ingen beskrivning angiven.</p>
                                    <?php endif; ?>
                                    <?php if (!empty($recipe['url'])): ?>
                                        <a class="btn btn-sm btn-outline-primary mt-auto" href="<?= htmlspecialchars($recipe['url']) ?>" target="_blank" rel="noopener">Visa recept</a>
                                    <?php else: ?>
                                        <span class="text-muted small mt-auto">Ingen länk angiven.</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted mb-0">Inga recept sparade ännu. Lägg till dem via adminpanelen.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

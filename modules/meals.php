<?php
// modules/meals.php
// Visar veckans middagsplanering
$mealsStmt = $pdo->query('SELECT id, dag, ratt, recept_url FROM meals ORDER BY FIELD(dag, "Måndag", "Tisdag", "Onsdag", "Torsdag", "Fredag", "Lördag", "Söndag"), id');
$meals = $mealsStmt->fetchAll();
?>
<div class="card shadow-sm">
    <div class="card-body">
        <h2 class="h5 mb-3">Middagsplanering</h2>
        <div class="table-responsive">
            <table class="table table-striped align-middle mb-0">
                <thead>
                    <tr>
                        <th>Dag</th>
                        <th>Rätt</th>
                        <th>Recept</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($meals as $meal): ?>
                        <tr>
                            <td><?= htmlspecialchars($meal['dag']) ?></td>
                            <td><?= htmlspecialchars($meal['ratt']) ?></td>
                            <td>
                                <?php if (!empty($meal['recipe_url'])): ?>
                                    <a href="<?= htmlspecialchars($meal['recipe_url']) ?>" target="_blank" rel="noopener">Visa recept</a>
                                <?php else: ?>
                                    <span class="text-muted">Inget recept angivet</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($meals)): ?>
                        <tr>
                            <td colspan="3" class="text-muted">Ingen middagsplanering ännu.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

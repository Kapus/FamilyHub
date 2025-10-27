<?php
// modules/tasks.php
// Visar att göra-listor i ett sammanhållet kort med filtrering per kategori (dynamiska)

$accentClasses = [
    'todo-list-item-accent-1',
    'todo-list-item-accent-2',
    'todo-list-item-accent-3',
    'todo-list-item-accent-4',
    'todo-list-item-accent-5',
];

$listsStmt = $pdo->query('SELECT id, namn, kategori, beskrivning FROM task_lists ORDER BY namn');
$rawLists = $listsStmt->fetchAll(PDO::FETCH_ASSOC);

$availableCategories = [];
$categoryAccentMap = [];
$accentIndex = 0;
$lists = [];

foreach ($rawLists as $list) {
    $rawCategory = (string) ($list['kategori'] ?? '');
    $categoryKey = strtolower(trim($rawCategory));
    if ($categoryKey === '') {
        $categoryKey = 'okategoriserad';
    }

    $categoryLabel = trim((string) ($list['namn'] ?? ''));
    if ($categoryLabel === '') {
        $categoryLabel = trim($rawCategory) !== '' ? $rawCategory : 'Okategoriserad';
    }

    $categoryLabel = preg_replace('/[-_]+/', ' ', $categoryLabel);
    if (function_exists('mb_convert_case')) {
        $categoryLabel = mb_convert_case($categoryLabel, MB_CASE_TITLE, 'UTF-8');
    } else {
        $categoryLabel = ucwords($categoryLabel);
    }

    if (!array_key_exists($categoryKey, $categoryAccentMap)) {
        $categoryAccentMap[$categoryKey] = $accentClasses[$accentIndex % count($accentClasses)];
        $accentIndex += 1;
    }

    if (!array_key_exists($categoryKey, $availableCategories)) {
        $availableCategories[$categoryKey] = $categoryLabel;
    }

    $list['_category_key'] = $categoryKey;
    $list['_category_label'] = $availableCategories[$categoryKey];
    $list['_accent_class'] = $categoryAccentMap[$categoryKey];

    $lists[] = $list;
}

$selectedCategory = strtolower(trim((string) ($_GET['todo_category'] ?? 'all')));
if ($selectedCategory === '') {
    $selectedCategory = 'all';
}
if ($selectedCategory !== 'all' && !array_key_exists($selectedCategory, $availableCategories)) {
    $selectedCategory = 'all';
}

$displayLists = $lists;
if ($selectedCategory !== 'all') {
    $displayLists = array_values(array_filter($lists, static function ($list) use ($selectedCategory) {
        return ($list['_category_key'] ?? 'okategoriserad') === $selectedCategory;
    }));
}

$activeCategoryLabel = $selectedCategory !== 'all'
    ? ($availableCategories[$selectedCategory] ?? ucfirst($selectedCategory))
    : null;

$tasksByList = [];
if (!empty($displayLists)) {
    $tasksStmt = $pdo->prepare(
        'SELECT t.id, t.titel, t.deadline, t.status, t.tilldelad_till, u.namn AS tilldelad_namn
           FROM tasks t
      LEFT JOIN users u ON u.id = t.tilldelad_till
          WHERE t.lista_id = :list_id
            AND (t.tilldelad_till IS NULL OR t.tilldelad_till = :user_id)
          ORDER BY t.status != "Klar", t.deadline IS NULL, t.deadline ASC, t.id ASC'
    );

    foreach ($displayLists as $list) {
        $tasksStmt->execute([
            'list_id' => $list['id'],
            'user_id' => $user['id'],
        ]);
        $tasksByList[$list['id']] = $tasksStmt->fetchAll();
    }
}

$totalLists = count($displayLists);
$totalTasks = array_reduce($displayLists, static function ($carry, $list) use ($tasksByList) {
    return $carry + count($tasksByList[$list['id']] ?? []);
}, 0);
?>

<div class="card module-surface shadow-sm border-0 todo-card">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 todo-header mb-4">
            <div>
                <h2 class="h5 mb-1">Att göra-kategorier</h2>
                <p class="text-muted small mb-0">Filtrera familjens kategorier och se alla uppgifter på ett ställe.</p>
                <?php if ($activeCategoryLabel !== null): ?>
                    <p class="text-muted small mb-0">Visar endast: <?= htmlspecialchars($activeCategoryLabel) ?></p>
                <?php endif; ?>
            </div>
            <?php if (!empty($availableCategories)): ?>
                <form id="todo-category-form" method="get" class="d-flex align-items-center gap-2">
                    <?php foreach ($_GET as $param => $value): ?>
                        <?php
                        if ($param === 'todo_category' || is_array($value)) {
                            continue;
                        }
                        ?>
                        <input type="hidden" name="<?= htmlspecialchars($param) ?>" value="<?= htmlspecialchars((string) $value) ?>">
                    <?php endforeach; ?>
                    <label class="form-label mb-0 visually-hidden" for="todo-category-filter">Kategori</label>
                    <select id="todo-category-filter" name="todo_category" class="form-select form-select-sm todo-filter-select" onchange="this.form.submit()">
                        <option value="all" <?= $selectedCategory === 'all' ? 'selected' : '' ?>>Alla kategorier</option>
                        <?php foreach ($availableCategories as $key => $label): ?>
                            <option value="<?= htmlspecialchars($key) ?>" <?= $selectedCategory === $key ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </form>
            <?php endif; ?>
        </div>

        <?php if (!empty($displayLists)): ?>
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 text-muted small mb-3">
                <span><?= $totalLists ?> kategori<?= $totalLists === 1 ? '' : 'er' ?></span>
                <span><?= $totalTasks ?> uppgift<?= $totalTasks === 1 ? '' : 'er' ?></span>
            </div>

            <div class="todo-list-group">
                <?php foreach ($displayLists as $list): ?>
                    <?php
                    $categoryKey = $list['_category_key'] ?? 'okategoriserad';
                    $categoryLabel = $list['_category_label'] ?? ucfirst($categoryKey);
                    $listTasks = $tasksByList[$list['id']] ?? [];
                    $taskCount = count($listTasks);
                    $itemClass = $list['_accent_class'] ?? 'todo-list-item-default';
                    $countBadgeClass = $taskCount > 0
                        ? 'text-bg-primary-subtle text-primary-emphasis'
                        : 'text-bg-secondary-subtle text-secondary-emphasis';
                    ?>
                    <div class="list-group-item todo-list-item <?= $itemClass ?>">
                        <div class="d-flex justify-content-between align-items-start gap-3">
                            <div>
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <span class="badge rounded-pill bg-white text-muted small fw-semibold">
                                        <?= htmlspecialchars($categoryLabel) ?>
                                    </span>
                                    <h3 class="h6 mb-0 text-body-emphasis"><?= htmlspecialchars($list['namn']) ?></h3>
                                </div>
                                <?php if (!empty($list['beskrivning'])): ?>
                                    <p class="text-muted small mb-0"><?= nl2br(htmlspecialchars($list['beskrivning'])) ?></p>
                                <?php endif; ?>
                            </div>
                            <span class="badge <?= $countBadgeClass ?>">
                                <?= $taskCount ?>
                            </span>
                        </div>

                        <?php if (!empty($listTasks)): ?>
                            <ul class="list-unstyled todo-task-list mb-0">
                                <?php foreach ($listTasks as $task): ?>
                                    <?php
                                    $statusClass = $task['status'] === 'Klar'
                                        ? 'text-bg-success-subtle text-success-emphasis'
                                        : 'text-bg-secondary-subtle text-secondary-emphasis';
                                    ?>
                                    <li class="todo-task-line">
                                        <div class="d-flex justify-content-between align-items-start gap-3">
                                            <div>
                                                <div class="fw-semibold"><?= htmlspecialchars($task['titel']) ?></div>
                                                <div class="small text-muted">
                                                    <?php if ($task['deadline']): ?>
                                                        Deadline: <?= htmlspecialchars(date('Y-m-d', strtotime($task['deadline']))) ?>
                                                    <?php else: ?>
                                                        Ingen deadline
                                                    <?php endif; ?>
                                                    <?php if (!empty($task['tilldelad_namn'])): ?>
                                                        &middot; Tilldelad: <?= htmlspecialchars($task['tilldelad_namn']) ?>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <span class="badge <?= $statusClass ?>"><?= htmlspecialchars($task['status']) ?></span>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p class="text-muted small mb-0">Inga uppgifter tillagda i denna kategori ännu.</p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="text-muted mb-0">Det finns inga kategorier ännu. Lägg till dem via adminpanelen för att komma igång.</p>
        <?php endif; ?>
    </div>
</div>

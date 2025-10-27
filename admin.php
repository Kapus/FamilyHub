<?php
require_once __DIR__ . '/config.php';
require_admin();

$currentUser = $_SESSION['user'];
$currentUserId = $currentUser['id'];

function create_category_slug(string $name): string
{
    $slug = trim($name);
    if ($slug === '') {
        return '';
    }

    if (function_exists('iconv')) {
        $transliterated = iconv('UTF-8', 'ASCII//TRANSLIT', $slug);
        if ($transliterated !== false) {
            $slug = $transliterated;
        }
    }
    $slug = strtolower($slug);
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);

    return trim($slug, '-');
}

$alerts = [];

$userForm = [
    'name' => '',
    'email' => '',
    'role' => 'USER',
];

$listForm = [
    'name' => '',
    'description' => '',
];

$taskForm = [
    'list_id' => '',
    'title' => '',
    'deadline' => '',
    'status' => 'Pågående',
    'assignee' => '',
];

$recipeForm = [
    'name' => '',
    'description' => '',
    'url' => '',
];

$mealDays = [
    'mon' => 'Måndag',
    'tue' => 'Tisdag',
    'wed' => 'Onsdag',
    'thu' => 'Torsdag',
    'fri' => 'Fredag',
    'sat' => 'Lördag',
    'sun' => 'Söndag',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'create_user':
            $userForm['name'] = trim($_POST['name'] ?? '');
            $userForm['email'] = trim($_POST['email'] ?? '');
            $userForm['role'] = ($_POST['role'] ?? 'USER') === 'ADMIN' ? 'ADMIN' : 'USER';
            $password = $_POST['password'] ?? '';

            if ($userForm['name'] === '' || $userForm['email'] === '' || $password === '') {
                $alerts[] = ['type' => 'danger', 'text' => 'Alla fält för ny användare måste fyllas i.'];
                break;
            }

            if (!filter_var($userForm['email'], FILTER_VALIDATE_EMAIL)) {
                $alerts[] = ['type' => 'danger', 'text' => 'Ogiltig e-postadress.'];
                break;
            }

            $existsStmt = $pdo->prepare('SELECT 1 FROM users WHERE email = ? LIMIT 1');
            $existsStmt->execute([$userForm['email']]);
            if ($existsStmt->fetchColumn()) {
                $alerts[] = ['type' => 'danger', 'text' => 'En användare med den e-postadressen finns redan.'];
                break;
            }

            $hash = password_hash($password, PASSWORD_DEFAULT);
            $insertStmt = $pdo->prepare('INSERT INTO users (namn, email, losenord, roll) VALUES (?, ?, ?, ?)');
            $insertStmt->execute([$userForm['name'], $userForm['email'], $hash, $userForm['role']]);

            $alerts[] = ['type' => 'success', 'text' => 'Ny användare skapades.'];
            $userForm = ['name' => '', 'email' => '', 'role' => 'USER'];
            break;

        case 'create_list':
            $listForm['name'] = trim($_POST['list_name'] ?? '');
            $listForm['description'] = trim($_POST['description'] ?? '');

            if ($listForm['name'] === '') {
                $alerts[] = ['type' => 'danger', 'text' => 'Kategorin måste ha ett namn.'];
                break;
            }

            $slug = create_category_slug($listForm['name']);
            if ($slug === '') {
                $alerts[] = ['type' => 'danger', 'text' => 'Namnet måste innehålla minst ett alfanumeriskt tecken.'];
                break;
            }

            $slugCandidate = $slug;
            $slugSuffix = 2;
            $slugExistsStmt = $pdo->prepare('SELECT 1 FROM task_lists WHERE kategori = :slug LIMIT 1');
            while (true) {
                $slugExistsStmt->execute([':slug' => $slugCandidate]);
                if (!$slugExistsStmt->fetchColumn()) {
                    break;
                }
                $slugCandidate = $slug . '-' . $slugSuffix;
                $slugSuffix += 1;
            }

            $insertListStmt = $pdo->prepare('INSERT INTO task_lists (namn, kategori, beskrivning, skapad_av) VALUES (:name, :category, :description, :creator)');
            $insertListStmt->bindValue(':name', $listForm['name']);
            $insertListStmt->bindValue(':category', $slugCandidate);
            $insertListStmt->bindValue(':description', $listForm['description'] !== '' ? $listForm['description'] : null, $listForm['description'] !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $insertListStmt->bindValue(':creator', $currentUserId, PDO::PARAM_INT);
            $insertListStmt->execute();

            $alerts[] = ['type' => 'success', 'text' => 'Ny kategori skapades.'];
            $listForm = ['name' => '', 'description' => ''];
            break;

        case 'delete_list':
            $listId = (int) ($_POST['list_id'] ?? 0);
            if ($listId <= 0) {
                $alerts[] = ['type' => 'danger', 'text' => 'Ogiltigt list-ID.'];
                break;
            }

            $deleteStmt = $pdo->prepare('DELETE FROM task_lists WHERE id = :id');
            $deleteStmt->execute([':id' => $listId]);

            if ($deleteStmt->rowCount() > 0) {
                $alerts[] = ['type' => 'success', 'text' => 'Kategorin togs bort.'];
            } else {
                $alerts[] = ['type' => 'warning', 'text' => 'Kategorin kunde inte hittas eller hade redan tagits bort.'];
            }
            break;

        case 'create_task':
            $taskForm['list_id'] = $_POST['task_list_id'] ?? '';
            $taskForm['title'] = trim($_POST['task_title'] ?? '');
            $taskForm['deadline'] = trim($_POST['task_deadline'] ?? '');
            $taskForm['status'] = in_array($_POST['task_status'] ?? 'Pågående', ['Pågående', 'Klar'], true)
                ? ($_POST['task_status'] ?? 'Pågående')
                : 'Pågående';
            $taskForm['assignee'] = $_POST['task_assignee'] ?? '';

            $listId = (int) $taskForm['list_id'];
            if ($listId <= 0) {
                $alerts[] = ['type' => 'danger', 'text' => 'Välj vilken kategori uppgiften ska kopplas till.'];
                break;
            }

            $listExistsStmt = $pdo->prepare('SELECT 1 FROM task_lists WHERE id = :id LIMIT 1');
            $listExistsStmt->execute([':id' => $listId]);
            if (!$listExistsStmt->fetchColumn()) {
                $alerts[] = ['type' => 'danger', 'text' => 'Kategorin kunde inte hittas.'];
                break;
            }

            if ($taskForm['title'] === '') {
                $alerts[] = ['type' => 'danger', 'text' => 'Uppgiften måste ha en titel.'];
                break;
            }

            $deadline = null;
            if ($taskForm['deadline'] !== '') {
                $date = DateTime::createFromFormat('Y-m-d', $taskForm['deadline']);
                $dateErrors = DateTime::getLastErrors();
                if (!$date || $dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0) {
                    $alerts[] = ['type' => 'danger', 'text' => 'Datumet för deadline är ogiltigt.'];
                    break;
                }
                $deadline = $date->format('Y-m-d');
            }

            $assigneeId = null;
            if ($taskForm['assignee'] !== '') {
                $assigneeId = (int) $taskForm['assignee'];
                $userExistsStmt = $pdo->prepare('SELECT 1 FROM users WHERE id = :id LIMIT 1');
                $userExistsStmt->execute([':id' => $assigneeId]);
                if (!$userExistsStmt->fetchColumn()) {
                    $alerts[] = ['type' => 'danger', 'text' => 'Vald användare för uppgiften finns inte.'];
                    break;
                }
            }

            $insertTaskStmt = $pdo->prepare('INSERT INTO tasks (lista_id, titel, deadline, status, tilldelad_till) VALUES (:list_id, :title, :deadline, :status, :assignee)');
            $insertTaskStmt->bindValue(':list_id', $listId, PDO::PARAM_INT);
            $insertTaskStmt->bindValue(':title', $taskForm['title']);
            $insertTaskStmt->bindValue(':deadline', $deadline, $deadline !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $insertTaskStmt->bindValue(':status', $taskForm['status']);
            if ($assigneeId !== null) {
                $insertTaskStmt->bindValue(':assignee', $assigneeId, PDO::PARAM_INT);
            } else {
                $insertTaskStmt->bindValue(':assignee', null, PDO::PARAM_NULL);
            }
            $insertTaskStmt->execute();

            $alerts[] = ['type' => 'success', 'text' => 'Ny att göra-uppgift lades till.'];
            $taskForm = ['list_id' => '', 'title' => '', 'deadline' => '', 'status' => 'Pågående', 'assignee' => ''];
            break;

        case 'create_recipe':
            $recipeForm['name'] = trim($_POST['recipe_name'] ?? '');
            $recipeForm['description'] = trim($_POST['recipe_description'] ?? '');
            $recipeForm['url'] = trim($_POST['recipe_url'] ?? '');

            if ($recipeForm['name'] === '') {
                $alerts[] = ['type' => 'danger', 'text' => 'Receptet måste ha ett namn.'];
                break;
            }

            if ($recipeForm['url'] !== '' && filter_var($recipeForm['url'], FILTER_VALIDATE_URL) === false) {
                $alerts[] = ['type' => 'danger', 'text' => 'Länken till receptet är ogiltig.'];
                break;
            }

            $insertRecipeStmt = $pdo->prepare('INSERT INTO recipes (namn, beskrivning, url) VALUES (:name, :description, :url)');
            $insertRecipeStmt->bindValue(':name', $recipeForm['name']);
            $insertRecipeStmt->bindValue(':description', $recipeForm['description'] !== '' ? $recipeForm['description'] : null, $recipeForm['description'] !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $insertRecipeStmt->bindValue(':url', $recipeForm['url'] !== '' ? $recipeForm['url'] : null, $recipeForm['url'] !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $insertRecipeStmt->execute();

            $alerts[] = ['type' => 'success', 'text' => 'Receptet har lagts till.'];
            $recipeForm = ['name' => '', 'description' => '', 'url' => ''];
            break;

        case 'delete_recipe':
            $recipeId = (int) ($_POST['recipe_id'] ?? 0);
            if ($recipeId <= 0) {
                $alerts[] = ['type' => 'danger', 'text' => 'Ogiltigt recept-ID.'];
                break;
            }

            $deleteRecipeStmt = $pdo->prepare('DELETE FROM recipes WHERE id = :id');
            $deleteRecipeStmt->execute([':id' => $recipeId]);

            if ($deleteRecipeStmt->rowCount() > 0) {
                $alerts[] = ['type' => 'success', 'text' => 'Receptet togs bort. Eventuella kopplingar i middagsplaneringen har rensats.'];
            } else {
                $alerts[] = ['type' => 'warning', 'text' => 'Receptet kunde inte hittas eller hade redan tagits bort.'];
            }
            break;

        case 'update_meal_plan':
            $mealsInput = $_POST['meals'] ?? [];
            $normalizedMeals = [];
            $validationFailed = false;

            $fetchRecipeNameStmt = $pdo->prepare('SELECT namn FROM recipes WHERE id = :id LIMIT 1');

            foreach ($mealDays as $dayKey => $dayName) {
                $dayData = $mealsInput[$dayKey] ?? [];
                $dish = trim($dayData['dish'] ?? '');
                $note = trim($dayData['note'] ?? '');
                $recipeId = isset($dayData['recipe']) && $dayData['recipe'] !== '' ? (int) $dayData['recipe'] : null;

                if ($recipeId !== null) {
                    if ($recipeId <= 0) {
                        $alerts[] = ['type' => 'danger', 'text' => "Ogiltigt recept för $dayName."];
                        $validationFailed = true;
                        break;
                    }

                    $fetchRecipeNameStmt->execute([':id' => $recipeId]);
                    $recipeName = $fetchRecipeNameStmt->fetchColumn();
                    if ($recipeName === false) {
                        $alerts[] = ['type' => 'danger', 'text' => "Receptet som valts för $dayName finns inte längre."];
                        $validationFailed = true;
                        break;
                    }

                    if ($dish === '') {
                        $dish = $recipeName;
                    }
                }

                if ($dish === '' && $note === '' && $recipeId === null) {
                    $normalizedMeals[$dayName] = null;
                    continue;
                }

                if ($dish === '') {
                    $alerts[] = ['type' => 'danger', 'text' => "Fyll i en rätt för $dayName eller lämna dagen tom."];
                    $validationFailed = true;
                    break;
                }

                $normalizedMeals[$dayName] = [
                    'dish' => $dish,
                    'note' => $note !== '' ? $note : null,
                    'recipe_id' => $recipeId,
                ];
            }

            if ($validationFailed) {
                break;
            }

            $pdo->beginTransaction();
            try {
                $deleteMealStmt = $pdo->prepare('DELETE FROM meals WHERE dag = :day');
                $upsertMealStmt = $pdo->prepare('INSERT INTO meals (dag, ratt, notering, recipe_id) VALUES (:day, :dish, :note, :recipe_id)
                    ON DUPLICATE KEY UPDATE ratt = VALUES(ratt), notering = VALUES(notering), recipe_id = VALUES(recipe_id)');

                foreach ($normalizedMeals as $dayName => $mealData) {
                    if ($mealData === null) {
                        $deleteMealStmt->execute([':day' => $dayName]);
                        continue;
                    }

                    $upsertMealStmt->execute([
                        ':day' => $dayName,
                        ':dish' => $mealData['dish'],
                        ':note' => $mealData['note'],
                        ':recipe_id' => $mealData['recipe_id'],
                    ]);
                }

                $pdo->commit();
                $alerts[] = ['type' => 'success', 'text' => 'Middagsplaneringen uppdaterades.'];
            } catch (Exception $e) {
                $pdo->rollBack();
                $alerts[] = ['type' => 'danger', 'text' => 'Det gick inte att spara middagsplaneringen. Försök igen.'];
            }
            break;

        default:
            $alerts[] = ['type' => 'warning', 'text' => 'Ogiltig åtgärd.'];
            break;
    }
}

$usersStmt = $pdo->query('SELECT id, namn, email, roll FROM users ORDER BY namn');
$users = $usersStmt->fetchAll();

$userNameMap = [];
foreach ($users as $u) {
    $userNameMap[(int) $u['id']] = $u['namn'];
}

$listsStmt = $pdo->query('SELECT id, namn, kategori, beskrivning, skapad_at, skapad_av FROM task_lists ORDER BY namn');
$taskLists = $listsStmt->fetchAll();

$usedCategorySlugs = [];
foreach ($taskLists as $index => $taskList) {
    $storedSlug = strtolower(trim((string) $taskList['kategori']));
    $slug = preg_match('/^[a-z0-9-]+$/', $storedSlug) ? $storedSlug : '';

    if ($slug === '') {
        $slug = create_category_slug($taskList['namn']);
    }

    if ($slug === '') {
        $slug = 'kategori-' . $taskList['id'];
    }

    $uniqueSlug = $slug;
    $suffix = 2;
    while (array_key_exists($uniqueSlug, $usedCategorySlugs)) {
        $uniqueSlug = $slug . '-' . $suffix;
        $suffix += 1;
    }

    $usedCategorySlugs[$uniqueSlug] = true;
    $taskLists[$index]['_slug'] = $uniqueSlug;
}

$taskCounts = [];
if (!empty($taskLists)) {
    $taskCountStmt = $pdo->prepare('SELECT COUNT(*) FROM tasks WHERE lista_id = :list_id');
    foreach ($taskLists as $list) {
        $taskCountStmt->execute([':list_id' => $list['id']]);
        $taskCounts[$list['id']] = (int) $taskCountStmt->fetchColumn();
    }
}

$recipesStmt = $pdo->query('SELECT id, namn, beskrivning, url, skapad_at FROM recipes ORDER BY namn');
$recipes = $recipesStmt->fetchAll();

$recipesById = [];
foreach ($recipes as $recipe) {
    $recipesById[(int) $recipe['id']] = $recipe;
}

$mealsStmt = $pdo->query('SELECT dag, ratt, notering, recipe_id FROM meals');
$mealsData = $mealsStmt->fetchAll();

$mealPlan = [];
foreach ($mealsData as $mealRow) {
    $mealPlan[$mealRow['dag']] = [
        'dish' => $mealRow['ratt'],
        'note' => $mealRow['notering'],
        'recipe_id' => $mealRow['recipe_id'],
    ];
}
?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Familyhub | Adminpanel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="dashboard.php">Familyhub</a>
        <div class="d-flex">
            <a class="btn btn-outline-light btn-sm" href="dashboard.php">Tillbaka till dashboard</a>
        </div>
    </div>
</nav>

<div class="container py-4">
    <h1 class="h3 mb-4">Adminpanel</h1>

    <?php foreach ($alerts as $alert): ?>
        <div class="alert alert-<?= htmlspecialchars($alert['type']) ?>" role="alert">
            <?= htmlspecialchars($alert['text']) ?>
        </div>
    <?php endforeach; ?>

    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h5">Skapa ny användare</h2>
                    <form method="post" novalidate>
                        <input type="hidden" name="action" value="create_user">
                        <div class="mb-3">
                            <label for="name" class="form-label">Namn</label>
                            <input type="text" id="name" name="name" class="form-control" value="<?= htmlspecialchars($userForm['name']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">E-post</label>
                            <input type="email" id="email" name="email" class="form-control" value="<?= htmlspecialchars($userForm['email']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Lösenord</label>
                            <input type="password" id="password" name="password" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="role" class="form-label">Roll</label>
                            <select id="role" name="role" class="form-select">
                                <option value="USER" <?= $userForm['role'] === 'USER' ? 'selected' : '' ?>>Användare</option>
                                <option value="ADMIN" <?= $userForm['role'] === 'ADMIN' ? 'selected' : '' ?>>Admin</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Skapa användare</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h5">Befintliga användare</h2>
                    <div class="table-responsive">
                        <table class="table table-striped table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Namn</th>
                                    <th>E-post</th>
                                    <th>Roll</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $u): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($u['namn']) ?></td>
                                        <td><?= htmlspecialchars($u['email']) ?></td>
                                        <td><?= htmlspecialchars($u['roll']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($users)): ?>
                                    <tr>
                                        <td colspan="3" class="text-muted">Inga användare registrerade.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-xl-5">
            <div class="d-flex flex-column gap-4 h-100">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h2 class="h5">Skapa ny kategori</h2>
                        <form method="post" novalidate>
                            <input type="hidden" name="action" value="create_list">
                            <div class="mb-3">
                                <label for="list_name" class="form-label">Kategorins namn</label>
                                <input type="text" id="list_name" name="list_name" class="form-control" value="<?= htmlspecialchars($listForm['name']) ?>" required>
                            </div>
                            <p class="text-muted small">En kategori får automatiskt ett filtervärde baserat på namnet. Värdet används i dashboardens filtrering.</p>
                            <div class="mb-3">
                                <label for="description" class="form-label">Beskrivning <span class="text-muted small">(valfritt)</span></label>
                                <textarea id="description" name="description" class="form-control" rows="3"><?= htmlspecialchars($listForm['description']) ?></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">Skapa kategori</button>
                        </form>
                    </div>
                </div>

                <div class="card shadow-sm">
                    <div class="card-body">
                        <h2 class="h5">Lägg till uppgift</h2>
                        <form method="post" novalidate>
                            <input type="hidden" name="action" value="create_task">
                            <div class="mb-3">
                                <label for="task_list_id" class="form-label">Kategori</label>
                                <select id="task_list_id" name="task_list_id" class="form-select" required>
                                    <option value="">Välj kategori</option>
                                    <?php foreach ($taskLists as $list): ?>
                                        <option value="<?= (int) $list['id'] ?>" <?= (string) $taskForm['list_id'] === (string) $list['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($list['namn']) ?> (<?= htmlspecialchars($list['_slug'] ?? $list['kategori']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="task_title" class="form-label">Titel</label>
                                <input type="text" id="task_title" name="task_title" class="form-control" value="<?= htmlspecialchars($taskForm['title']) ?>" required>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="task_deadline" class="form-label">Deadline <span class="text-muted small">(valfritt)</span></label>
                                    <input type="date" id="task_deadline" name="task_deadline" class="form-control" value="<?= htmlspecialchars($taskForm['deadline']) ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="task_status" class="form-label">Status</label>
                                    <select id="task_status" name="task_status" class="form-select">
                                        <option value="Pågående" <?= $taskForm['status'] === 'Pågående' ? 'selected' : '' ?>>Pågående</option>
                                        <option value="Klar" <?= $taskForm['status'] === 'Klar' ? 'selected' : '' ?>>Klar</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3 mt-3">
                                <label for="task_assignee" class="form-label">Tilldela <span class="text-muted small">(valfritt)</span></label>
                                <select id="task_assignee" name="task_assignee" class="form-select">
                                    <option value="">Ingen specifik</option>
                                    <?php foreach ($users as $assignUser): ?>
                                        <option value="<?= (int) $assignUser['id'] ?>" <?= (string) $taskForm['assignee'] === (string) $assignUser['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($assignUser['namn']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary">Lägg till uppgift</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-7">
            <div class="card shadow-sm h-100">
                <div class="card-body d-flex flex-column">
                    <h2 class="h5">Kategorier</h2>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Namn</th>
                                    <th>Filtervärde</th>
                                    <th>Uppgifter</th>
                                    <th>Skapad</th>
                                    <th>Skapad av</th>
                                    <th class="text-end">Åtgärder</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($taskLists as $list): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-semibold"><?= htmlspecialchars($list['namn']) ?></div>
                                            <?php if (!empty($list['beskrivning'])): ?>
                                                <div class="small text-muted"><?= htmlspecialchars(mb_strimwidth($list['beskrivning'], 0, 80, '…')) ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($list['_slug'] ?? $list['kategori']) ?></td>
                                        <td><?= $taskCounts[$list['id']] ?? 0 ?></td>
                                        <td><?= htmlspecialchars(date('Y-m-d', strtotime($list['skapad_at']))) ?></td>
                                        <td><?= htmlspecialchars($userNameMap[(int) ($list['skapad_av'] ?? 0)] ?? 'Okänd') ?></td>
                                        <td class="text-end">
                                            <form method="post" class="d-inline" onsubmit="return confirm('Är du säker på att du vill ta bort denna kategori? Alla tillhörande uppgifter tas bort.');">
                                                <input type="hidden" name="action" value="delete_list">
                                                <input type="hidden" name="list_id" value="<?= (int) $list['id'] ?>">
                                                <button type="submit" class="btn btn-outline-danger btn-sm">Ta bort</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($taskLists)): ?>
                                    <tr>
                                        <td colspan="6" class="text-muted">Inga kategorier har skapats ännu.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <p class="small text-muted mt-auto mb-0">Tips: skapa kategorier med beskrivningar för att organisera familjens uppgifter. Filtreringsvärdet skapas automatiskt utifrån namnet.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mt-1">
        <div class="col-xl-5">
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h2 class="h5">Lägg till recept</h2>
                    <form method="post" novalidate>
                        <input type="hidden" name="action" value="create_recipe">
                        <div class="mb-3">
                            <label for="recipe_name" class="form-label">Receptnamn</label>
                            <input type="text" id="recipe_name" name="recipe_name" class="form-control" value="<?= htmlspecialchars($recipeForm['name']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="recipe_description" class="form-label">Kort beskrivning <span class="text-muted small">(valfritt)</span></label>
                            <textarea id="recipe_description" name="recipe_description" class="form-control" rows="3"><?= htmlspecialchars($recipeForm['description']) ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="recipe_url" class="form-label">Länk till recept <span class="text-muted small">(valfritt)</span></label>
                            <input type="url" id="recipe_url" name="recipe_url" class="form-control" value="<?= htmlspecialchars($recipeForm['url']) ?>">
                        </div>
                        <button type="submit" class="btn btn-primary">Spara recept</button>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-body">
                    <h2 class="h5">Receptbank</h2>
                    <?php if (!empty($recipes)): ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($recipes as $recipe): ?>
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-start gap-3">
                                        <div>
                                            <div class="fw-semibold"><?= htmlspecialchars($recipe['namn']) ?></div>
                                            <?php if (!empty($recipe['beskrivning'])): ?>
                                                <div class="small text-muted mb-1"><?= htmlspecialchars(mb_strimwidth($recipe['beskrivning'], 0, 120, '…')) ?></div>
                                            <?php endif; ?>
                                            <?php if (!empty($recipe['url'])): ?>
                                                <a class="small" href="<?= htmlspecialchars($recipe['url']) ?>" target="_blank" rel="noopener">Öppna recept</a>
                                            <?php endif; ?>
                                        </div>
                                        <form method="post" onsubmit="return confirm('Är du säker på att du vill ta bort detta recept? Eventuella kopplingar i middagsplaneringen rensas.');">
                                            <input type="hidden" name="action" value="delete_recipe">
                                            <input type="hidden" name="recipe_id" value="<?= (int) $recipe['id'] ?>">
                                            <button type="submit" class="btn btn-outline-danger btn-sm">Ta bort</button>
                                        </form>
                                    </div>
                                    <div class="small text-muted mt-2">Tillagd <?= htmlspecialchars(date('Y-m-d', strtotime($recipe['skapad_at']))) ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted mb-0">Inga recept skapade ännu. Lägg till recept för att använda dem i middagsplaneringen.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-xl-7">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h5">Veckans middagar</h2>
                    <p class="small text-muted">Fyll i rätten för varje dag. Välj gärna ett recept för att kunna återanvända det i framtiden. Lämna en dag tom för att rensa den ur planen.</p>
                    <form method="post" novalidate>
                        <input type="hidden" name="action" value="update_meal_plan">
                        <?php foreach ($mealDays as $dayKey => $dayLabel): ?>
                            <?php
                                $currentMeal = $mealPlan[$dayLabel] ?? null;
                                $currentDish = $currentMeal['dish'] ?? '';
                                $currentNote = $currentMeal['note'] ?? '';
                                $currentRecipeId = $currentMeal['recipe_id'] ?? null;
                            ?>
                            <div class="border rounded p-3 mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h3 class="h6 mb-0"><?= htmlspecialchars($dayLabel) ?></h3>
                                    <?php if ($currentRecipeId && isset($recipesById[(int) $currentRecipeId])): ?>
                                        <span class="badge text-bg-light">Recept: <?= htmlspecialchars($recipesById[(int) $currentRecipeId]['namn']) ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="row g-3">
                                    <div class="col-lg-6">
                                        <label for="meal_<?= $dayKey ?>_dish" class="form-label">Rätt</label>
                                        <input type="text" id="meal_<?= $dayKey ?>_dish" name="meals[<?= $dayKey ?>][dish]" class="form-control" value="<?= htmlspecialchars($currentDish) ?>" placeholder="Ex. Fiskgratäng">
                                    </div>
                                    <div class="col-lg-6">
                                        <label for="meal_<?= $dayKey ?>_recipe" class="form-label">Recept</label>
                                        <select id="meal_<?= $dayKey ?>_recipe" name="meals[<?= $dayKey ?>][recipe]" class="form-select">
                                            <option value="">Välj recept (valfritt)</option>
                                            <?php foreach ($recipes as $recipe): ?>
                                                <option value="<?= (int) $recipe['id'] ?>" <?= (string) $currentRecipeId === (string) $recipe['id'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($recipe['namn']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <label for="meal_<?= $dayKey ?>_note" class="form-label">Notering <span class="text-muted small">(valfritt)</span></label>
                                    <textarea id="meal_<?= $dayKey ?>_note" name="meals[<?= $dayKey ?>][note]" class="form-control" rows="2" placeholder="T.ex. Servera med sallad"><?= htmlspecialchars($currentNote) ?></textarea>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <button type="submit" class="btn btn-primary">Spara veckomenyn</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>

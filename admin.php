<?php
require_once __DIR__ . '/config.php';
require_admin();

$name = '';
$email = '';
$role = 'USER';
$message = '';
$messageType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'] === 'ADMIN' ? 'ADMIN' : 'USER';
    $password = $_POST['password'] ?? '';

    if ($name === '' || $email === '' || $password === '') {
        $message = 'Alla fält måste fyllas i.';
        $messageType = 'danger';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Ogiltig e-postadress.';
        $messageType = 'danger';
    } else {
        // Kontrollera om e-post redan finns
        $existsStmt = $pdo->prepare('SELECT 1 FROM users WHERE email = ? LIMIT 1');
        $existsStmt->execute([$email]);
        if ($existsStmt->fetchColumn()) {
            $message = 'En användare med den e-postadressen finns redan.';
            $messageType = 'danger';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $insertStmt = $pdo->prepare('INSERT INTO users (namn, email, losenord, roll) VALUES (?, ?, ?, ?)');
            $insertStmt->execute([$name, $email, $hash, $role]);

            $message = 'Ny användare skapades.';
            $messageType = 'success';
            $name = '';
            $email = '';
            $role = 'USER';
        }
    }
}

$usersStmt = $pdo->query('SELECT id, namn, email, roll FROM users ORDER BY namn');
$users = $usersStmt->fetchAll();
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
    <?php if ($message !== ''): ?>
        <div class="alert alert-<?= htmlspecialchars($messageType) ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h2 class="h5">Skapa ny användare</h2>
                    <form method="post" novalidate>
                        <div class="mb-3">
                            <label for="name" class="form-label">Namn</label>
                            <input type="text" id="name" name="name" class="form-control" value="<?= htmlspecialchars($name) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">E-post</label>
                            <input type="email" id="email" name="email" class="form-control" value="<?= htmlspecialchars($email) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Lösenord</label>
                            <input type="password" id="password" name="password" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="role" class="form-label">Roll</label>
                            <select id="role" name="role" class="form-select">
                                <option value="USER" <?= $role === 'USER' ? 'selected' : '' ?>>Användare</option>
                                <option value="ADMIN" <?= $role === 'ADMIN' ? 'selected' : '' ?>>Admin</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Skapa användare</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm">
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
</div>
</body>
</html>

<?php
require_once __DIR__ . '/config.php';

// Om användaren redan är inloggad, skicka vidare till dashboarden
if (!empty($_SESSION['user'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Fyll i både e-post och lösenord.';
    } else {
        $stmt = $pdo->prepare('SELECT id, namn, email, losenord, roll FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['losenord'])) {
            $_SESSION['user'] = [
                'id' => $user['id'],
                'name' => $user['namn'],
                'email' => $user['email'],
                'role' => $user['roll'],
            ];

            header('Location: dashboard.php');
            exit;
        }

        $error = 'Felaktiga inloggningsuppgifter.';
    }
}
?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Familyhub | Inloggning</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body class="bg-light d-flex align-items-center" style="min-height: 100vh;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <h1 class="h3 mb-3 text-center">Familyhub</h1>
                        <?php if ($error !== ''): ?>
                            <div class="alert alert-danger" role="alert">
                                <?= htmlspecialchars($error) ?>
                            </div>
                        <?php endif; ?>
                        <form method="post" novalidate>
                            <div class="mb-3">
                                <label for="email" class="form-label">E-post</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Lösenord</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Logga in</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

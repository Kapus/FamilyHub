<?php
// Temporärt skript för att sätta/uppdatera admin-lösenordet lokalt.
// Användning (lokalt via XAMPP):
// 1) Besök i webbläsaren: http://localhost/FamilyHub/tools/set_admin_password.php
// 2) Fyll i ett nytt lösenord i formuläret och klicka "Sätt lösenord".
// OBS: Ta bort denna fil efter användning!

require_once __DIR__ . '/../config.php';

// Konfigurera här vilket admin-email som ska uppdateras.
$adminEmail = 'admin@example.com';

function safe_output($s) { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pw = $_POST['password'] ?? '';
    if (trim($pw) === '') {
        $error = 'Lösenordet får inte vara tomt.';
    } else {
        $hash = password_hash($pw, PASSWORD_DEFAULT);
        try {
            // Kolla om användaren finns
            $check = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
            $check->execute([$adminEmail]);
            $row = $check->fetch();
            if ($row) {
                $stmt = $pdo->prepare('UPDATE users SET losenord = ? WHERE id = ?');
                $stmt->execute([$hash, $row['id']]);
                $message = "Lösenord uppdaterat för $adminEmail (user id: " . $row['id'] . ").";
            } else {
                // Skapa användaren som ADMIN
                $stmt = $pdo->prepare('INSERT INTO users (namn, email, losenord, roll) VALUES (?, ?, ?, ?)');
                $stmt->execute(['Admin', $adminEmail, $hash, 'ADMIN']);
                $message = "Ny admin-användare skapad med e-post $adminEmail.";
            }
        } catch (PDOException $e) {
            $error = 'Databasfel: ' . $e->getMessage();
        }
    }
}
?>
<!doctype html>
<html lang="sv">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Sätt admin-lösenord</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h1 class="h5">Sätt/uppdatera admin-lösenord</h1>
                    <p class="small text-muted">Ändrar lösenord för <strong><?= safe_output($adminEmail) ?></strong> i databasen <code>test</code>. Ta bort filen när du är klar.</p>

                    <?php if ($message): ?>
                        <div class="alert alert-success"><?= safe_output($message) ?></div>
                    <?php endif; ?>
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= safe_output($error) ?></div>
                    <?php endif; ?>

                    <form method="post">
                        <div class="mb-3">
                            <label for="password" class="form-label">Nytt lösenord</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <button class="btn btn-primary">Sätt lösenord</button>
                        <a class="btn btn-outline-secondary ms-2" href="/FamilyHub/tools/check_password.php">Verifiera</a>
                    </form>

                    <hr>
                    <p class="small text-muted mb-0">OBS: Detta är ett tillfälligt verktyg för lokal utveckling. Radera filen efter att du uppdaterat lösenordet:</p>
                    <pre class="small">Remove-Item 'c:\\xampp\\htdocs\\FamilyHub\\tools\\set_admin_password.php'</pre>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
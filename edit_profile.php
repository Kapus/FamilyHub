<?php
require_once __DIR__ . '/config.php';
require_login();

$userId = (int) ($_SESSION['user']['id'] ?? 0);

$stmt = $pdo->prepare('SELECT namn, profiltext, profilbild FROM users WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $userId]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: logout.php');
    exit;
}

$errors = [];
$profileTextInput = $user['profiltext'] ?? '';
$currentImage = $user['profilbild'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $profileTextInput = trim($_POST['profile_text'] ?? '');
    $profileTextValue = $profileTextInput === '' ? null : $profileTextInput;

    $newImageName = null;

    if (!empty($_FILES['profile_image']['name'])) {
        $file = $_FILES['profile_image'];
        if ($file['error'] === UPLOAD_ERR_OK) {
            $tmpPath = $file['tmp_name'];
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowedExtensions = ['jpg', 'jpeg', 'png'];

            if (!in_array($extension, $allowedExtensions, true)) {
                $errors[] = 'Endast JPG- eller PNG-filer är tillåtna.';
            } else {
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mimeType = $finfo->file($tmpPath) ?: '';
                $allowedMime = ['image/jpeg', 'image/png'];

                if (!in_array($mimeType, $allowedMime, true)) {
                    $errors[] = 'Ogiltig bildtyp.';
                } else {
                    $safeExtension = $extension === 'jpeg' ? 'jpg' : $extension;
                    try {
                        $uniqueSuffix = bin2hex(random_bytes(8));
                        $newImageName = sprintf('profile_%d_%s.%s', $userId, $uniqueSuffix, $safeExtension);
                        $destinationDir = __DIR__ . '/uploads';
                        $destinationPath = $destinationDir . '/' . $newImageName;

                        if (!is_dir($destinationDir)) {
                            mkdir($destinationDir, 0775, true);
                        }

                        if (!move_uploaded_file($tmpPath, $destinationPath)) {
                            $errors[] = 'Kunde inte spara den uppladdade bilden.';
                            $newImageName = null;
                        }
                    } catch (Exception $e) {
                        $errors[] = 'Kunde inte skapa ett filnamn. Försök igen.';
                        $newImageName = null;
                    }
                }
            }
        } elseif ($file['error'] !== UPLOAD_ERR_NO_FILE) {
            $errors[] = 'Ett fel uppstod vid uppladdning av filen.';
        }
    }

    if (!$errors) {
        $query = 'UPDATE users SET profiltext = :profileText';
        if ($newImageName !== null) {
            $query .= ', profilbild = :profileImage';
        }
        $query .= ' WHERE id = :id';

        $stmt = $pdo->prepare($query);
        if ($profileTextValue === null) {
            $stmt->bindValue(':profileText', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(':profileText', $profileTextValue, PDO::PARAM_STR);
        }
        if ($newImageName !== null) {
            $stmt->bindValue(':profileImage', $newImageName, PDO::PARAM_STR);
        }
        $stmt->bindValue(':id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        if ($newImageName !== null) {
            if (!empty($currentImage)) {
                $previousPath = __DIR__ . '/uploads/' . basename($currentImage);
                if (is_file($previousPath)) {
                    unlink($previousPath);
                }
            }
            $currentImage = $newImageName;
        }

        $_SESSION['profile_success'] = 'Profilen uppdaterades.';
        header('Location: profile.php');
        exit;
    }
}

$previewImage = 'assets/img/default-profile.svg';
if (!empty($currentImage)) {
    $imagePath = __DIR__ . '/uploads/' . basename($currentImage);
    if (is_file($imagePath)) {
        $previewImage = 'uploads/' . rawurlencode(basename($currentImage));
    }
}
?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Familyhub | Redigera profil</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom mb-4">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php">Familyhub</a>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary btn-sm" href="profile.php">Tillbaka</a>
        </div>
    </div>
</nav>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h1 class="h4 mb-3">Redigera profil</h1>
                    <?php if ($errors): ?>
                        <div class="alert alert-danger" role="alert">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?= htmlspecialchars($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    <form method="post" enctype="multipart/form-data">
                        <div class="mb-3 text-center">
                            <img src="<?= htmlspecialchars($previewImage) ?>" class="rounded-circle mb-2" alt="Profilbild" style="width: 140px; height: 140px; object-fit: cover;">
                            <div class="form-text">Nuvarande profilbild</div>
                        </div>
                        <div class="mb-3">
                            <label for="profile_text" class="form-label">Profiltext</label>
                            <textarea class="form-control" id="profile_text" name="profile_text" rows="5" placeholder="Skriv något om dig själv..."><?= htmlspecialchars($profileTextInput) ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="profile_image" class="form-label">Profilbild (JPG eller PNG)</label>
                            <input class="form-control" type="file" id="profile_image" name="profile_image" accept="image/jpeg,image/png">
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <a class="btn btn-link" href="profile.php">Avbryt</a>
                            <button type="submit" class="btn btn-primary">Spara ändringar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>

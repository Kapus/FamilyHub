<?php
// modules/photos.php
// Visar familjens minnen/foton
$photosStmt = $pdo->query('SELECT id, filnamn, album, kommentar, uppladdad_av FROM photos ORDER BY id DESC');
$photos = $photosStmt->fetchAll();

$uploaderStmt = $pdo->query('SELECT id, namn FROM users');
$uploaderMap = [];
foreach ($uploaderStmt as $member) {
    $uploaderMap[$member['id']] = $member['namn'];
}
?>
<div class="row g-3">
    <?php foreach ($photos as $photo): ?>
        <div class="col-sm-6 col-lg-4">
            <div class="card shadow-sm h-100">
                <?php if (!empty($photo['filnamn'])): ?>
                    <img src="uploads/<?= htmlspecialchars($photo['filnamn']) ?>" class="card-img-top" alt="Familjefoto">
                <?php endif; ?>
                <div class="card-body">
                    <h3 class="h6 card-title mb-2">Album: <?= htmlspecialchars($photo['album'] ?? 'Okänt') ?></h3>
                    <p class="card-text small mb-2"><?= nl2br(htmlspecialchars($photo['kommentar'] ?? '')) ?></p>
                    <p class="card-text"><small class="text-muted">Uppladdad av: <?= htmlspecialchars($uploaderMap[$photo['uppladdad_av']] ?? 'Okänd') ?></small></p>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    <?php if (empty($photos)): ?>
        <div class="col-12">
            <div class="alert alert-info mb-0">Inga minnen uppladdade ännu.</div>
        </div>
    <?php endif; ?>
</div>

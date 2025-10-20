<?php
require_once __DIR__ . '/../config.php';
require_login();

header('Content-Type: application/json');

$title = trim($_POST['titel'] ?? '');
$start = $_POST['startdatum'] ?? '';
$end = $_POST['slutdatum'] ?? '';
$color = $_POST['farg'] ?? '#0d6efd';
$userId = $_POST['anvandar_id'] ?? '';

if ($title === '' || $start === '') {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => 'Titel och startdatum måste anges.']);
    exit;
}

try {
    $startDate = new DateTimeImmutable($start);
    $endDate = $end !== '' ? new DateTimeImmutable($end) : $startDate;
    if ($endDate < $startDate) {
        throw new InvalidArgumentException('Slutdatum kan inte vara före startdatum.');
    }
} catch (Exception $ex) {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => 'Ogiltigt datumformat.']);
    exit;
}

$targetUserId = null;
if ($userId !== '') {
    $targetUserId = (int) $userId;
    if ($targetUserId <= 0) {
        $targetUserId = null;
    }
}

try {
    $stmt = $pdo->prepare(
        'INSERT INTO events (titel, datum, slut_datum, anvandar_id, farg) VALUES (:title, :start, :end, :user, :color)'
    );
    $stmt->execute([
        'title' => $title,
        'start' => $startDate->format('Y-m-d'),
        'end' => $endDate->format('Y-m-d'),
        'user' => $targetUserId,
        'color' => $color,
    ]);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Kunde inte spara händelsen.']);
}

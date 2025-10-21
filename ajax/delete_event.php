<?php
require_once __DIR__ . '/../config.php';
require_login();

header('Content-Type: application/json');

$id = isset($_POST['event_id']) ? (int) $_POST['event_id'] : 0;

if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Ogiltigt händelse-ID.']);
    exit;
}

try {
    $stmt = $pdo->prepare(
        'DELETE FROM events WHERE id = :id'
    );
    $stmt->execute([
        'id' => $id,
    ]);

    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Händelsen kunde inte tas bort.']);
        exit;
    }

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Kunde inte ta bort händelsen.']);
}

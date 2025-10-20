<?php
require_once __DIR__ . '/../config.php';
require_login();

header('Content-Type: application/json');

$message = trim($_POST['message'] ?? '');
$recipient = trim($_POST['recipient'] ?? '');

if ($message === '') {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => 'Meddelandet får inte vara tomt.']);
    exit;
}

$recipientId = $recipient !== '' ? (int) $recipient : null;

try {
    $stmt = $pdo->prepare('INSERT INTO messages (avsandare, mottagare, meddelande, timestamp) VALUES (:sender, :recipient, :message, NOW())');
    $stmt->bindValue(':sender', $_SESSION['user']['id'], PDO::PARAM_INT);
    $stmt->bindValue(':recipient', $recipientId, $recipientId !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
    $stmt->bindValue(':message', $message, PDO::PARAM_STR);
    $stmt->execute();

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Databasfel: ' . $e->getMessage()]);
}

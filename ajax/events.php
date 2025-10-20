<?php
require_once __DIR__ . '/../config.php';
require_login();

header('Content-Type: application/json');

$start = $_GET['start'] ?? null;
$end = $_GET['end'] ?? null;

if (!$start || !$end) {
    http_response_code(400);
    echo json_encode(['error' => 'Start och slutdatum krävs.']);
    exit;
}

try {
    $startDate = (new DateTimeImmutable($start))->format('Y-m-d');
    $endDate = (new DateTimeImmutable($end))->format('Y-m-d');
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => 'Ogiltigt datumformat.']);
    exit;
}

try {
        $stmt = $pdo->prepare(
                'SELECT id, titel, datum, COALESCE(slut_datum, datum) AS slut_datum, farg
                     FROM events
                    WHERE (anvandar_id = :user OR anvandar_id IS NULL)
                        AND datum <= :end
                        AND (slut_datum IS NULL OR slut_datum >= :start)
                    ORDER BY datum ASC'
        );
    $stmt->execute([
        'user' => $_SESSION['user']['id'],
        'start' => $startDate,
        'end' => $endDate,
    ]);
    $rows = $stmt->fetchAll();

    $events = array_map(static function (array $row): array {
        $color = $row['farg'] ?? '#0d6efd';
        $start = new DateTimeImmutable($row['datum']);
        $end = new DateTimeImmutable($row['slut_datum']);
        // FullCalendar behandlar end som exklusivt, lägg till en dag
        $exclusiveEnd = $end->modify('+1 day');
        return [
            'id' => $row['id'],
            'title' => $row['titel'],
            'start' => $start->format('Y-m-d'),
            'end' => $exclusiveEnd->format('Y-m-d'),
            'allDay' => true,
            'backgroundColor' => $color,
            'borderColor' => $color,
        ];
    }, $rows);

    echo json_encode($events);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Kunde inte hämta händelser.']);
}

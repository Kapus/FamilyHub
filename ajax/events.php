<?php
require_once __DIR__ . '/../config.php';
require_login();

header('Content-Type: application/json');

$start = $_GET['start'] ?? null;
$end = $_GET['end'] ?? null;
$filterUser = $_GET['userId'] ?? 'all';

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
        $query = 'SELECT id, titel, datum, COALESCE(slut_datum, datum) AS slut_datum, anvandar_id, farg
                     FROM events
                    WHERE datum <= :end
                      AND (slut_datum IS NULL OR slut_datum >= :start)';
        $params = [
            'start' => $startDate,
            'end' => $endDate,
        ];

        if ($filterUser === 'family') {
            $query .= ' AND anvandar_id IS NULL';
        } elseif ($filterUser !== '' && $filterUser !== 'all') {
            $filterId = filter_var($filterUser, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($filterId !== false) {
                $query .= ' AND (anvandar_id = :filterUser OR anvandar_id IS NULL)';
                $params['filterUser'] = $filterId;
            }
        }

        $query .= ' ORDER BY datum ASC';

        $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    $events = array_map(static function (array $row): array {
        $color = $row['farg'] ?? '#0d6efd';
        $start = new DateTimeImmutable($row['datum']);
        $inclusiveEnd = new DateTimeImmutable($row['slut_datum']);
        // FullCalendar behandlar end som exklusivt, lägg till en dag
        $exclusiveEnd = $inclusiveEnd->modify('+1 day');
        return [
            'id' => $row['id'],
            'title' => $row['titel'],
            'start' => $start->format('Y-m-d'),
            'end' => $exclusiveEnd->format('Y-m-d'),
            'allDay' => true,
            'backgroundColor' => $color,
            'borderColor' => $color,
            'extendedProps' => [
                'inclusiveEnd' => $inclusiveEnd->format('Y-m-d'),
                'anvandarId' => $row['anvandar_id'],
                'color' => $color,
            ],
        ];
    }, $rows);

    echo json_encode($events);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Kunde inte hämta händelser.']);
}

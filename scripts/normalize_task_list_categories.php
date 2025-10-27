<?php
require_once __DIR__ . '/../config.php';

if (!isset($pdo) || !($pdo instanceof PDO)) {
    fwrite(STDERR, "Database connection missing.\n");
    exit(1);
}

function slugify_category(string $value): string
{
    $value = trim($value);
    if ($value === '') {
        return '';
    }

    if (function_exists('iconv')) {
        $converted = iconv('UTF-8', 'ASCII//TRANSLIT', $value);
        if ($converted !== false) {
            $value = $converted;
        }
    }

    $value = strtolower($value);
    $value = preg_replace('/[^a-z0-9]+/', '-', $value);

    return trim($value, '-');
}

$listsStmt = $pdo->query('SELECT id, namn, kategori FROM task_lists ORDER BY id');
$lists = $listsStmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($lists)) {
    echo "No task lists found.\n";
    exit(0);
}

$usedSlugs = [];
$updates = [];

foreach ($lists as $list) {
    $id = (int) $list['id'];
    $name = (string) ($list['namn'] ?? '');
    $storedCategory = (string) ($list['kategori'] ?? '');

    $normalized = strtolower(trim($storedCategory));
    if (!preg_match('/^[a-z0-9-]+$/', $normalized)) {
        $normalized = '';
    }

    if ($normalized === '') {
        $normalized = slugify_category($storedCategory);
    }

    if ($normalized === '') {
        $normalized = slugify_category($name);
    }

    if ($normalized === '') {
        $normalized = 'kategori-' . $id;
    }

    $candidate = $normalized;
    $suffix = 2;
    while (array_key_exists($candidate, $usedSlugs)) {
        $candidate = $normalized . '-' . $suffix;
        $suffix++;
    }

    $usedSlugs[$candidate] = true;

    if ($candidate !== $storedCategory) {
        $updates[] = ['id' => $id, 'slug' => $candidate, 'from' => $storedCategory];
    }
}

if (empty($updates)) {
    echo "All task list categories already normalized.\n";
    exit(0);
}

$updateStmt = $pdo->prepare('UPDATE task_lists SET kategori = :slug WHERE id = :id');

foreach ($updates as $update) {
    $updateStmt->execute([
        ':slug' => $update['slug'],
        ':id' => $update['id'],
    ]);
    echo sprintf("Updated list #%d: '%s' -> '%s'\n", $update['id'], $update['from'], $update['slug']);
}

echo "Done.\n";

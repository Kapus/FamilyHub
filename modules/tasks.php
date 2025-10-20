<?php
// modules/tasks.php
// Visar att göra-lista med uppgifter för den inloggade användaren
$tasksStmt = $pdo->prepare('SELECT id, titel, deadline, status, tilldelad_till FROM tasks WHERE tilldelad_till = :user_id OR tilldelad_till IS NULL ORDER BY deadline ASC');
$tasksStmt->execute(['user_id' => $user['id']]);
$tasks = $tasksStmt->fetchAll();
?>
<div class="card shadow-sm">
    <div class="card-body">
        <h2 class="h5 mb-3">Att göra-listor</h2>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Uppgift</th>
                        <th>Deadline</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tasks as $task): ?>
                        <tr>
                            <td><?= htmlspecialchars($task['titel']) ?></td>
                            <td><?= $task['deadline'] ? htmlspecialchars(date('Y-m-d', strtotime($task['deadline']))) : '&ndash;' ?></td>
                            <td>
                                <span class="badge bg-<?= $task['status'] === 'Klar' ? 'success' : 'secondary' ?>">
                                    <?= htmlspecialchars($task['status'] ?? 'Pågående') ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($tasks)): ?>
                        <tr>
                            <td colspan="3" class="text-muted">Inga uppgifter tilldelade ännu.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

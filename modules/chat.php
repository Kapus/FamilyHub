<?php
// modules/chat.php
// Enkel familjechatt med stöd för broadcast eller direktmeddelanden
$messagesStmt = $pdo->prepare('
    SELECT m.id, m.avsandare, m.mottagare, m.meddelande, m.timestamp,
         sender.namn AS sender_name, recipient.namn AS recipient_name
      FROM messages m
     LEFT JOIN users sender ON sender.id = m.avsandare
     LEFT JOIN users recipient ON recipient.id = m.mottagare
     WHERE m.mottagare IS NULL OR m.mottagare = :user_id OR m.avsandare = :user_id
     ORDER BY m.timestamp DESC
     LIMIT 30
');
$messagesStmt->execute(['user_id' => $user['id']]);
$messages = $messagesStmt->fetchAll();

$familyStmt = $pdo->prepare('SELECT id, namn FROM users WHERE id != :user_id ORDER BY namn');
$familyStmt->execute(['user_id' => $user['id']]);
$familyMembers = $familyStmt->fetchAll();
?>
<div class="row g-4">
    <div class="col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-body d-flex flex-column">
                <h2 class="h5 mb-3">Skicka meddelande</h2>
                <form id="chat-form" class="d-flex flex-column gap-3" autocomplete="off">
                    <div>
                        <label for="message" class="form-label">Meddelande</label>
                        <textarea id="message" name="message" class="form-control" rows="3" required></textarea>
                    </div>
                    <div>
                        <label for="recipient" class="form-label">Mottagare</label>
                        <select id="recipient" name="recipient" class="form-select">
                            <option value="">Alla</option>
                            <?php foreach ($familyMembers as $member): ?>
                                <option value="<?= (int) $member['id'] ?>"><?= htmlspecialchars($member['namn']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary align-self-start">Skicka</button>
                    <div id="chat-feedback" class="small text-muted" role="status"></div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h2 class="h5 mb-3">Senaste meddelanden</h2>
                <div class="list-group list-group-flush">
                    <?php foreach ($messages as $message): ?>
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between">
                                <strong><?= htmlspecialchars($message['sender_name'] ?? 'Okänd') ?></strong>
                                <small class="text-muted"><?= htmlspecialchars(date('Y-m-d H:i', strtotime($message['timestamp']))) ?></small>
                            </div>
                            <div class="text-muted small mb-1">
                                <?php if ($message['recipient_name']): ?>
                                    Till: <?= htmlspecialchars($message['recipient_name']) ?>
                                <?php else: ?>
                                    Till: Alla
                                <?php endif; ?>
                            </div>
                            <div><?= nl2br(htmlspecialchars($message['meddelande'])) ?></div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($messages)): ?>
                        <div class="list-group-item text-muted">Inga meddelanden ännu.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

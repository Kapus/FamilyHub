<?php
$familyStmt = $pdo->query('SELECT id, namn FROM users ORDER BY namn');
$familyMembers = $familyStmt->fetchAll();
?>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="h5 mb-0">Händelser</h2>
            <button type="button" class="btn btn-outline-primary btn-sm" data-calendar-toggle aria-expanded="false">
                Lägg till
            </button>
        </div>
    <div class="mt-3 d-none" data-calendar-form>
            <form id="calendar-event-form" class="row g-3">
                <div class="col-md-6">
                    <label for="event-title" class="form-label">Titel</label>
                    <input type="text" class="form-control" id="event-title" name="titel" required>
                </div>
                <div class="col-md-3">
                    <label for="event-start" class="form-label">Startdatum</label>
                    <input type="date" class="form-control" id="event-start" name="startdatum" required>
                </div>
                <div class="col-md-3">
                    <label for="event-end" class="form-label">Slutdatum</label>
                    <input type="date" class="form-control" id="event-end" name="slutdatum">
                </div>
                <div class="col-md-4">
                    <label for="event-user" class="form-label">Tillhör</label>
                    <select class="form-select" id="event-user" name="anvandar_id">
                        <option value="">Hela familjen</option>
                        <?php foreach ($familyMembers as $member): ?>
                            <option value="<?= (int) $member['id'] ?>"><?= htmlspecialchars($member['namn']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="event-color" class="form-label">Färg</label>
                    <input type="color" class="form-control form-control-color" id="event-color" name="farg" value="#0d6efd" title="Välj färg">
                </div>
                <div class="col-md-6 d-flex align-items-end">
                    <div class="form-text mb-0">Lämna slutdatum tomt för endags-händelser.</div>
                </div>
                <div class="col-md-6 d-flex align-items-end justify-content-end">
                    <button type="submit" class="btn btn-primary">Spara händelse</button>
                </div>
                <div class="col-12">
                    <div id="calendar-feedback" class="small"></div>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <div class="d-flex flex-wrap gap-3 justify-content-between align-items-center mb-3">
            <div>
                <h2 class="h5 mb-1">Familjekalender</h2>
                <div class="text-muted small" data-calendar-title></div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <div class="btn-group" role="group" aria-label="Navigering">
                    <button class="btn btn-outline-primary btn-sm" data-calendar-action="prev">&laquo;</button>
                    <button class="btn btn-outline-primary btn-sm" data-calendar-action="today">Idag</button>
                    <button class="btn btn-outline-primary btn-sm" data-calendar-action="next">&raquo;</button>
                </div>
                <div class="btn-group" role="group" aria-label="Vyer">
                    <button class="btn btn-outline-secondary btn-sm" data-calendar-view="dayGridMonth">Månad</button>
                    <button class="btn btn-outline-secondary btn-sm" data-calendar-view="dayGridWeek">Vecka</button>
                    <button class="btn btn-outline-secondary btn-sm" data-calendar-view="dayGridDay">Dag</button>
                </div>
            </div>
        </div>
        <div id="familyhub-calendar"></div>
    </div>
</div>

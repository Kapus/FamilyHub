<?php
$familyStmt = $pdo->query('SELECT id, namn FROM users ORDER BY namn');
$familyMembers = $familyStmt->fetchAll();
?>

<div class="card shadow-sm">
    <div class="card-body">
        <div class="d-flex flex-wrap gap-3 justify-content-between align-items-center mb-3">
            <div class="flex-grow-1">
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <h2 class="h5 mb-0">Familjekalender</h2>
                    <button type="button" class="btn btn-primary btn-sm" data-calendar-create>Lägg till</button>
                    <select class="form-select form-select-sm w-auto" data-calendar-filter aria-label="Filtrera händelser">
                        <option value="all" selected>Alla händelser</option>
                        <option value="family">Hela familjen</option>
                        <?php foreach ($familyMembers as $member): ?>
                            <option value="<?= (int) $member['id'] ?>"><?= htmlspecialchars($member['namn']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
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

<div class="modal fade" id="calendar-event-modal" tabindex="-1" aria-labelledby="calendarEventModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" id="calendar-modal-form">
            <div class="modal-header">
                <h5 class="modal-title" id="calendarEventModalLabel" data-calendar-modal-title data-edit-label="Redigera händelse" data-create-label="Ny händelse">Redigera händelse</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Stäng"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="event_id" id="modal-event-id">
                <div class="mb-3">
                    <label for="modal-event-title" class="form-label">Titel</label>
                    <input type="text" class="form-control" id="modal-event-title" name="titel" required>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="modal-event-start" class="form-label">Startdatum</label>
                        <input type="date" class="form-control" id="modal-event-start" name="startdatum" required>
                    </div>
                    <div class="col-md-6">
                        <label for="modal-event-end" class="form-label">Slutdatum</label>
                        <input type="date" class="form-control" id="modal-event-end" name="slutdatum">
                    </div>
                </div>
                <div class="row g-3 mt-0">
                    <div class="col-md-6">
                        <label for="modal-event-user" class="form-label">Tillhör</label>
                        <select class="form-select" id="modal-event-user" name="anvandar_id">
                            <option value="">Hela familjen</option>
                            <?php foreach ($familyMembers as $member): ?>
                                <option value="<?= (int) $member['id'] ?>"><?= htmlspecialchars($member['namn']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="modal-event-color" class="form-label">Färg</label>
                        <input type="color" class="form-control form-control-color" id="modal-event-color" name="farg" value="#0d6efd">
                    </div>
                </div>
                <div class="form-text mt-2">Lämna slutdatum tomt för endags-händelser.</div>
                <div id="calendar-modal-feedback" class="small mt-2"></div>
            </div>
            <div class="modal-footer d-flex justify-content-between">
                <button type="button" class="btn btn-outline-danger" data-calendar-delete>Ta bort</button>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Avbryt</button>
                    <button type="submit" class="btn btn-primary" data-calendar-save data-edit-text="Spara ändringar" data-create-text="Skapa händelse">Spara ändringar</button>
                </div>
            </div>
        </form>
    </div>
</div>

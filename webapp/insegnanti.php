<?php
ob_start();
ini_set('log_errors', '1');
ini_set('display_errors', '0');
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/functions.php';
require_once __DIR__ . '/config/encryption.php';
require_once __DIR__ . '/includes/auth.php';
requireAuth();

$pdo = Database::getInstance();

function teacherNullableString(string $value): ?string
{
    $value = trim($value);
    return $value === '' ? null : $value;
}

function teacherMoney(string $value): float
{
    $normalized = str_replace(',', '.', trim($value));
    return is_numeric($normalized) ? max(0, (float)$normalized) : 0.0;
}

$requestAction = $_SERVER['REQUEST_METHOD'] === 'POST' ? post('action') : get('action');

if ($requestAction !== '') {
    try {
        if ($requestAction === 'save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            verifyCsrf();

            $id = sanitizeInt(post('id'));
            $nome = trim(post('nome'));
            $cognome = trim(post('cognome'));
            $telefono = teacherNullableString(post('telefono'));
            $emailRaw = teacherNullableString(post('email'));
            $tariffaOraria = teacherMoney(post('tariffa_oraria'));
            $strumenti = $_POST['strumenti'] ?? [];
            $strumentiIds = [];

            if ($nome === '' || $cognome === '') {
                jsonResponse(['success' => false, 'message' => 'Nome e cognome sono obbligatori.'], 422);
            }

            $email = null;
            if ($emailRaw !== null) {
                if (!filter_var($emailRaw, FILTER_VALIDATE_EMAIL)) {
                    jsonResponse(['success' => false, 'message' => 'Inserisci un indirizzo email valido.'], 422);
                }
                $email = $emailRaw;
            }

            foreach ((array)$strumenti as $strumentoId) {
                $cleanId = (int)$strumentoId;
                if ($cleanId > 0) {
                    $strumentiIds[] = $cleanId;
                }
            }
            $strumentiIds = array_values(array_unique($strumentiIds));

            $pdo->beginTransaction();

            if ($id > 0) {
                $stmt = $pdo->prepare(
                    'UPDATE insegnanti
                     SET nome = ?, cognome = ?, telefono = ?, email = ?, tariffa_oraria = ?
                     WHERE id = ?'
                );
                $stmt->execute([$nome, $cognome, $telefono, $email, $tariffaOraria, $id]);
            } else {
                $stmt = $pdo->prepare(
                    'INSERT INTO insegnanti (nome, cognome, telefono, email, tariffa_oraria)
                     VALUES (?, ?, ?, ?, ?)'
                );
                $stmt->execute([$nome, $cognome, $telefono, $email, $tariffaOraria]);
                $id = (int)$pdo->lastInsertId();
            }

            $stmt = $pdo->prepare('DELETE FROM insegnanti_strumenti WHERE insegnante_id = ?');
            $stmt->execute([$id]);

            if ($strumentiIds !== []) {
                $stmt = $pdo->prepare('INSERT INTO insegnanti_strumenti (insegnante_id, strumento_id) VALUES (?, ?)');
                foreach ($strumentiIds as $strumentoId) {
                    $stmt->execute([$id, $strumentoId]);
                }
            }

            $pdo->commit();
            jsonResponse(['success' => true, 'message' => 'Insegnante salvato con successo.', 'id' => $id]);
        }

        if ($requestAction === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            verifyCsrf();

            $id = sanitizeInt(post('id'));
            if ($id <= 0) {
                jsonResponse(['success' => false, 'message' => 'Insegnante non valido.'], 422);
            }

            $stmt = $pdo->prepare('SELECT COUNT(*) FROM prenotazioni WHERE insegnante_id = ?');
            $stmt->execute([$id]);
            if ((int)$stmt->fetchColumn() > 0) {
                jsonResponse(['success' => false, 'message' => 'Impossibile eliminare l\'insegnante: sono presenti prenotazioni collegate.'], 409);
            }

            $stmt = $pdo->prepare('DELETE FROM insegnanti WHERE id = ?');
            $stmt->execute([$id]);

            if ($stmt->rowCount() === 0) {
                jsonResponse(['success' => false, 'message' => 'Insegnante non trovato.'], 404);
            }

            jsonResponse(['success' => true, 'message' => 'Insegnante eliminato con successo.']);
        }

        if ($requestAction === 'save_tariffa_coppia' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            verifyCsrf();

            $teacherId = sanitizeInt(post('insegnante_id'));
            $tariffa = teacherMoney(post('tariffa'));

            if ($teacherId <= 0) {
                jsonResponse(['success' => false, 'message' => 'Insegnante non valido.'], 422);
            }

            $stmt = $pdo->prepare(
                'INSERT INTO tariffe_coppia (insegnante_id, tariffa)
                 VALUES (?, ?)
                 ON DUPLICATE KEY UPDATE tariffa = VALUES(tariffa)'
            );
            $stmt->execute([$teacherId, $tariffa]);

            jsonResponse(['success' => true, 'message' => 'Tariffa di coppia aggiornata.', 'tariffa' => $tariffa]);
        }

        if ($requestAction === 'get') {
            $id = sanitizeInt($_GET['id'] ?? $_POST['id'] ?? 0);
            if ($id <= 0) {
                jsonResponse(['success' => false, 'message' => 'Insegnante non valido.'], 422);
            }

            $stmt = $pdo->prepare('SELECT * FROM insegnanti WHERE id = ? LIMIT 1');
            $stmt->execute([$id]);
            $teacher = $stmt->fetch();
            if (!$teacher) {
                jsonResponse(['success' => false, 'message' => 'Insegnante non trovato.'], 404);
            }

            $stmt = $pdo->prepare(
                'SELECT s.id, s.nome
                 FROM insegnanti_strumenti ins
                 INNER JOIN strumenti s ON s.id = ins.strumento_id
                 WHERE ins.insegnante_id = ?
                 ORDER BY s.nome ASC'
            );
            $stmt->execute([$id]);
            $strumentiRows = $stmt->fetchAll();
            $teacher['strumenti'] = array_map(static fn(array $row): int => (int)$row['id'], $strumentiRows);
            $teacher['strumenti_nomi'] = array_map(static fn(array $row): string => (string)$row['nome'], $strumentiRows);

            $stmt = $pdo->prepare('SELECT tariffa FROM tariffe_coppia WHERE insegnante_id = ? LIMIT 1');
            $stmt->execute([$id]);
            $teacher['tariffa_coppia'] = $stmt->fetchColumn();

            $stmt = $pdo->prepare(
                "SELECT p.data, p.ora_inizio, p.ora_fine, p.stato, p.strumento, c.nome, c.cognome
                 FROM prenotazioni p
                 INNER JOIN clienti c ON c.id = p.cliente_id
                 WHERE p.insegnante_id = ? AND p.data >= CURDATE()
                 ORDER BY p.data ASC, p.ora_inizio ASC
                 LIMIT 5"
            );
            $stmt->execute([$id]);
            $teacher['upcoming_lessons'] = $stmt->fetchAll();

            jsonResponse(['success' => true, 'teacher' => $teacher]);
        }
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        jsonResponse(['success' => false, 'message' => 'Errore durante l\'operazione richiesta.'], 500);
    }
}

$teachers = [];
$instruments = [];
$pageError = '';

try {
    $stmt = $pdo->prepare(
        'SELECT i.id, i.nome, i.cognome, i.email, i.telefono, i.tariffa_oraria,
                COALESCE(GROUP_CONCAT(DISTINCT s.nome ORDER BY s.nome SEPARATOR ", "), "") AS strumenti
         FROM insegnanti i
         LEFT JOIN insegnanti_strumenti ins ON ins.insegnante_id = i.id
         LEFT JOIN strumenti s ON s.id = ins.strumento_id
         GROUP BY i.id, i.nome, i.cognome, i.email, i.telefono, i.tariffa_oraria
         ORDER BY i.cognome ASC, i.nome ASC'
    );
    $stmt->execute();
    $teachers = $stmt->fetchAll();

    $stmt = $pdo->prepare('SELECT id, nome FROM strumenti ORDER BY nome ASC');
    $stmt->execute();
    $instruments = $stmt->fetchAll();
} catch (PDOException $e) {
    $pageError = 'Impossibile caricare insegnanti o strumenti.';
}

require_once __DIR__ . '/includes/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h2 class="mb-1">Insegnanti</h2>
        <p class="text-secondary mb-0">Gestisci docenti, strumenti associati e tariffe.</p>
    </div>
    <button type="button" class="btn btn-primary" id="newTeacherBtn">
        <i class="fas fa-plus me-2"></i>Nuovo Insegnante
    </button>
</div>

<?php if ($pageError !== ''): ?>
<div class="alert alert-warning">
    <i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($pageError) ?>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <i class="fas fa-chalkboard-teacher"></i>
        Elenco insegnanti
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table datatable align-middle" id="insegnantiTable">
                <thead>
                    <tr>
                        <th>#ID</th>
                        <th>Nome Cognome</th>
                        <th>Email</th>
                        <th>Telefono</th>
                        <th>Tariffa Oraria</th>
                        <th>Strumenti</th>
                        <th>Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($teachers as $teacher): ?>
                    <tr>
                        <td><?= htmlspecialchars((string)$teacher['id']) ?></td>
                        <td class="fw-semibold"><?= htmlspecialchars(trim((string)$teacher['nome'] . ' ' . (string)$teacher['cognome'])) ?></td>
                        <td><?= htmlspecialchars((string)($teacher['email'] ?: '—')) ?></td>
                        <td><?= htmlspecialchars((string)($teacher['telefono'] ?: '—')) ?></td>
                        <td>€ <?= htmlspecialchars(number_format((float)$teacher['tariffa_oraria'], 2, ',', '.')) ?></td>
                        <td><?= htmlspecialchars($teacher['strumenti'] !== '' ? (string)$teacher['strumenti'] : '—') ?></td>
                        <td>
                            <div class="d-flex flex-wrap gap-2">
                                <button type="button" class="btn btn-sm btn-outline-info btn-view-teacher" data-id="<?= htmlspecialchars((string)$teacher['id']) ?>">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-primary btn-edit-teacher" data-id="<?= htmlspecialchars((string)$teacher['id']) ?>">
                                    <i class="fas fa-pen"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger btn-delete-teacher" data-id="<?= htmlspecialchars((string)$teacher['id']) ?>" data-name="<?= htmlspecialchars(trim((string)$teacher['nome'] . ' ' . (string)$teacher['cognome'])) ?>">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="teacherModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <form id="teacherForm" method="post" action="insegnanti.php">
                <div class="modal-header">
                    <h5 class="modal-title" id="teacherModalTitle">Nuovo Insegnante</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Chiudi"></button>
                </div>
                <div class="modal-body">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" id="teacher_id" value="">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="teacher_nome" class="form-label">Nome *</label>
                            <input type="text" class="form-control" id="teacher_nome" name="nome" required>
                        </div>
                        <div class="col-md-6">
                            <label for="teacher_cognome" class="form-label">Cognome *</label>
                            <input type="text" class="form-control" id="teacher_cognome" name="cognome" required>
                        </div>
                        <div class="col-md-6">
                            <label for="teacher_telefono" class="form-label">Telefono</label>
                            <input type="text" class="form-control" id="teacher_telefono" name="telefono">
                        </div>
                        <div class="col-md-6">
                            <label for="teacher_email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="teacher_email" name="email">
                        </div>
                        <div class="col-md-6">
                            <label for="teacher_tariffa_oraria" class="form-label">Tariffa Oraria (€)</label>
                            <input type="number" class="form-control" id="teacher_tariffa_oraria" name="tariffa_oraria" min="0" step="0.01" value="0.00">
                        </div>
                        <div class="col-12">
                            <label class="form-label d-block">Strumenti</label>
                            <div class="row g-2">
                                <?php if ($instruments === []): ?>
                                <div class="col-12">
                                    <div class="alert alert-secondary mb-0">Nessuno strumento disponibile.</div>
                                </div>
                                <?php else: ?>
                                    <?php foreach ($instruments as $instrument): ?>
                                    <div class="col-sm-6 col-lg-4">
                                        <label class="instrument-option">
                                            <input class="form-check-input me-2 instrument-checkbox" type="checkbox" name="strumenti[]" value="<?= htmlspecialchars((string)$instrument['id']) ?>">
                                            <span><?= htmlspecialchars((string)$instrument['nome']) ?></span>
                                        </label>
                                    </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Salva Insegnante
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="teacherDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Dettaglio Insegnante</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Chiudi"></button>
            </div>
            <div class="modal-body">
                <div class="row g-4">
                    <div class="col-lg-5">
                        <div class="detail-box mb-3">
                            <div class="detail-label">Nome e cognome</div>
                            <div class="detail-value" id="teacher_detail_name">—</div>
                        </div>
                        <div class="detail-box mb-3">
                            <div class="detail-label">Contatti</div>
                            <div class="detail-value" id="teacher_detail_contacts">—</div>
                        </div>
                        <div class="detail-box mb-3">
                            <div class="detail-label">Tariffa oraria</div>
                            <div class="detail-value" id="teacher_detail_tariffa">—</div>
                        </div>
                        <div class="detail-box mb-3">
                            <div class="detail-label">Strumenti</div>
                            <div class="detail-value" id="teacher_detail_strumenti">—</div>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <a href="#" class="btn btn-primary d-none" id="teacher_pdf_futuri" target="_blank" rel="noopener noreferrer">
                                <i class="fas fa-file-pdf me-2"></i>PDF Lezioni Future
                            </a>
                            <a href="#" class="btn btn-secondary d-none" id="teacher_pdf_storico" target="_blank" rel="noopener noreferrer">
                                <i class="fas fa-file-pdf me-2"></i>PDF Storico
                            </a>
                        </div>
                    </div>
                    <div class="col-lg-7">
                        <div class="detail-box mb-4">
                            <div class="detail-label">Tariffa coppia</div>
                            <form id="tariffaCoppiaForm" action="insegnanti.php" method="post" class="row g-3 align-items-end">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="save_tariffa_coppia">
                                <input type="hidden" name="insegnante_id" id="tariffa_insegnante_id" value="">
                                <div class="col-sm-8">
                                    <label for="tariffa_coppia" class="form-label">Importo (€)</label>
                                    <input type="number" class="form-control" id="tariffa_coppia" name="tariffa" min="0" step="0.01" value="0.00">
                                </div>
                                <div class="col-sm-4">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-save me-2"></i>Salva
                                    </button>
                                </div>
                            </form>
                        </div>

                        <div class="detail-box">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="detail-label mb-0">Prossime lezioni</div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-sm align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>Data</th>
                                            <th>Orario</th>
                                            <th>Cliente</th>
                                            <th>Strumento</th>
                                            <th>Stato</th>
                                        </tr>
                                    </thead>
                                    <tbody id="teacherUpcomingLessonsBody">
                                        <tr>
                                            <td colspan="5" class="text-secondary text-center py-3">Nessuna lezione.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.text-secondary { color: var(--text-secondary) !important; }
.detail-box {
    background: rgba(255,255,255,0.02);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-sm);
    padding: 16px;
}
.detail-label {
    color: var(--text-muted);
    text-transform: uppercase;
    font-size: 0.78rem;
    margin-bottom: 6px;
}
.detail-value {
    white-space: pre-wrap;
    word-break: break-word;
}
.instrument-option {
    display: flex;
    align-items: center;
    background: rgba(255,255,255,0.02);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-sm);
    padding: 10px 12px;
    width: 100%;
    cursor: pointer;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const teacherModalEl = document.getElementById('teacherModal');
    const teacherDetailModalEl = document.getElementById('teacherDetailModal');
    const teacherModal = new bootstrap.Modal(teacherModalEl);
    const teacherDetailModal = new bootstrap.Modal(teacherDetailModalEl);
    const teacherForm = document.getElementById('teacherForm');
    const tariffaCoppiaForm = document.getElementById('tariffaCoppiaForm');

    function resetTeacherForm() {
        teacherForm.reset();
        document.getElementById('teacher_id').value = '';
        document.getElementById('teacher_tariffa_oraria').value = '0.00';
        document.getElementById('teacherModalTitle').textContent = 'Nuovo Insegnante';
        document.querySelectorAll('.instrument-checkbox').forEach((checkbox) => {
            checkbox.checked = false;
        });
    }

    function euroValue(value) {
        return '€ ' + Number(value || 0).toLocaleString('it-IT', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function renderStatusBadge(status) {
        const colorMap = {
            'Programmata': 'primary',
            'Svolta': 'success',
            'Assente': 'danger',
            'Rimandata': 'warning',
            'Riprogrammata': 'info'
        };
        const color = colorMap[status] || 'secondary';
        return `<span class="badge bg-${color}">${escapeHtml(status || '')}</span>`;
    }

    async function fetchTeacher(id) {
        const response = await fetch(`insegnanti.php?action=get&id=${encodeURIComponent(id)}`);
        const data = await response.json();
        if (!data.success) {
            throw new Error(data.message || 'Errore nel caricamento dell\'insegnante.');
        }
        return data.teacher;
    }

    document.getElementById('newTeacherBtn').addEventListener('click', () => {
        resetTeacherForm();
        teacherModal.show();
    });

    document.querySelectorAll('.btn-edit-teacher').forEach((button) => {
        button.addEventListener('click', async () => {
            try {
                const teacher = await fetchTeacher(button.dataset.id);
                resetTeacherForm();
                document.getElementById('teacherModalTitle').textContent = 'Modifica Insegnante';
                document.getElementById('teacher_id').value = teacher.id || '';
                document.getElementById('teacher_nome').value = teacher.nome || '';
                document.getElementById('teacher_cognome').value = teacher.cognome || '';
                document.getElementById('teacher_telefono').value = teacher.telefono || '';
                document.getElementById('teacher_email').value = teacher.email || '';
                document.getElementById('teacher_tariffa_oraria').value = Number(teacher.tariffa_oraria || 0).toFixed(2);

                document.querySelectorAll('.instrument-checkbox').forEach((checkbox) => {
                    checkbox.checked = Array.isArray(teacher.strumenti) && teacher.strumenti.includes(Number(checkbox.value));
                });

                teacherModal.show();
            } catch (error) {
                showToast(error.message, 'danger');
            }
        });
    });

    document.querySelectorAll('.btn-view-teacher').forEach((button) => {
        button.addEventListener('click', async () => {
            try {
                const teacher = await fetchTeacher(button.dataset.id);
                document.getElementById('teacher_detail_name').textContent = `${teacher.nome || ''} ${teacher.cognome || ''}`.trim() || '—';
                document.getElementById('teacher_detail_contacts').textContent = `${teacher.email || '—'}\n${teacher.telefono || '—'}`;
                document.getElementById('teacher_detail_tariffa').textContent = euroValue(teacher.tariffa_oraria || 0);
                document.getElementById('teacher_detail_strumenti').textContent = Array.isArray(teacher.strumenti_nomi) && teacher.strumenti_nomi.length > 0 ? teacher.strumenti_nomi.join(', ') : '—';
                document.getElementById('tariffa_insegnante_id').value = teacher.id || '';
                document.getElementById('tariffa_coppia').value = Number(teacher.tariffa_coppia || 0).toFixed(2);

                const tbody = document.getElementById('teacherUpcomingLessonsBody');
                tbody.innerHTML = '';
                if (!Array.isArray(teacher.upcoming_lessons) || teacher.upcoming_lessons.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="5" class="text-secondary text-center py-3">Nessuna lezione futura.</td></tr>';
                } else {
                    teacher.upcoming_lessons.forEach((lesson) => {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td>${escapeHtml((lesson.data || '').split('-').reverse().join('/'))}</td>
                            <td>${escapeHtml(String(lesson.ora_inizio || '').slice(0, 5))} - ${escapeHtml(String(lesson.ora_fine || '').slice(0, 5))}</td>
                            <td>${escapeHtml(`${lesson.nome || ''} ${lesson.cognome || ''}`.trim())}</td>
                            <td>${escapeHtml(lesson.strumento || '—')}</td>
                            <td>${renderStatusBadge(lesson.stato || '')}</td>
                        `;
                        tbody.appendChild(row);
                    });
                }

                // PDF export links
                const pdfFuturiT = document.getElementById('teacher_pdf_futuri');
                const pdfStoricoT = document.getElementById('teacher_pdf_storico');
                if (teacher.id) {
                    pdfFuturiT.href = `api/export-insegnante-pdf.php?id=${encodeURIComponent(teacher.id)}&tipo=futuri`;
                    pdfFuturiT.classList.remove('d-none');
                    pdfStoricoT.href = `api/export-insegnante-pdf.php?id=${encodeURIComponent(teacher.id)}&tipo=storico`;
                    pdfStoricoT.classList.remove('d-none');
                } else {
                    pdfFuturiT.classList.add('d-none');
                    pdfStoricoT.classList.add('d-none');
                }

                teacherDetailModal.show();
            } catch (error) {
                showToast(error.message, 'danger');
            }
        });
    });

    document.querySelectorAll('.btn-delete-teacher').forEach((button) => {
        button.addEventListener('click', async () => {
            const name = button.dataset.name || 'questo insegnante';
            if (!confirm(`Eliminare ${name}?`)) {
                return;
            }

            try {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('id', button.dataset.id);
                formData.append('csrf_token', getCsrfToken());

                const response = await fetch('insegnanti.php', { method: 'POST', body: formData });
                const data = await response.json();
                if (!data.success) {
                    throw new Error(data.message || 'Errore durante l\'eliminazione.');
                }

                showToast(data.message, 'success');
                window.location.reload();
            } catch (error) {
                showToast(error.message, 'danger');
            }
        });
    });

    ajaxForm(teacherForm, (data) => {
        showToast(data.message || 'Insegnante salvato.', 'success');
        window.location.reload();
    }, (message) => {
        showToast(message, 'danger');
    });

    ajaxForm(tariffaCoppiaForm, (data) => {
        showToast(data.message || 'Tariffa coppia aggiornata.', 'success');
    }, (message) => {
        showToast(message, 'danger');
    });

    teacherModalEl.addEventListener('hidden.bs.modal', resetTeacherForm);
});
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>

<?php
require_once __DIR__ . '/../includes/init.php';
requireRole(['superadmin']);

$db = getDB();
$pageTitle = 'Manage Barangays';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
        $name = trim($_POST['name'] ?? '');
        $code = strtoupper(trim($_POST['code'] ?? ''));
        if ($code === '' && $name !== '') {
            $code = barangayCodeFromName($name);
        }
        if ($name !== '') {
            try {
                $db->prepare('INSERT INTO barangay (name, code) VALUES (?, ?)')->execute([$name, $code ?: null]);
                setFlash('success', 'Barangay added.');
            } catch (PDOException $e) {
                setFlash('danger', 'Barangay name or code already exists.');
            }
        }
    } elseif ($action === 'update') {
        $id = (int) ($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $code = strtoupper(trim($_POST['code'] ?? ''));
        if ($code === '' && $name !== '') {
            $code = barangayCodeFromName($name);
        }
        if ($name !== '' && $id > 0) {
            try {
                $stmt = $db->prepare('UPDATE barangay SET name=?, code=? WHERE id=?');
                $stmt->execute([$name, $code ?: null, $id]);
                setFlash('success', 'Barangay updated.');
            } catch (PDOException $e) {
                setFlash('danger', 'Barangay name or code may already exist.');
            }
        }
    } elseif ($action === 'assign_nurse') {
        $barangayId = (int) ($_POST['barangay_id'] ?? 0);
        $nurseIds = array_map('intval', $_POST['nurses'] ?? []);

        if ($barangayId <= 0) {
            setFlash('danger', 'Invalid barangay selected.');
        } else {
            $stmt = $db->prepare('SELECT id, name FROM barangay WHERE id = ? AND is_active = 1');
            $stmt->execute([$barangayId]);
            $barangay = $stmt->fetch();
            if (!$barangay) {
                setFlash('danger', 'Barangay not found.');
            } else {
                $validNurseIds = [];
                if (!empty($nurseIds)) {
                    $placeholders = implode(',', array_fill(0, count($nurseIds), '?'));
                    $stmt = $db->prepare(
                        "SELECT id FROM users WHERE id IN ($placeholders) AND role = 'nurse' AND is_active = 1"
                    );
                    $stmt->execute($nurseIds);
                    $validNurseIds = array_map('intval', array_column($stmt->fetchAll(), 'id'));
                }

                $db->prepare('DELETE FROM user_barangay WHERE barangay_id = ?')->execute([$barangayId]);
                if (!empty($validNurseIds)) {
                    $ins = $db->prepare('INSERT INTO user_barangay (user_id, barangay_id) VALUES (?, ?)');
                    foreach ($validNurseIds as $nurseId) {
                        $ins->execute([$nurseId, $barangayId]);
                    }
                }

                logAudit($db, 'user_barangay', $barangayId, 'UPDATE', null, [
                    'barangay'   => $barangay['name'],
                    'nurse_ids'  => $validNurseIds,
                ]);
                setFlash('success', 'Nurse assignment updated.');
            }
        }
    } elseif ($action === 'deactivate') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) {
            setFlash('danger', 'Invalid barangay selected.');
        } else {
            $stmt = $db->prepare('SELECT id, name, is_active FROM barangay WHERE id = ?');
            $stmt->execute([$id]);
            $barangay = $stmt->fetch();
            if (!$barangay) {
                setFlash('danger', 'Barangay not found.');
            } elseif (!(int) $barangay['is_active']) {
                setFlash('info', 'Barangay is already inactive.');
            } else {
                $stmtNurses = $db->prepare(
                    'SELECT COUNT(*) FROM user_barangay ub
                     JOIN users u ON u.id = ub.user_id AND u.role = "nurse" AND u.is_active = 1
                     WHERE ub.barangay_id = ?'
                );
                $stmtNurses->execute([$id]);
                $nurseCount = (int) $stmtNurses->fetchColumn();

                $stmtPending = $db->prepare(
                    'SELECT COUNT(*) FROM report_submission WHERE barangay_id = ? AND status = "submitted"'
                );
                $stmtPending->execute([$id]);
                $pendingCount = (int) $stmtPending->fetchColumn();

                if ($nurseCount > 0) {
                    setFlash('danger', 'Cannot deactivate: nurses are still assigned to this barangay. Reassign them first.');
                } elseif ($pendingCount > 0) {
                    setFlash('danger', 'Cannot deactivate: this barangay has reports pending review.');
                } else {
                    $db->prepare('UPDATE barangay SET is_active = 0 WHERE id = ?')->execute([$id]);
                    logAudit($db, 'barangay', $id, 'UPDATE', null, ['is_active' => 0, 'name' => $barangay['name']]);
                    setFlash('success', 'Barangay deactivated.');
                }
            }
        }
    }
    redirect(roleUrl('superadmin', 'barangays.php'));
}

$barangays = $db->query(
    'SELECT b.*,
            COUNT(rs.id) AS total_reports,
            COALESCE(SUM(rs.status = "submitted"), 0) AS submitted_count,
            COALESCE(SUM(rs.status = "rejected"), 0) AS rejected_count,
            COALESCE(SUM(rs.status = "validated"), 0) AS validated_count
     FROM barangay b
     LEFT JOIN report_submission rs ON rs.barangay_id = b.id
     WHERE b.is_active = 1
     GROUP BY b.id
     ORDER BY b.name'
)->fetchAll();

$assignments = $db->query(
    'SELECT ub.barangay_id, u.id, u.full_name
     FROM user_barangay ub
     JOIN users u ON u.id = ub.user_id AND u.role = "nurse" AND u.is_active = 1
     ORDER BY u.full_name'
)->fetchAll();

$barangayNurses = [];
foreach ($assignments as $a) {
    $barangayNurses[$a['barangay_id']][] = $a;
}

$allNurses = $db->query(
    'SELECT id, full_name, username, position
     FROM users
     WHERE role = "nurse" AND is_active = 1
     ORDER BY full_name'
)->fetchAll();

require_once __DIR__ . '/../includes/header.php';

saPageOpen();
saBreadcrumb([
    ['label' => 'Dashboard', 'url' => roleUrl('superadmin', 'dashboard.php'), 'icon' => 'bi-house-door'],
    ['label' => 'Barangays'],
]);
saHeader('Manage Barangays', 'Manage barangays and view nurse assignments');
?>

<div class="sa-entity-toolbar">
    <div class="sa-entity-search">
        <i class="bi bi-search"></i>
        <input type="search" id="barangaySearch" class="form-control" placeholder="Search barangays..." autocomplete="off" aria-label="Search barangays">
    </div>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addBarangayModal">
        <i class="bi bi-plus-lg me-1"></i>Add Barangay
    </button>
</div>

<?php if (empty($barangays)): ?>
<div class="sa-entity-empty">
    <i class="bi bi-geo-alt"></i>
    <p class="mb-0">No active barangays yet. Add one to get started.</p>
</div>
<?php else: ?>
<div class="row g-3" id="barangayGrid">
    <?php foreach ($barangays as $b):
        $nurses = $barangayNurses[$b['id']] ?? [];
        $searchText = strtolower($b['name'] . ' ' . ($b['code'] ?? ''));
        $location = trim(($b['municipality'] ?? 'Cauayan') . ', ' . ($b['province'] ?? 'Isabela'), ', ');
        $nurseNames = array_map(static fn ($n) => $n['full_name'], $nurses);
        $nurseLabel = !empty($nurseNames) ? implode(', ', $nurseNames) : 'Not assigned';
        $assignedNurseIds = implode(',', array_map(static fn ($n) => (int) $n['id'], $nurses));
    ?>
    <div class="col-12 col-md-6 col-xl-4 barangay-col" data-search="<?= e($searchText) ?>">
        <article class="sa-entity-card">
            <div class="sa-entity-card__head">
                <div class="sa-entity-card__icon" aria-hidden="true">
                    <i class="bi bi-geo-alt-fill"></i>
                </div>
                <div class="sa-entity-card__title-wrap">
                    <h2 class="sa-entity-card__name"><?= e($b['name']) ?></h2>
                    <?php if (!empty($b['code'])): ?>
                    <p class="sa-entity-card__code"><?= e($b['code']) ?></p>
                    <?php endif; ?>
                </div>
                <div class="sa-entity-card__menu dropdown">
                    <button type="button" class="btn" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Barangay actions">
                        <i class="bi bi-three-dots-vertical"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <button type="button" class="dropdown-item edit-barangay-btn"
                                    data-id="<?= (int) $b['id'] ?>"
                                    data-name="<?= e($b['name']) ?>"
                                    data-code="<?= e($b['code'] ?? '') ?>">
                                <i class="bi bi-pencil me-2"></i>Edit Barangay
                            </button>
                        </li>
                        <li>
                            <button type="button" class="dropdown-item assign-nurse-btn"
                                    data-id="<?= (int) $b['id'] ?>"
                                    data-name="<?= e($b['name']) ?>"
                                    data-nurses="<?= e($assignedNurseIds) ?>">
                                <i class="bi bi-person-badge me-2"></i>Assign Nurse
                            </button>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <button type="button" class="dropdown-item text-danger deactivate-barangay-btn"
                                    data-id="<?= (int) $b['id'] ?>"
                                    data-name="<?= e($b['name']) ?>">
                                <i class="bi bi-trash me-2"></i>Deactivate
                            </button>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="sa-entity-card__body">
                <p class="sa-entity-card__type">Barangay · <?= e($location) ?></p>
                <span class="sa-entity-card__section-label">Assigned Nurse(s)</span>
                <p class="sa-entity-card__lead<?= empty($nurses) ? ' is-muted' : '' ?>"><?= e($nurseLabel) ?></p>
                <div class="sa-entity-card__stats">
                    <div class="sa-entity-stat sa-entity-stat--total">
                        <i class="bi bi-file-earmark-text"></i>
                        <span>Total: <?= (int) $b['total_reports'] ?></span>
                    </div>
                    <div class="sa-entity-stat sa-entity-stat--sent">
                        <i class="bi bi-send"></i>
                        <span>Submitted: <?= (int) $b['submitted_count'] ?></span>
                    </div>
                    <div class="sa-entity-stat sa-entity-stat--archived">
                        <i class="bi bi-x-circle"></i>
                        <span>Rejected: <?= (int) $b['rejected_count'] ?></span>
                    </div>
                    <div class="sa-entity-stat sa-entity-stat--received">
                        <i class="bi bi-check-circle"></i>
                        <span>Validated: <?= (int) $b['validated_count'] ?></span>
                    </div>
                </div>
            </div>
            <footer class="sa-entity-card__footer">
                <?php if (!empty($b['code'])): ?>
                Code prefix: <?= e($b['code']) ?>
                <?php else: ?>
                No report code prefix set
                <?php endif; ?>
            </footer>
        </article>
    </div>
    <?php endforeach; ?>
</div>
<div class="sa-entity-no-results" id="barangayNoResults">
    <i class="bi bi-search d-block mb-2" style="font-size:1.5rem;"></i>
    No barangays match your search.
</div>
<?php endif; ?>

<?php saPageClose(); ?>

<div class="sa-entity-modals">
<div class="modal fade" id="addBarangayModal" tabindex="-1" aria-labelledby="addBarangayModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="post" class="sa-modal-form">
                <div class="modal-header">
                    <div class="d-flex justify-content-between align-items-start w-100 gap-3">
                        <div>
                            <h5 class="modal-title" id="addBarangayModalLabel">Create New Barangay</h5>
                            <p class="modal-subtitle">Add a new barangay to the system.</p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                </div>
                <div class="modal-body modal-body--form">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="create">
                    <div class="mb-3">
                        <label class="form-label">Barangay Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Aggub" required autofocus>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Report Code Prefix</label>
                        <input type="text" name="code" class="form-control text-uppercase" maxlength="10"
                               placeholder="e.g. AGG" pattern="[A-Za-z]{2,10}" title="2–10 letters">
                        <div class="form-text">Used in report references (BRGY-PROGRAM-YYYYMM). Auto-generated from name if left blank.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-create">
                        <i class="bi bi-plus-lg me-1"></i>Create Barangay
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editBarangayModal" tabindex="-1" aria-labelledby="editBarangayModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="post" class="sa-modal-form">
                <div class="modal-header">
                    <div class="d-flex justify-content-between align-items-start w-100 gap-3">
                        <div>
                            <h5 class="modal-title" id="editBarangayModalLabel">Edit Barangay</h5>
                            <p class="modal-subtitle">Update barangay name and report code prefix.</p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                </div>
                <div class="modal-body modal-body--form">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" id="edit-id">
                    <div class="mb-3">
                        <label class="form-label">Barangay Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="edit-name" class="form-control" required>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Report Code Prefix</label>
                        <input type="text" name="code" id="edit-code" class="form-control text-uppercase" maxlength="10"
                               placeholder="e.g. AGG" pattern="[A-Za-z]{2,10}" title="2–10 letters">
                        <div class="form-text">Used in report references (BRGY-PROGRAM-YYYYMM).</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="deactivateBarangayModal" tabindex="-1" aria-labelledby="deactivateBarangayModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog--confirm">
        <div class="modal-content">
            <form method="post" id="deactivateBarangayForm">
                <div class="modal-body modal-confirm">
                    <div class="modal-confirm__icon" aria-hidden="true">
                        <i class="bi bi-trash"></i>
                    </div>
                    <h5 class="modal-confirm__title" id="deactivateBarangayModalLabel">Deactivate Barangay</h5>
                    <p class="modal-confirm__text">
                        Are you sure you want to deactivate
                        <span class="modal-confirm__name" id="deactivateBarangayName"></span>?
                        This barangay will be hidden from active lists.
                    </p>
                </div>
                <div class="modal-footer justify-content-end">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="deactivate">
                    <input type="hidden" name="id" id="deactivate-id" value="">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Deactivate</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="assignNurseModal" tabindex="-1" aria-labelledby="assignNurseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="post" class="sa-modal-form">
                <div class="modal-header">
                    <div class="d-flex justify-content-between align-items-start w-100 gap-3">
                        <div>
                            <h5 class="modal-title" id="assignNurseModalLabel">Assign Nurse</h5>
                            <p class="modal-subtitle">Set or change nurses assigned to this barangay.</p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                </div>
                <div class="modal-body modal-body--form">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="assign_nurse">
                    <input type="hidden" name="barangay_id" id="assign-barangay-id" value="">
                    <div class="mb-3">
                        <label class="form-label">Barangay</label>
                        <input type="text" id="assign-barangay-name" class="form-control" readonly>
                    </div>
                    <div class="mb-0">
                        <label class="form-label" for="assign-nurses">Assigned Nurse(s)</label>
                        <select name="nurses[]" id="assign-nurses" class="form-select" multiple size="<?= max(4, min(8, count($allNurses))) ?>">
                            <?php if (empty($allNurses)): ?>
                            <option value="" disabled>No active nurses in the system</option>
                            <?php else: ?>
                            <?php foreach ($allNurses as $nurse): ?>
                            <option value="<?= (int) $nurse['id'] ?>">
                                <?= e($nurse['full_name']) ?>
                                <?php if (!empty($nurse['position'])): ?>
                                — <?= e($nurse['position']) ?>
                                <?php endif; ?>
                                (<?= e($nurse['username']) ?>)
                            </option>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                        <div class="form-text">Hold Ctrl (Windows) or Cmd (Mac) to select multiple nurses. Leave empty to remove all assignments.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"<?= empty($allNurses) ? ' disabled' : '' ?>>Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var modalsHost = document.querySelector('.sa-entity-modals');
    if (modalsHost) {
        modalsHost.querySelectorAll('.modal').forEach(function (modalEl) {
            document.body.appendChild(modalEl);
        });
        modalsHost.remove();
    }

    function openEditModal(button) {
        if (!button) {
            return;
        }
        document.getElementById('edit-id').value = button.getAttribute('data-id') || '';
        document.getElementById('edit-name').value = button.getAttribute('data-name') || '';
        document.getElementById('edit-code').value = button.getAttribute('data-code') || '';
        bootstrap.Modal.getOrCreateInstance(document.getElementById('editBarangayModal')).show();
    }

    document.querySelectorAll('.edit-barangay-btn').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            openEditModal(btn);
        });
    });

    var deactivateModal = document.getElementById('deactivateBarangayModal');
    if (deactivateModal) {
        deactivateModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            if (!button || !button.classList.contains('deactivate-barangay-btn')) {
                return;
            }
            document.getElementById('deactivate-id').value = button.getAttribute('data-id') || '';
            document.getElementById('deactivateBarangayName').textContent = button.getAttribute('data-name') || 'this barangay';
        });
    }

    document.querySelectorAll('.deactivate-barangay-btn').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            document.getElementById('deactivate-id').value = btn.getAttribute('data-id') || '';
            document.getElementById('deactivateBarangayName').textContent = btn.getAttribute('data-name') || 'this barangay';
            bootstrap.Modal.getOrCreateInstance(deactivateModal).show();
        });
    });

    var assignNurseSelect = document.getElementById('assign-nurses');

    function openAssignNurseModal(button) {
        if (!button || !assignNurseSelect) {
            return;
        }
        document.getElementById('assign-barangay-id').value = button.getAttribute('data-id') || '';
        document.getElementById('assign-barangay-name').value = button.getAttribute('data-name') || '';
        var assigned = (button.getAttribute('data-nurses') || '').split(',').filter(Boolean).map(String);
        Array.from(assignNurseSelect.options).forEach(function (opt) {
            opt.selected = assigned.indexOf(String(opt.value)) !== -1;
        });
        bootstrap.Modal.getOrCreateInstance(document.getElementById('assignNurseModal')).show();
    }

    document.querySelectorAll('.assign-nurse-btn').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            openAssignNurseModal(btn);
        });
    });

    var searchInput = document.getElementById('barangaySearch');
    var grid = document.getElementById('barangayGrid');
    var noResults = document.getElementById('barangayNoResults');
    if (!searchInput || !grid) {
        return;
    }

    var cols = grid.querySelectorAll('.barangay-col');

    function filterBarangays() {
        var query = searchInput.value.trim().toLowerCase();
        var visible = 0;
        cols.forEach(function (col) {
            var haystack = col.getAttribute('data-search') || '';
            var match = query === '' || haystack.indexOf(query) !== -1;
            col.style.display = match ? '' : 'none';
            if (match) {
                visible++;
            }
        });
        if (noResults) {
            noResults.classList.toggle('is-visible', visible === 0);
        }
    }

    searchInput.addEventListener('input', filterBarangays);
});
</script>

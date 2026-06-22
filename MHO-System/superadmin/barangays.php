<?php
require_once __DIR__ . '/../includes/init.php';
requireRole(['superadmin']);

$db = getDB();
$pageTitle = 'Manage Barangays';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
        $name = trim($_POST['name'] ?? '');
        if ($name !== '') {
            try {
                $db->prepare('INSERT INTO barangay (name) VALUES (?)')->execute([$name]);
                setFlash('success', 'Barangay added.');
            } catch (PDOException $e) {
                setFlash('danger', 'Barangay name already exists.');
            }
        }
    } elseif ($action === 'update') {
        $id = (int) ($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $municipality = trim($_POST['municipality'] ?? '');
        $province = trim($_POST['province'] ?? '');
        if ($name !== '' && $id > 0) {
            try {
                $stmt = $db->prepare('UPDATE barangay SET name=?, municipality=?, province=? WHERE id=?');
                $stmt->execute([$name, $municipality, $province, $id]);
                setFlash('success', 'Barangay updated.');
            } catch (PDOException $e) {
                setFlash('danger', 'Barangay name may already exist.');
            }
        }
    }
    redirect(roleUrl('superadmin', 'barangays.php'));
}

$barangays = $db->query('SELECT * FROM barangay ORDER BY name')->fetchAll();

$assignments = $db->query(
    'SELECT ub.barangay_id, u.id, u.full_name
     FROM user_barangay ub
     JOIN users u ON u.id = ub.user_id AND u.role = "nurse"
     ORDER BY u.full_name'
)->fetchAll();

$barangayNurses = [];
foreach ($assignments as $a) {
    $barangayNurses[$a['barangay_id']][] = $a;
}

require_once __DIR__ . '/../includes/header.php';
?>
<div class="card shadow-sm">
    <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
        <span>Barangays (<?= count($barangays) ?>)</span>
        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addBarangayModal">
            <i class="bi bi-plus-lg me-1"></i> Add Barangay
        </button>
    </div>
    <div class="row g-3 p-3">
        <?php foreach ($barangays as $b):
            $nurses = $barangayNurses[$b['id']] ?? [];
        ?>
        <div class="col-12 col-sm-6 col-lg-4 col-xl-3">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body d-flex flex-column">
                    <h6 class="card-title fw-semibold mb-1"><?= e($b['name']) ?></h6>
                    <p class="card-text text-muted small mb-2">
                        <?= e($b['municipality']) ?><?= $b['municipality'] && $b['province'] ? ', ' : '' ?><?= e($b['province']) ?>
                    </p>

                    <?php if (!empty($nurses)): ?>
                    <div class="mb-2">
                        <small class="text-muted fw-semibold">Nurses:</small>
                        <ul class="list-unstyled mb-0 small">
                            <?php foreach ($nurses as $n): ?>
                            <li><i class="bi bi-person-badge me-1"></i><?= e($n['full_name']) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php else: ?>
                    <div class="mb-2">
                        <small class="text-muted fst-italic">No nurses assigned</small>
                    </div>
                    <?php endif; ?>

                    <div class="mt-auto">
                        <button type="button" class="btn btn-sm btn-outline-primary w-100"
                                data-bs-toggle="modal" data-bs-target="#editBarangayModal"
                                data-id="<?= (int)$b['id'] ?>"
                                data-name="<?= e($b['name']) ?>"
                                data-municipality="<?= e($b['municipality']) ?>"
                                data-province="<?= e($b['province']) ?>">
                            <i class="bi bi-pencil me-1"></i> Edit
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="modal fade" id="addBarangayModal" tabindex="-1" aria-labelledby="addBarangayModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header">
                    <h5 class="modal-title" id="addBarangayModalLabel">Add Barangay</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="create">
                    <div class="mb-0">
                        <label class="form-label">Barangay Name</label>
                        <input type="text" name="name" class="form-control" required autofocus>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm">Add</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editBarangayModal" tabindex="-1" aria-labelledby="editBarangayModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header">
                    <h5 class="modal-title" id="editBarangayModalLabel">Edit Barangay</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" id="edit-id">
                    <div class="mb-3">
                        <label class="form-label">Barangay Name</label>
                        <input type="text" name="name" id="edit-name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Municipality</label>
                        <input type="text" name="municipality" id="edit-municipality" class="form-control">
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Province</label>
                        <input type="text" name="province" id="edit-province" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var editModal = document.getElementById('editBarangayModal');
    if (editModal) {
        editModal.addEventListener('show.bs.modal', function(event) {
            var button = event.relatedTarget;
            document.getElementById('edit-id').value = button.getAttribute('data-id');
            document.getElementById('edit-name').value = button.getAttribute('data-name');
            document.getElementById('edit-municipality').value = button.getAttribute('data-municipality');
            document.getElementById('edit-province').value = button.getAttribute('data-province');
        });
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

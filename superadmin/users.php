<?php
require_once __DIR__ . '/../includes/init.php';
requireRole(['superadmin']);

$db = getDB();
$pageTitle = 'Manage Users';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
    $action = $_POST['action'] ?? '';

    if ($action === 'create' || $action === 'update') {
        $id       = (int) ($_POST['id'] ?? 0);
        $username = trim($_POST['username'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $fullName = trim($_POST['full_name'] ?? '');
        $role     = $_POST['role'] ?? 'nurse';
        $position = trim($_POST['position'] ?? '');
        $phone    = trim($_POST['phone'] ?? '');
        $active   = isset($_POST['is_active']) ? 1 : 0;
        $barangays = array_map('intval', $_POST['barangays'] ?? []);

        $validRoles = ['superadmin', 'admin', 'doctor', 'nurse'];
        if (!in_array($role, $validRoles, true) || $username === '' || $email === '' || $fullName === '') {
            setFlash('danger', 'Please fill in all required fields.');
        } else {
            try {
                if ($action === 'create') {
                    $password = $_POST['password'] ?? '';
                    if (strlen($password) < 8) {
                        setFlash('danger', 'Password must be at least 8 characters.');
                    } else {
                        $hash = password_hash($password, PASSWORD_BCRYPT);
                        $stmt = $db->prepare(
                            'INSERT INTO users (username, email, password_hash, role, full_name, position, phone, is_active)
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
                        );
                        $stmt->execute([$username, $email, $hash, $role, $fullName, $position, $phone, $active]);
                        $userId = (int) $db->lastInsertId();
                        logAudit($db, 'users', $userId, 'INSERT', null, ['username' => $username, 'role' => $role]);

                        $upload = saveProfileImage($userId, $_FILES['profile_image'] ?? []);
                        if ($upload['path']) {
                            $db->prepare('UPDATE users SET profile_image = ? WHERE id = ?')->execute([$upload['path'], $userId]);
                        }

                        if ($upload['error']) {
                            setFlash('warning', 'User created, but profile photo could not be saved: ' . $upload['error']);
                        } else {
                            setFlash('success', 'User created successfully.');
                        }
                    }
                } else {
                    if ($id <= 0) {
                        setFlash('danger', 'Invalid user selected for update.');
                    } else {
                    $stmt = $db->prepare(
                        'UPDATE users SET username=?, email=?, role=?, full_name=?, position=?, phone=?, is_active=?
                         WHERE id=?'
                    );
                    $stmt->execute([$username, $email, $role, $fullName, $position, $phone, $active, $id]);
                    if (!empty($_POST['password'])) {
                        $hash = password_hash($_POST['password'], PASSWORD_BCRYPT);
                        $db->prepare('UPDATE users SET password_hash=? WHERE id=?')->execute([$hash, $id]);
                    }

                    $currentImage = $db->prepare('SELECT profile_image FROM users WHERE id = ?');
                    $currentImage->execute([$id]);
                    $existingPath = $currentImage->fetchColumn() ?: null;
                    $imageWarning = null;

                    if (!empty($_POST['remove_profile_image'])) {
                        deleteProfileImage($existingPath);
                        $db->prepare('UPDATE users SET profile_image = NULL WHERE id = ?')->execute([$id]);
                    } elseif (!empty($_FILES['profile_image']['name'])) {
                        $upload = saveProfileImage($id, $_FILES['profile_image']);
                        if ($upload['path']) {
                            deleteProfileImage($existingPath);
                            $db->prepare('UPDATE users SET profile_image = ? WHERE id = ?')->execute([$upload['path'], $id]);
                        } elseif ($upload['error']) {
                            $imageWarning = $upload['error'];
                        }
                    }

                    $userId = $id;
                    logAudit($db, 'users', $id, 'UPDATE', null, ['username' => $username]);
                    if ($imageWarning) {
                        setFlash('warning', 'User updated, but profile photo could not be saved: ' . $imageWarning);
                    } else {
                        setFlash('success', 'User updated successfully.');
                    }
                    }
                }

                if (isset($userId) && $role === 'nurse') {
                    $db->prepare('DELETE FROM user_barangay WHERE user_id = ?')->execute([$userId]);
                    $ins = $db->prepare('INSERT INTO user_barangay (user_id, barangay_id) VALUES (?, ?)');
                    foreach ($barangays as $bid) {
                        $ins->execute([$userId, $bid]);
                    }
                } elseif (isset($userId)) {
                    $db->prepare('DELETE FROM user_barangay WHERE user_id = ?')->execute([$userId]);
                }
            } catch (PDOException $e) {
                setFlash('danger', 'Error: username or email may already exist.');
            }
        }
        redirect(roleUrl('superadmin', 'users.php'));
    }

    if ($action === 'deactivate') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id === (int) $_SESSION['user_id']) {
            setFlash('danger', 'Cannot deactivate your own account.');
        } else {
            $stmt = $db->prepare('SELECT role, is_active FROM users WHERE id = ?');
            $stmt->execute([$id]);
            $target = $stmt->fetch();
            if (!$target) {
                setFlash('danger', 'User not found.');
            } elseif ($target['role'] === 'superadmin') {
                setFlash('danger', 'Cannot deactivate a superadmin account.');
            } elseif (!(int) $target['is_active']) {
                setFlash('info', 'User is already inactive.');
            } else {
                $db->prepare('UPDATE users SET is_active = 0, is_logged_in = 0 WHERE id = ?')->execute([$id]);
                logAudit($db, 'users', $id, 'UPDATE', null, ['is_active' => 0]);
                setFlash('success', 'User deactivated.');
            }
        }
        redirect(roleUrl('superadmin', 'users.php'));
    }
}

$users = $db->query(
    'SELECT u.*, GROUP_CONCAT(ub.barangay_id) AS barangay_ids
     FROM users u
     LEFT JOIN user_barangay ub ON ub.user_id = u.id
     GROUP BY u.id ORDER BY u.role, u.full_name'
)->fetchAll();

$barangays = $db->query('SELECT id, name FROM barangay WHERE is_active = 1 ORDER BY name')->fetchAll();
$barangayNames = [];
foreach ($barangays as $b) {
    $barangayNames[(int) $b['id']] = $b['name'];
}

$roleIcons = [
    'superadmin' => 'bi-shield-lock-fill',
    'admin'      => 'bi-person-gear',
    'doctor'     => 'bi-heart-pulse-fill',
    'nurse'      => 'bi-person-badge-fill',
];

require_once __DIR__ . '/../includes/header.php';

saPageOpen();
saBreadcrumb([
    ['label' => 'Dashboard', 'url' => roleUrl('superadmin', 'dashboard.php'), 'icon' => 'bi-house-door'],
    ['label' => 'Users'],
]);
saHeader('Manage Users', 'Create and manage system user accounts and barangay assignments');

saPanelOpen('All Users', [
    'count' => count($users),
    'actions' => '<button type="button" class="btn btn-sm btn-primary" id="addUserBtn" data-bs-toggle="modal" data-bs-target="#userModal"><i class="bi bi-plus-lg me-1"></i>Add User</button>',
]);
?>
<div class="row g-3 p-3">
    <?php foreach ($users as $u):
        $assignedBrgys = [];
        if (!empty($u['barangay_ids'])) {
            foreach (explode(',', $u['barangay_ids']) as $bid) {
                $bid = (int) $bid;
                if (isset($barangayNames[$bid])) {
                    $assignedBrgys[] = $barangayNames[$bid];
                }
            }
        }
        $roleIcon = $roleIcons[$u['role']] ?? 'bi-person-fill';
        $profileUrl = profileImageUrl($u['profile_image'] ?? null);
    ?>
    <div class="col-12 col-sm-6 col-lg-4 col-xl-3">
        <div class="sa-user-card h-100">
            <div class="sa-user-card__top">
                <div class="sa-user-card__avatar<?= $profileUrl ? ' sa-user-card__avatar--has-image' : '' ?>">
                    <?php if ($profileUrl): ?>
                    <img src="<?= e($profileUrl) ?>" alt="<?= e($u['full_name']) ?>">
                    <?php else: ?>
                    <i class="bi <?= e($roleIcon) ?>"></i>
                    <?php endif; ?>
                </div>
                <div class="sa-user-card__status <?= $u['is_active'] ? 'is-active' : 'is-inactive' ?>">
                    <?= $u['is_active'] ? 'Active' : 'Inactive' ?>
                </div>
            </div>
            <div class="sa-user-card__body">
                <h6 class="sa-user-card__name"><?= e($u['full_name']) ?></h6>
                <?php if (!empty($u['position'])): ?>
                <p class="sa-user-card__position"><?= e($u['position']) ?></p>
                <?php endif; ?>
                <div class="sa-user-card__meta">
                    <span><i class="bi bi-at"></i><?= e($u['username']) ?></span>
                    <span><i class="bi bi-envelope"></i><?= e($u['email']) ?></span>
                </div>
                <span class="badge bg-primary badge-role text-capitalize"><?= e($u['role']) ?></span>
                <?php if ($u['role'] === 'nurse'): ?>
                <div class="sa-user-card__brgys">
                    <small class="sa-user-card__brgys-label">Assigned Barangays</small>
                    <?php if (!empty($assignedBrgys)): ?>
                    <ul class="list-unstyled mb-0 small">
                        <?php foreach ($assignedBrgys as $brgy): ?>
                        <li><i class="bi bi-geo-alt me-1"></i><?= e($brgy) ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <?php else: ?>
                    <small class="text-muted fst-italic">None assigned</small>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            <div class="sa-user-card__actions">
                <button type="button"
                        class="btn btn-sm btn-outline-primary flex-fill edit-user-btn"
                        data-bs-toggle="modal"
                        data-bs-target="#userModal"
                        data-id="<?= (int) $u['id'] ?>"
                        data-username="<?= e($u['username']) ?>"
                        data-email="<?= e($u['email']) ?>"
                        data-full-name="<?= e($u['full_name']) ?>"
                        data-role="<?= e($u['role']) ?>"
                        data-position="<?= e($u['position'] ?? '') ?>"
                        data-phone="<?= e($u['phone'] ?? '') ?>"
                        data-active="<?= (int) $u['is_active'] ?>"
                        data-barangays="<?= e($u['barangay_ids'] ?? '') ?>"
                        data-profile-image="<?= e($profileUrl ?? '') ?>">
                    <i class="bi bi-pencil me-1"></i>Edit
                </button>
                <?php if ((int) $u['id'] !== (int) $_SESSION['user_id'] && $u['role'] !== 'superadmin' && $u['is_active']): ?>
                <form method="post" class="flex-fill" onsubmit="return confirm('Deactivate this user?')">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="deactivate">
                    <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-outline-warning w-100">
                        <i class="bi bi-person-x me-1"></i>Deactivate
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php saPanelClose(); saPageClose(); ?>

<div class="modal fade" id="userModal" tabindex="-1" aria-labelledby="userModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="userModalLabel">Add User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" id="userForm" class="sa-user-modal-form" enctype="multipart/form-data">
                <div class="modal-body">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" id="userFormAction" value="create">
                    <input type="hidden" name="id" id="userFormId" value="">
                    <div class="row g-2">
                        <div class="col-md-6 mb-2">
                            <label class="form-label small">Username *</label>
                            <input type="text" name="username" id="userUsername" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-6 mb-2">
                            <label class="form-label small">Email *</label>
                            <input type="email" name="email" id="userEmail" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-6 mb-2">
                            <label class="form-label small">Full Name *</label>
                            <input type="text" name="full_name" id="userFullName" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-6 mb-2">
                            <label class="form-label small">Role *</label>
                            <select name="role" class="form-select form-select-sm" id="roleSelect">
                                <?php foreach (['nurse', 'doctor', 'admin', 'superadmin'] as $r): ?>
                                <option value="<?= $r ?>"><?= ucfirst($r) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-2">
                            <label class="form-label small">Position</label>
                            <input type="text" name="position" id="userPosition" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-6 mb-2">
                            <label class="form-label small">Phone</label>
                            <input type="text" name="phone" id="userPhone" class="form-control form-control-sm">
                        </div>
                        <div class="col-12 mb-2">
                            <label class="form-label small">Profile Photo</label>
                            <div id="profileImagePreview" class="sa-profile-preview mb-2" hidden>
                                <img id="profileImagePreviewImg" src="" alt="Profile preview">
                            </div>
                            <input type="file" name="profile_image" id="userProfileImage" class="form-control form-control-sm" accept="image/jpeg,image/png,image/gif,image/webp">
                            <div class="form-text">JPEG, PNG, GIF, or WebP. Max 2 MB.</div>
                            <div class="form-check mt-2" id="removeProfileImageWrap" hidden>
                                <input type="checkbox" name="remove_profile_image" class="form-check-input" id="removeProfileImage" value="1">
                                <label class="form-check-label small" for="removeProfileImage">Remove current photo</label>
                            </div>
                        </div>
                        <div class="col-12 mb-2">
                            <label class="form-label small" id="passwordLabel">Password *</label>
                            <input type="password" name="password" id="userPassword" class="form-control form-control-sm" required minlength="8">
                        </div>
                        <div class="col-12 mb-2" id="barangaySection">
                            <label class="form-label small">Assigned Barangays (Nurse)</label>
                            <select name="barangays[]" id="userBarangays" class="form-select form-select-sm" multiple size="5">
                                <?php foreach ($barangays as $b): ?>
                                <option value="<?= (int) $b['id'] ?>"><?= e($b['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input type="checkbox" name="is_active" class="form-check-input" id="isActive" checked>
                                <label class="form-check-label small" for="isActive">Active</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm" id="userFormSubmit">Create User</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var userModal = document.getElementById('userModal');
    var userForm = document.getElementById('userForm');
    var roleSelect = document.getElementById('roleSelect');
    var barangaySection = document.getElementById('barangaySection');
    var barangaySelect = document.getElementById('userBarangays');
    var passwordInput = document.getElementById('userPassword');
    var passwordLabel = document.getElementById('passwordLabel');
    var profileImageInput = document.getElementById('userProfileImage');
    var profileImagePreview = document.getElementById('profileImagePreview');
    var profileImagePreviewImg = document.getElementById('profileImagePreviewImg');
    var removeProfileImageWrap = document.getElementById('removeProfileImageWrap');
    var removeProfileImage = document.getElementById('removeProfileImage');

    function toggleBarangaySection() {
        barangaySection.style.display = roleSelect.value === 'nurse' ? 'block' : 'none';
    }

    function setProfilePreview(url) {
        if (url) {
            profileImagePreviewImg.src = url;
            profileImagePreview.hidden = false;
        } else {
            profileImagePreviewImg.removeAttribute('src');
            profileImagePreview.hidden = true;
        }
    }

    function resetUserForm() {
        userForm.reset();
        document.getElementById('userFormAction').value = 'create';
        document.getElementById('userFormId').value = '';
        document.getElementById('userModalLabel').textContent = 'Add User';
        document.getElementById('userFormSubmit').textContent = 'Create User';
        passwordLabel.textContent = 'Password *';
        passwordInput.required = true;
        passwordInput.minLength = 8;
        document.getElementById('isActive').checked = true;
        Array.from(barangaySelect.options).forEach(function (opt) { opt.selected = false; });
        profileImageInput.value = '';
        setProfilePreview('');
        removeProfileImageWrap.hidden = true;
        removeProfileImage.checked = false;
        toggleBarangaySection();
    }

    function populateUserForm(button) {
        document.getElementById('userFormAction').value = 'update';
        document.getElementById('userFormId').value = button.getAttribute('data-id') || '';
        document.getElementById('userUsername').value = button.getAttribute('data-username') || '';
        document.getElementById('userEmail').value = button.getAttribute('data-email') || '';
        document.getElementById('userFullName').value = button.getAttribute('data-full-name') || '';
        roleSelect.value = button.getAttribute('data-role') || 'nurse';
        document.getElementById('userPosition').value = button.getAttribute('data-position') || '';
        document.getElementById('userPhone').value = button.getAttribute('data-phone') || '';
        document.getElementById('isActive').checked = button.getAttribute('data-active') === '1';
        passwordInput.value = '';
        passwordLabel.textContent = 'New Password (leave blank to keep)';
        passwordInput.required = false;
        passwordInput.removeAttribute('minlength');
        document.getElementById('userModalLabel').textContent = 'Edit User';
        document.getElementById('userFormSubmit').textContent = 'Update User';
        profileImageInput.value = '';
        removeProfileImage.checked = false;

        var existingImage = button.getAttribute('data-profile-image') || '';
        setProfilePreview(existingImage);
        removeProfileImageWrap.hidden = !existingImage;

        var assigned = (button.getAttribute('data-barangays') || '').split(',').filter(Boolean).map(String);
        Array.from(barangaySelect.options).forEach(function (opt) {
            opt.selected = assigned.indexOf(String(opt.value)) !== -1;
        });
        toggleBarangaySection();
    }

    profileImageInput.addEventListener('change', function () {
        if (profileImageInput.files && profileImageInput.files[0]) {
            setProfilePreview(URL.createObjectURL(profileImageInput.files[0]));
            removeProfileImage.checked = false;
        }
    });

    removeProfileImage.addEventListener('change', function () {
        if (removeProfileImage.checked) {
            profileImageInput.value = '';
            setProfilePreview('');
        }
    });

    roleSelect.addEventListener('change', toggleBarangaySection);

    userModal.addEventListener('show.bs.modal', function (event) {
        var trigger = event.relatedTarget;
        if (trigger && trigger.classList.contains('edit-user-btn')) {
            populateUserForm(trigger);
        } else {
            resetUserForm();
        }
    });

    toggleBarangaySection();
});
</script>

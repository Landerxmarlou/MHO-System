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

function userInitials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name), -1, PREG_SPLIT_NO_EMPTY);
    if ($parts === false || $parts === []) {
        return '?';
    }
    if (count($parts) === 1) {
        return strtoupper(substr($parts[0], 0, 2));
    }
    return strtoupper(substr($parts[0], 0, 1) . substr($parts[count($parts) - 1], 0, 1));
}

function userAvatarTone(int $id): int
{
    return $id % 6;
}

function roleDisplayLabel(string $role): string
{
    return match ($role) {
        'superadmin' => 'Super Admin',
        'admin'      => 'Admin',
        'doctor'     => 'Doctor',
        'nurse'      => 'Nurse',
        default      => ucfirst($role),
    };
}

$totalUsers = count($users);
$activeCount = count(array_filter($users, static fn ($u) => (int) $u['is_active']));
$inactiveCount = $totalUsers - $activeCount;

require_once __DIR__ . '/../includes/header.php';

saPageOpen();
saBreadcrumb([
    ['label' => 'Dashboard', 'url' => roleUrl('superadmin', 'dashboard.php'), 'icon' => 'bi-house-door'],
    ['label' => 'Users'],
]);
saHeader('User Management', 'Manage user accounts and roles');
?>

<div class="sa-user-mgmt-toolbar sa-entity-toolbar">
    <div class="sa-entity-search">
        <i class="bi bi-search"></i>
        <input type="search" id="userSearch" class="form-control" placeholder="Search by name or email" autocomplete="off" aria-label="Search users">
    </div>
    <select id="userRoleFilter" class="form-select sa-user-mgmt-toolbar__role" aria-label="Filter by role">
        <option value="">All Roles</option>
        <option value="nurse">Nurse</option>
        <option value="doctor">Doctor</option>
        <option value="admin">Admin</option>
        <option value="superadmin">Superadmin</option>
    </select>
    <button type="button" class="btn btn-primary" id="addUserBtn">
        <i class="bi bi-plus-lg me-1"></i>Add User
    </button>
</div>

<div class="sa-filter-pills" role="group" aria-label="Filter by status">
    <span class="sa-filter-pills__label">Status</span>
    <button type="button" class="sa-filter-pill is-active" data-filter-status="all">All</button>
    <button type="button" class="sa-filter-pill" data-filter-status="active">Active</button>
    <button type="button" class="sa-filter-pill" data-filter-status="inactive">Inactive</button>
</div>

<div class="sa-filter-pills" role="group" aria-label="Filter by barangay">
    <span class="sa-filter-pills__label">Barangay</span>
    <button type="button" class="sa-filter-pill is-active" data-filter-barangay="all">All Barangays</button>
    <?php foreach ($barangays as $b): ?>
    <button type="button" class="sa-filter-pill" data-filter-barangay="<?= (int) $b['id'] ?>"><?= e($b['name']) ?></button>
    <?php endforeach; ?>
    <button type="button" class="sa-filter-pill" data-filter-barangay="unassigned">Unassigned</button>
</div>

<p class="sa-user-mgmt-summary" id="userSummary">
    <?= (int) $totalUsers ?> total users &bull; <?= (int) $activeCount ?> active &bull; <?= (int) $inactiveCount ?> inactive
</p>

<?php if (empty($users)): ?>
<div class="sa-entity-empty">
    <i class="bi bi-people"></i>
    <p class="mb-0">No users yet. Add one to get started.</p>
</div>
<?php else: ?>
<div class="row g-3" id="userGrid">
    <?php foreach ($users as $u):
        $assignedBrgyIds = [];
        $assignedBrgys = [];
        if (!empty($u['barangay_ids'])) {
            foreach (explode(',', $u['barangay_ids']) as $bid) {
                $bid = (int) $bid;
                if ($bid > 0) {
                    $assignedBrgyIds[] = $bid;
                    if (isset($barangayNames[$bid])) {
                        $assignedBrgys[] = $barangayNames[$bid];
                    }
                }
            }
        }
        $profileUrl = profileImageUrl($u['profile_image'] ?? null);
        $searchText = strtolower($u['full_name'] . ' ' . $u['email'] . ' ' . $u['username']);
        $barangayData = $u['role'] === 'nurse' ? implode(',', $assignedBrgyIds) : '';
        $canDeactivate = (int) $u['id'] !== (int) $_SESSION['user_id']
            && $u['role'] !== 'superadmin'
            && (int) $u['is_active'];
        $avatarTone = userAvatarTone((int) $u['id']);
    ?>
    <div class="col-12 col-md-6 col-xl-4 user-col"
         data-search="<?= e($searchText) ?>"
         data-role="<?= e($u['role']) ?>"
         data-status="<?= (int) $u['is_active'] ? 'active' : 'inactive' ?>"
         data-barangays="<?= e($barangayData) ?>">
        <article class="sa-user-mgmt-card">
            <div class="sa-user-mgmt-card__head">
                <div class="sa-user-mgmt-card__avatar sa-user-mgmt-card__avatar--c<?= $avatarTone ?><?= $profileUrl ? ' sa-user-mgmt-card__avatar--has-image' : '' ?>">
                    <?php if ($profileUrl): ?>
                    <img src="<?= e($profileUrl) ?>" alt="<?= e($u['full_name']) ?>">
                    <?php else: ?>
                    <?= e(userInitials($u['full_name'])) ?>
                    <?php endif; ?>
                </div>
                <div class="sa-user-mgmt-card__identity">
                    <h2 class="sa-user-mgmt-card__name"><?= e($u['full_name']) ?></h2>
                    <p class="sa-user-mgmt-card__email"><?= e($u['email']) ?></p>
                </div>
                <span class="sa-user-mgmt-card__status <?= (int) $u['is_active'] ? 'is-active' : 'is-inactive' ?>">
                    <?= (int) $u['is_active'] ? 'Active' : 'Inactive' ?>
                </span>
            </div>
            <div class="sa-user-mgmt-card__tags">
                <span class="sa-user-mgmt-card__tag sa-user-mgmt-card__tag--role-<?= e($u['role']) ?>">
                    <?= e(roleDisplayLabel($u['role'])) ?>
                </span>
                <?php if ($u['role'] === 'nurse'): ?>
                    <?php if (!empty($assignedBrgys)): ?>
                        <?php foreach ($assignedBrgys as $brgy): ?>
                        <span class="sa-user-mgmt-card__tag sa-user-mgmt-card__tag--barangay"><?= e($brgy) ?></span>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <span class="sa-user-mgmt-card__tag sa-user-mgmt-card__tag--muted">Unassigned</span>
                    <?php endif; ?>
                <?php elseif (!empty($u['position'])): ?>
                <span class="sa-user-mgmt-card__tag sa-user-mgmt-card__tag--barangay"><?= e($u['position']) ?></span>
                <?php endif; ?>
            </div>
            <div class="sa-user-mgmt-card__actions">
                <button type="button"
                        class="btn btn-outline-primary btn-edit edit-user-btn"
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
                <?php if ($canDeactivate): ?>
                <button type="button"
                        class="btn btn-outline-danger btn-disable deactivate-user-btn"
                        data-id="<?= (int) $u['id'] ?>"
                        data-name="<?= e($u['full_name']) ?>">
                    <i class="bi bi-slash-circle me-1"></i>Disable
                </button>
                <?php endif; ?>
            </div>
        </article>
    </div>
    <?php endforeach; ?>
</div>
<div class="sa-entity-no-results" id="userNoResults">
    <i class="bi bi-search d-block mb-2" style="font-size:1.5rem;"></i>
    No users match your filters.
</div>
<?php endif; ?>

<?php saPageClose(); ?>

<div class="sa-entity-modals">
<div class="modal fade" id="userModal" tabindex="-1" aria-labelledby="userModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable modal-dialog--wide">
        <div class="modal-content">
            <form method="post" id="userForm" class="sa-user-modal-form sa-modal-form" enctype="multipart/form-data">
                <div class="modal-header">
                    <div class="d-flex justify-content-between align-items-start w-100 gap-3">
                        <div>
                            <h5 class="modal-title" id="userModalLabel">Add User</h5>
                            <p class="modal-subtitle" id="userModalSubtitle">Add a new user to the system.</p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                </div>
                <div class="modal-body modal-body--form">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" id="userFormAction" value="create">
                    <input type="hidden" name="id" id="userFormId" value="">
                    <div class="row g-2">
                        <div class="col-md-6 mb-2">
                            <label class="form-label small">Username <span class="text-danger">*</span></label>
                            <input type="text" name="username" id="userUsername" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-6 mb-2">
                            <label class="form-label small">Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" id="userEmail" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-6 mb-2">
                            <label class="form-label small">Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="full_name" id="userFullName" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-6 mb-2">
                            <label class="form-label small">Role <span class="text-danger">*</span></label>
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
                            <label class="form-label small" id="passwordLabel">Password <span class="text-danger">*</span></label>
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
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-create" id="userFormSubmit">
                        <i class="bi bi-plus-lg me-1"></i>Create User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="deactivateUserModal" tabindex="-1" aria-labelledby="deactivateUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog--confirm">
        <div class="modal-content">
            <form method="post" id="deactivateUserForm">
                <div class="modal-body modal-confirm">
                    <div class="modal-confirm__icon" aria-hidden="true">
                        <i class="bi bi-person-x"></i>
                    </div>
                    <h5 class="modal-confirm__title" id="deactivateUserModalLabel">Disable User</h5>
                    <p class="modal-confirm__text">
                        Are you sure you want to disable
                        <span class="modal-confirm__name" id="deactivateUserName"></span>?
                        They will no longer be able to sign in.
                    </p>
                </div>
                <div class="modal-footer justify-content-end">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="deactivate">
                    <input type="hidden" name="id" id="deactivate-user-id" value="">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Disable</button>
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
    var userModalSubtitle = document.getElementById('userModalSubtitle');
    var userFormSubmit = document.getElementById('userFormSubmit');
    var addUserBtn = document.getElementById('addUserBtn');
    var deactivateUserModal = document.getElementById('deactivateUserModal');

    var activeStatusFilter = 'all';
    var activeBarangayFilter = 'all';

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
        userModalSubtitle.textContent = 'Add a new user to the system.';
        userFormSubmit.innerHTML = '<i class="bi bi-plus-lg me-1"></i>Create User';
        passwordLabel.innerHTML = 'Password <span class="text-danger">*</span>';
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
        userModalSubtitle.textContent = 'Update user account and barangay assignments.';
        userFormSubmit.textContent = 'Save Changes';
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

    if (addUserBtn) {
        addUserBtn.addEventListener('click', function () {
            resetUserForm();
            bootstrap.Modal.getOrCreateInstance(userModal).show();
        });
    }

    document.querySelectorAll('.edit-user-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            populateUserForm(btn);
            bootstrap.Modal.getOrCreateInstance(userModal).show();
        });
    });

    document.querySelectorAll('.deactivate-user-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.getElementById('deactivate-user-id').value = btn.getAttribute('data-id') || '';
            document.getElementById('deactivateUserName').textContent = btn.getAttribute('data-name') || 'this user';
            bootstrap.Modal.getOrCreateInstance(deactivateUserModal).show();
        });
    });

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
    toggleBarangaySection();

    var searchInput = document.getElementById('userSearch');
    var roleFilter = document.getElementById('userRoleFilter');
    var grid = document.getElementById('userGrid');
    var noResults = document.getElementById('userNoResults');
    var statusPills = document.querySelectorAll('[data-filter-status]');
    var barangayPills = document.querySelectorAll('[data-filter-barangay]');

    function matchesBarangayFilter(col) {
        if (activeBarangayFilter === 'all') {
            return true;
        }
        var role = col.getAttribute('data-role') || '';
        var brgys = (col.getAttribute('data-barangays') || '').split(',').filter(Boolean);
        if (activeBarangayFilter === 'unassigned') {
            return role === 'nurse' && brgys.length === 0;
        }
        if (role !== 'nurse') {
            return false;
        }
        return brgys.indexOf(String(activeBarangayFilter)) !== -1;
    }

    function filterUsers() {
        if (!grid) {
            return;
        }
        var query = (searchInput ? searchInput.value : '').trim().toLowerCase();
        var role = roleFilter ? roleFilter.value : '';
        var cols = grid.querySelectorAll('.user-col');
        var visible = 0;

        cols.forEach(function (col) {
            var haystack = col.getAttribute('data-search') || '';
            var matchSearch = query === '' || haystack.indexOf(query) !== -1;
            var matchRole = role === '' || col.getAttribute('data-role') === role;
            var matchStatus = activeStatusFilter === 'all' || col.getAttribute('data-status') === activeStatusFilter;
            var matchBarangay = matchesBarangayFilter(col);
            var match = matchSearch && matchRole && matchStatus && matchBarangay;
            col.style.display = match ? '' : 'none';
            if (match) {
                visible++;
            }
        });

        if (noResults) {
            noResults.classList.toggle('is-visible', visible === 0);
        }
    }

    if (searchInput) {
        searchInput.addEventListener('input', filterUsers);
    }
    if (roleFilter) {
        roleFilter.addEventListener('change', filterUsers);
    }

    statusPills.forEach(function (pill) {
        pill.addEventListener('click', function () {
            statusPills.forEach(function (p) { p.classList.remove('is-active'); });
            pill.classList.add('is-active');
            activeStatusFilter = pill.getAttribute('data-filter-status') || 'all';
            filterUsers();
        });
    });

    barangayPills.forEach(function (pill) {
        pill.addEventListener('click', function () {
            barangayPills.forEach(function (p) { p.classList.remove('is-active'); });
            pill.classList.add('is-active');
            activeBarangayFilter = pill.getAttribute('data-filter-barangay') || 'all';
            filterUsers();
        });
    });
});
</script>

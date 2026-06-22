<?php
require_once __DIR__ . '/../includes/init.php';
requireRole(['superadmin']);

$db = getDB();
$pageTitle = 'Health Programs';

$selectedProgramId = (int) ($_GET['program'] ?? 0);

$programIcons = [
    'CHILD'      => 'bi-bandaid',
    'MATERNAL'   => 'bi-heart-pulse',
    'NATA'       => 'bi-clipboard2-pulse',
    'FP'         => 'bi-people',
    'INFECTIOUS' => 'bi-virus',
    'NCD'        => 'bi-lungs',
];

function programShortName(string $name): string
{
    $short = preg_replace('/\s*-\s*.*/', '', $name);
    return $short !== '' ? $short : $name;
}

function sexCountLabel(string $sex): string
{
    return $sex === 'MFT' ? 'By sex' : 'Total only';
}

function ageCountLabel(string $age): string
{
    return ($age === '' || $age === 'NONE') ? 'All ages' : 'By age';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
    $action    = $_POST['action'] ?? '';
    $programId = (int) ($_POST['program_id'] ?? 0);

    if ($action === 'create' || $action === 'update') {
        $id          = (int) ($_POST['id'] ?? 0);
        $code        = trim($_POST['code'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $part        = trim($_POST['part'] ?? '');
        $category    = trim($_POST['category'] ?? '');
        $sex         = $_POST['sex_disaggregation'] ?? 'NONE';
        $age         = trim($_POST['age_disaggregation'] ?? 'NONE');
        $sortOrder   = (int) ($_POST['sort_order'] ?? 0);

        $validSex = ['MFT', 'NONE'];
        if ($code === '' || $description === '' || !in_array($sex, $validSex, true)) {
            setFlash('danger', 'Please fill in the record code, name, and how counts are grouped.');
        } else {
            try {
                if ($sortOrder <= 0) {
                    $stmt = $db->prepare('SELECT COALESCE(MAX(sort_order), 0) + 10 FROM indicator WHERE program_id = ?');
                    $stmt->execute([$programId]);
                    $sortOrder = (int) $stmt->fetchColumn();
                }

                if ($action === 'create') {
                    $stmt = $db->prepare(
                        'INSERT INTO indicator (program_id, code, part, category, description, sex_disaggregation, age_disaggregation, sort_order, is_active)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)'
                    );
                    $stmt->execute([$programId, $code, $part ?: null, $category ?: null, $description, $sex, $age ?: 'NONE', $sortOrder]);
                    $indicatorId = (int) $db->lastInsertId();
                    logAudit($db, 'indicator', $indicatorId, 'INSERT', null, ['code' => $code, 'description' => $description]);
                    setFlash('success', 'Record added successfully.');
                } else {
                    $stmt = $db->prepare(
                        'UPDATE indicator SET code=?, part=?, category=?, description=?, sex_disaggregation=?, age_disaggregation=?, sort_order=?
                         WHERE id=? AND program_id=?'
                    );
                    $stmt->execute([$code, $part ?: null, $category ?: null, $description, $sex, $age ?: 'NONE', $sortOrder, $id, $programId]);
                    logAudit($db, 'indicator', $id, 'UPDATE', null, ['code' => $code, 'description' => $description]);
                    setFlash('success', 'Record updated successfully.');
                }
            } catch (PDOException $e) {
                setFlash('danger', 'That record code already exists in this program.');
            }
        }
    }

    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        $db->prepare('UPDATE indicator SET is_active = 0 WHERE id = ? AND program_id = ?')->execute([$id, $programId]);
        logAudit($db, 'indicator', $id, 'DELETE', null, null);
        setFlash('success', 'Record removed.');
    }

    redirect(roleUrl('superadmin', 'programs.php' . ($programId ? '?program=' . $programId : '')));
}

$programs = $db->query(
    'SELECT hp.*, COUNT(i.id) AS indicator_count
     FROM health_program hp
     LEFT JOIN indicator i ON i.program_id = hp.id AND i.is_active = 1
     GROUP BY hp.id ORDER BY hp.id'
)->fetchAll();

$selectedProgram = null;
$indicators = [];
$editIndicator = null;

if ($selectedProgramId > 0) {
    $stmt = $db->prepare('SELECT * FROM health_program WHERE id = ?');
    $stmt->execute([$selectedProgramId]);
    $selectedProgram = $stmt->fetch();
    if ($selectedProgram) {
        $stmt = $db->prepare(
            'SELECT id, code, part, category, description, sex_disaggregation, age_disaggregation, sort_order
             FROM indicator WHERE program_id = ? AND is_active = 1 ORDER BY sort_order, id'
        );
        $stmt->execute([$selectedProgramId]);
        $indicators = $stmt->fetchAll();

        if (isset($_GET['edit'])) {
            $stmt = $db->prepare('SELECT * FROM indicator WHERE id = ? AND program_id = ? AND is_active = 1');
            $stmt->execute([(int) $_GET['edit'], $selectedProgramId]);
            $editIndicator = $stmt->fetch();
        }
    }
}

require_once __DIR__ . '/../includes/header.php';

saPageOpen('sa-programs-page');
saBreadcrumb([
    ['label' => 'Dashboard', 'url' => roleUrl('superadmin', 'dashboard.php'), 'icon' => 'bi-house-door'],
    ['label' => 'Programs'],
]);
saHeader(
    'Health Programs',
    'Pick a program on the left, then add or update the health records nurses fill out each month.'
);
?>
<div class="row g-3 sa-programs-layout">
    <div class="col-lg-3">
        <div class="sa-panel sa-programs-nav-panel">
            <div class="sa-panel__header">
                <span class="sa-panel__title"><i class="bi bi-journal-medical me-1"></i> Programs</span>
            </div>
            <div class="sa-panel__body sa-programs-nav">
                <?php foreach ($programs as $p):
                    $icon = $programIcons[$p['code']] ?? 'bi-folder2';
                    $isActive = ($selectedProgram['id'] ?? 0) == $p['id'];
                ?>
                <a href="?program=<?= (int) $p['id'] ?>"
                   class="sa-programs-nav__item<?= $isActive ? ' is-active' : '' ?>"
                   title="<?= e($p['name']) ?>">
                    <span class="sa-programs-nav__icon"><i class="bi <?= e($icon) ?>"></i></span>
                    <span class="sa-programs-nav__copy">
                        <span class="sa-programs-nav__name"><?= e(programShortName($p['name'])) ?></span>
                        <span class="sa-programs-nav__meta"><?= e($p['code']) ?> · <?= (int) $p['indicator_count'] ?> records</span>
                    </span>
                    <span class="sa-programs-nav__chevron"><i class="bi bi-chevron-right"></i></span>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-9">
        <?php if ($selectedProgram): ?>
        <div class="sa-panel sa-programs-detail">
            <div class="sa-panel__header">
                <span class="sa-panel__title">
                    <i class="bi <?= e($programIcons[$selectedProgram['code']] ?? 'bi-folder2') ?> me-1"></i>
                    <?= e($selectedProgram['name']) ?>
                    <span class="sa-panel__count">(<?= count($indicators) ?> records)</span>
                </span>
                <?php if (!$editIndicator): ?>
                <div class="sa-panel__actions">
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#indicatorModal">
                        <i class="bi bi-plus-lg me-1"></i>Add Record
                    </button>
                </div>
                <?php endif; ?>
            </div>

            <div class="sa-programs-toolbar">
                <div class="sa-programs-search">
                    <i class="bi bi-search"></i>
                    <input type="search"
                           id="indicatorSearch"
                           class="form-control form-control-sm"
                           placeholder="Search by name or code…"
                           autocomplete="off"
                           aria-label="Search records">
                </div>
                <div class="sa-programs-toolbar__hint">
                    <i class="bi bi-lightbulb"></i>
                    Tap <strong>Edit</strong> to change a record, or use the trash icon to remove it.
                </div>
            </div>

            <div class="sa-programs-table-wrap">
                <table class="table table-sm sa-programs-table mb-0">
                    <thead>
                        <tr>
                            <th>Health record</th>
                            <th class="text-center" style="width:9rem;">How to count</th>
                            <th class="text-end" style="width:5.5rem;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="indicatorTableBody">
                        <?php if (empty($indicators)): ?>
                        <tr class="sa-programs-empty-row">
                            <td colspan="3">
                                <div class="sa-programs-empty">
                                    <i class="bi bi-inbox"></i>
                                    <p>No records yet for this program.</p>
                                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#indicatorModal">
                                        <i class="bi bi-plus-lg me-1"></i>Add first record
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php else: foreach ($indicators as $i):
                            $searchText = strtolower($i['code'] . ' ' . $i['description'] . ' ' . ($i['part'] ?? '') . ' ' . ($i['category'] ?? ''));
                        ?>
                        <tr class="sa-programs-row" data-search="<?= e($searchText) ?>">
                            <td>
                                <div class="sa-programs-record">
                                    <code class="sa-programs-record__code"><?= e($i['code']) ?></code>
                                    <span class="sa-programs-record__name"><?= e($i['description']) ?></span>
                                    <?php if (!empty($i['part']) || !empty($i['category'])): ?>
                                    <span class="sa-programs-record__group">
                                        <?php if (!empty($i['part'])): ?><?= e($i['part']) ?><?php endif; ?>
                                        <?php if (!empty($i['part']) && !empty($i['category'])): ?> · <?php endif; ?>
                                        <?php if (!empty($i['category'])): ?><?= e($i['category']) ?><?php endif; ?>
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="sa-programs-tag<?= $i['sex_disaggregation'] === 'MFT' ? ' is-split' : '' ?>" title="<?= e(sexCountLabel($i['sex_disaggregation'])) ?>">
                                    <i class="bi <?= $i['sex_disaggregation'] === 'MFT' ? 'bi-gender-ambiguous' : 'bi-123' ?>"></i>
                                    <?= e(sexCountLabel($i['sex_disaggregation'])) ?>
                                </span>
                                <?php if ($i['age_disaggregation'] !== '' && $i['age_disaggregation'] !== 'NONE'): ?>
                                <span class="sa-programs-tag is-age" title="<?= e($i['age_disaggregation']) ?>">
                                    <i class="bi bi-calendar3"></i> By age
                                </span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <div class="sa-programs-actions">
                                    <a href="?program=<?= (int) $selectedProgramId ?>&edit=<?= (int) $i['id'] ?>"
                                       class="btn btn-sm btn-outline-primary"
                                       title="Edit record">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form method="post" onsubmit="return confirm('Remove this record from the program?')">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="program_id" value="<?= (int) $selectedProgramId ?>">
                                        <input type="hidden" name="id" value="<?= (int) $i['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Remove record">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
                <div id="indicatorNoResults" class="sa-programs-no-results" hidden>
                    <i class="bi bi-search"></i>
                    <p>No records match your search.</p>
                </div>
            </div>
        </div>

        <div class="modal fade" id="indicatorModal" tabindex="-1" aria-labelledby="indicatorModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="indicatorModalLabel">
                            <?= $editIndicator ? 'Edit Health Record' : 'Add Health Record' ?>
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="post" class="sa-modal-form">
                        <div class="modal-body">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="<?= $editIndicator ? 'update' : 'create' ?>">
                            <input type="hidden" name="program_id" value="<?= (int) $selectedProgramId ?>">
                            <?php if ($editIndicator): ?>
                            <input type="hidden" name="id" value="<?= (int) $editIndicator['id'] ?>">
                            <?php endif; ?>

                            <div class="sa-form-section">
                                <div class="sa-form-section__title"><i class="bi bi-card-text"></i> Basic information</div>
                                <div class="row g-2">
                                    <div class="col-sm-4 mb-2">
                                        <label class="form-label small">Record code *</label>
                                        <input type="text" name="code" class="form-control form-control-sm" required
                                               placeholder="e.g. CHILD_A1_1"
                                               value="<?= e($editIndicator['code'] ?? '') ?>">
                                        <div class="form-text">Short ID used in reports.</div>
                                    </div>
                                    <div class="col-sm-8 mb-2">
                                        <label class="form-label small">Record name *</label>
                                        <input type="text" name="description" class="form-control form-control-sm" required
                                               placeholder="e.g. BCG (within 24 hours)"
                                               value="<?= e($editIndicator['description'] ?? '') ?>">
                                        <div class="form-text">What nurses see when encoding data.</div>
                                    </div>
                                </div>
                            </div>

                            <div class="sa-form-section">
                                <div class="sa-form-section__title"><i class="bi bi-folder2"></i> Grouping <span class="text-muted fw-normal">(optional)</span></div>
                                <div class="row g-2">
                                    <div class="col-sm-6 mb-2">
                                        <label class="form-label small">Section / Part</label>
                                        <input type="text" name="part" class="form-control form-control-sm"
                                               placeholder="e.g. Part II. Natality"
                                               value="<?= e($editIndicator['part'] ?? '') ?>">
                                    </div>
                                    <div class="col-sm-6 mb-2">
                                        <label class="form-label small">Category</label>
                                        <input type="text" name="category" class="form-control form-control-sm"
                                               placeholder="e.g. Live Births"
                                               value="<?= e($editIndicator['category'] ?? '') ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="sa-form-section mb-0">
                                <div class="sa-form-section__title"><i class="bi bi-sliders"></i> How nurses enter counts</div>
                                <div class="row g-2">
                                    <div class="col-sm-6 mb-2">
                                        <label class="form-label small">Count by sex? *</label>
                                        <select name="sex_disaggregation" class="form-select form-select-sm" required>
                                            <option value="NONE" <?= ($editIndicator['sex_disaggregation'] ?? 'NONE') === 'NONE' ? 'selected' : '' ?>>
                                                No — one combined total
                                            </option>
                                            <option value="MFT" <?= ($editIndicator['sex_disaggregation'] ?? '') === 'MFT' ? 'selected' : '' ?>>
                                                Yes — Male, Female, and Total
                                            </option>
                                        </select>
                                    </div>
                                    <div class="col-sm-6 mb-2">
                                        <label class="form-label small">Count by age group?</label>
                                        <input type="text" name="age_disaggregation" class="form-control form-control-sm"
                                               placeholder="Leave as NONE, or e.g. 10-14,15-19,20-49"
                                               value="<?= e($editIndicator['age_disaggregation'] ?? 'NONE') ?>">
                                        <div class="form-text">Type <strong>NONE</strong> if no age breakdown is needed.</div>
                                    </div>
                                    <div class="col-sm-4 mb-0">
                                        <label class="form-label small">Display order</label>
                                        <input type="number" name="sort_order" class="form-control form-control-sm" min="0"
                                               placeholder="Auto"
                                               value="<?= e($editIndicator['sort_order'] ?? '') ?>">
                                        <div class="form-text">Lower numbers appear first. Leave blank to auto-sort.</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <?php if ($editIndicator): ?>
                            <a href="?program=<?= (int) $selectedProgramId ?>" class="btn btn-outline-secondary btn-sm">Cancel</a>
                            <?php else: ?>
                            <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                            <?php endif; ?>
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="bi bi-check-lg me-1"></i><?= $editIndicator ? 'Save changes' : 'Add record' ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <?php if ($editIndicator): ?>
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            var modalElement = document.getElementById('indicatorModal');
            if (modalElement && window.bootstrap) {
                new bootstrap.Modal(modalElement).show();
            }
        });
        </script>
        <?php endif; ?>

        <?php else: ?>
        <div class="sa-programs-welcome">
            <div class="sa-programs-welcome__icon"><i class="bi bi-arrow-left-circle"></i></div>
            <h2>Choose a program to get started</h2>
            <p>Select one of the health programs on the left — such as <strong>Child Health</strong> or <strong>Maternal Health</strong> — to view and manage the records nurses fill out each month.</p>
            <ul class="sa-programs-welcome__steps">
                <li><span>1</span> Pick a program from the list</li>
                <li><span>2</span> Review or search existing records</li>
                <li><span>3</span> Add or edit records as needed</li>
            </ul>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php saPageClose(); ?>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var searchInput = document.getElementById('indicatorSearch');
    var tableBody = document.getElementById('indicatorTableBody');
    var noResults = document.getElementById('indicatorNoResults');

    if (!searchInput || !tableBody) {
        return;
    }

    function filterIndicators() {
        var query = searchInput.value.trim().toLowerCase();
        var rows = tableBody.querySelectorAll('.sa-programs-row');
        var visible = 0;

        rows.forEach(function (row) {
            var match = query === '' || (row.getAttribute('data-search') || '').indexOf(query) !== -1;
            row.hidden = !match;
            if (match) {
                visible++;
            }
        });

        if (noResults) {
            noResults.hidden = visible > 0 || rows.length === 0;
        }
    }

    searchInput.addEventListener('input', filterIndicators);
});
</script>

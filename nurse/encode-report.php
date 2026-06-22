<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/indicators.php';
requireRole(['nurse']);

$db = getDB();
$submissionId = (int) ($_GET['id'] ?? 0);
$submission = getSubmission($db, $submissionId);

if (!$submission || !canViewSubmission($submission)) {
    setFlash('danger', 'Report not found or access denied.');
    redirect(roleUrl('nurse', 'submissions.php'));
}

$editable = canEditSubmission($submission);
$pageTitle = 'Encode Report';
$bodyClass = 'encode-report-active';
$extraStyles = ['encode-report.css'];
$extraScripts = ['report-form.js'];

$grouped = loadIndicatorsGrouped($db, (int) $submission['program_id']);
$stored  = loadIndicatorValues($db, $submissionId);

$partsList = [];
foreach ($grouped as $part => $categories) {
    $count = 0;
    foreach ($categories as $inds) {
        $count += count($inds);
    }
    $partsList[] = ['name' => $part, 'count' => $count];
}
$totalParts = count($partsList);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="encode-report-page">

    <nav aria-label="breadcrumb" class="encode-breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item">
                <a href="<?= e(roleUrl('nurse', 'submissions.php')) ?>">
                    <i class="bi bi-arrow-left me-1"></i>My Reports
                </a>
            </li>
            <li class="breadcrumb-item active" aria-current="page">Encode</li>
        </ol>
    </nav>

    <header class="encode-header mt-2">
        <div class="encode-header__main">
            <h1 class="encode-header__title"><?= e($submission['program_name']) ?></h1>
            <p class="encode-header__subtitle">Enter monthly health indicators for your assigned barangay</p>
        </div>
        <div class="encode-header__badges">
            <?= statusBadge($submission['status']) ?>
            <?php if (!empty($submission['report_code'])): ?>
            <span class="badge bg-light text-dark border">
                <i class="bi bi-upc-scan me-1"></i><?= e($submission['report_code']) ?>
            </span>
            <?php endif; ?>
            <?php if ($editable): ?>
            <span class="encode-autosave-pill" id="autosavePill" title="Drafts auto-save every 45 seconds">
                <span class="encode-autosave-dot"></span>
                <span id="autosaveText">Auto-save on</span>
            </span>
            <?php endif; ?>
        </div>
    </header>

    <div class="encode-meta-grid">
        <div class="encode-meta-item">
            <div class="encode-meta-item__icon"><i class="bi bi-geo-alt-fill"></i></div>
            <div>
                <div class="encode-meta-item__label">Barangay</div>
                <div class="encode-meta-item__value"><?= e($submission['barangay_name']) ?></div>
            </div>
        </div>
        <div class="encode-meta-item">
            <div class="encode-meta-item__icon"><i class="bi bi-calendar3"></i></div>
            <div>
                <div class="encode-meta-item__label">Reporting Period</div>
                <div class="encode-meta-item__value"><?= e(periodLabel((int)$submission['year'], (int)$submission['month'])) ?></div>
            </div>
        </div>
        <div class="encode-meta-item">
            <div class="encode-meta-item__icon"><i class="bi bi-clipboard2-pulse"></i></div>
            <div>
                <div class="encode-meta-item__label">Program</div>
                <div class="encode-meta-item__value"><?= e($submission['program_name']) ?></div>
            </div>
        </div>
    </div>

    <?php if ($submission['remarks'] && $submission['status'] === 'rejected'): ?>
    <div class="encode-remarks-alert">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <div>
            <strong>Report returned for revision</strong><br>
            <?= e($submission['remarks']) ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($editable): ?>

    <div class="encode-progress-card">
        <div class="encode-progress-ring" aria-hidden="true">
            <svg viewBox="0 0 64 64">
                <circle class="encode-progress-ring__bg" cx="32" cy="32" r="26"></circle>
                <circle class="encode-progress-ring__fill" id="progressRingFill" cx="32" cy="32" r="26"></circle>
            </svg>
            <span class="encode-progress-ring__label" id="progressRingLabel">0%</span>
        </div>
        <div class="encode-progress-details">
            <div class="encode-progress-details__title">Overall completion</div>
            <div class="encode-progress-details__stats">
                <span id="progressSummary">0 / 0 fields filled</span>
                <span class="text-muted"> · </span>
                <span id="progressPartsHint"><?= $totalParts ?> section<?= $totalParts !== 1 ? 's' : '' ?> total</span>
            </div>
            <div class="encode-progress-bar-wrap" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" id="progressBarWrap">
                <div class="encode-progress-bar-fill" id="progressBarFill"></div>
            </div>
        </div>
        <div class="encode-progress-part-hint d-none d-md-block">
            <i class="bi bi-lightbulb me-1"></i>
            Tip: Use <kbd>Ctrl</kbd>+<kbd>→</kbd> to move between sections
        </div>
    </div>

    <form id="reportForm" class="encode-form" data-submission-id="<?= $submissionId ?>" data-total-parts="<?= $totalParts ?>">

        <div class="encode-stepper-card">
            <div class="step-pills" id="stepPills" role="tablist" aria-label="Report sections">
                <?php foreach ($partsList as $i => $p): $idx = $i + 1; ?>
                <button type="button"
                        class="step-pill <?= $idx === 1 ? 'step-pill--active' : '' ?>"
                        data-part="<?= $idx ?>"
                        role="tab"
                        aria-selected="<?= $idx === 1 ? 'true' : 'false' ?>"
                        aria-controls="part<?= $idx ?>">
                    <span class="step-pill-number"><?= $idx ?></span>
                    <span class="step-pill-label"><?= e($p['name']) ?></span>
                    <span class="step-pill-count">
                        <span class="step-filled-<?= $idx ?>">0</span>/<span class="step-total-<?= $idx ?>"><?= $p['count'] ?></span>
                    </span>
                </button>
                <?php if ($idx < $totalParts): ?>
                <div class="step-connector" aria-hidden="true"></div>
                <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="encode-toolbar">
            <div class="encode-search-wrap">
                <i class="bi bi-search encode-search-icon"></i>
                <input type="text"
                       id="indicatorSearch"
                       class="form-control"
                       placeholder="Search indicators by name or code…"
                       autocomplete="off"
                       aria-label="Search indicators">
                <button type="button" class="encode-search-clear d-none" id="searchClearBtn" aria-label="Clear search">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <span class="encode-toolbar-hint">
                <kbd>Ctrl</kbd>+<kbd>S</kbd> save
            </span>
            <div class="encode-field-counter" id="progressText">
                <i class="bi bi-ui-checks-grid"></i>
                <span id="fieldCounterLabel">0 / 0 fields</span>
            </div>
        </div>

        <?php renderIndicatorForm($grouped, $stored, false); ?>

        <div class="encode-no-results" id="encodeNoResults">
            <i class="bi bi-search"></i>
            No indicators match your search. Try a different keyword.
        </div>

        <div class="encode-action-bar">
            <div class="encode-action-bar__nav">
                <button type="button" id="prevPartBtn" class="btn btn-outline-secondary btn-nav" disabled>
                    <i class="bi bi-chevron-left"></i> Previous
                </button>
                <button type="button" id="nextPartBtn" class="btn btn-primary btn-nav">
                    Next <i class="bi bi-chevron-right"></i>
                </button>
                <span class="encode-action-bar__part-label" id="partIndicator">
                    Section <span id="currentPartNum">1</span> of <?= $totalParts ?>
                </span>
            </div>
            <div class="encode-action-bar__actions">
                <div id="saveStatus" class="alert d-none mb-0 py-2 px-3 small"></div>
                <button type="button" id="copyPrevBtn" class="btn btn-outline-secondary" title="Copy values from previous month">
                    <i class="bi bi-copy"></i><span class="d-none d-sm-inline">Copy Previous</span>
                </button>
                <button type="button" id="saveDraftBtn" class="btn btn-outline-secondary">
                    <i class="bi bi-cloud-arrow-up"></i><span class="d-none d-sm-inline">Save Draft</span>
                </button>
                <button type="button" id="submitReportBtn" class="btn btn-success">
                    <i class="bi bi-send-fill"></i><span class="d-none d-sm-inline">Submit Report</span>
                </button>
            </div>
        </div>

    </form>

    <?php else: ?>

    <div class="card encode-readonly-card shadow-sm">
        <div class="card-body">
            <?php renderIndicatorSummary($grouped, $stored); ?>
        </div>
    </div>
    <div class="encode-back-link">
        <a href="<?= e(roleUrl('nurse', 'submissions.php')) ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Back to My Reports
        </a>
    </div>

    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

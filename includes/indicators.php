<?php

/**
 * Load indicators grouped by part and category for a program.
 */
function loadIndicatorsGrouped(PDO $db, int $programId): array
{
    $stmt = $db->prepare(
        'SELECT * FROM indicator WHERE program_id = ? AND is_active = 1 ORDER BY sort_order, id'
    );
    $stmt->execute([$programId]);
    $indicators = $stmt->fetchAll();

    $grouped = [];
    foreach ($indicators as $ind) {
        $part = $ind['part'] ?: 'General';
        $category = $ind['category'] ?: 'Uncategorized';
        $grouped[$part][$category][] = $ind;
    }
    return $grouped;
}

/**
 * Load existing indicator values keyed for easy lookup.
 */
function loadIndicatorValues(PDO $db, int $submissionId): array
{
    $stmt = $db->prepare(
        'SELECT indicator_id, sex, age_group, value, notes FROM indicator_value WHERE submission_id = ?'
    );
    $stmt->execute([$submissionId]);
    $values = [];
    foreach ($stmt->fetchAll() as $row) {
        $key = $row['indicator_id'] . '|' . ($row['sex'] ?? '') . '|' . ($row['age_group'] ?? '');
        $values[$key] = $row;
    }
    return $values;
}

function valueKey(int $indicatorId, ?string $sex, ?string $ageGroup): string
{
    return $indicatorId . '|' . ($sex ?? '') . '|' . ($ageGroup ?? '');
}

function getStoredValue(array $stored, int $indicatorId, ?string $sex, ?string $ageGroup): string
{
    $key = valueKey($indicatorId, $sex, $ageGroup);
    return isset($stored[$key]) ? (string) $stored[$key]['value'] : '';
}

function formatIndicatorValue(string $val): string
{
    if ($val === '') {
        return '—';
    }
    if (is_numeric($val) && (float) $val == (int) (float) $val) {
        return (string) (int) (float) $val;
    }
    return $val;
}

function indicatorNumericValue(string $val): float
{
    $val = trim($val);
    if ($val === '') {
        return 0.0;
    }
    return is_numeric($val) ? (float) $val : 0.0;
}

function sumIndicatorRowValues(array $ind, array $stored): float
{
    $columns = indicatorValueColumns($ind);
    $hasSexMft = ($ind['sex_disaggregation'] ?? '') === 'MFT';

    if (!$hasSexMft) {
        $sum = 0.0;
        foreach ($columns as $col) {
            $sum += indicatorNumericValue(getStoredValue(
                $stored,
                (int) $ind['id'],
                $col['sex'],
                $col['ageGroup']
            ));
        }
        return $sum;
    }

    $byAge = [];
    foreach ($columns as $col) {
        $age = $col['ageGroup'] ?? '';
        $byAge[$age][] = $col;
    }

    $sum = 0.0;
    foreach ($byAge as $cols) {
        $male = 0.0;
        $female = 0.0;
        $total = 0.0;
        foreach ($cols as $col) {
            $value = indicatorNumericValue(getStoredValue(
                $stored,
                (int) $ind['id'],
                $col['sex'],
                $col['ageGroup']
            ));
            if ($col['sex'] === 'M') {
                $male = $value;
            } elseif ($col['sex'] === 'F') {
                $female = $value;
            } elseif ($col['sex'] === 'T') {
                $total = $value;
            }
        }
        if ($male > 0 || $female > 0) {
            $sum += $male + $female;
        } else {
            $sum += $total;
        }
    }

    return $sum;
}

function sumRecordedIndicatorValues(array $grouped, array $stored): array
{
    $grandTotal = 0.0;
    $partTotals = [];

    foreach ($grouped as $part => $categories) {
        $partSum = 0.0;
        foreach ($categories as $indicators) {
            foreach ($indicators as $ind) {
                $partSum += sumIndicatorRowValues($ind, $stored);
            }
        }
        $partTotals[$part] = $partSum;
        $grandTotal += $partSum;
    }

    return [
        'grand' => $grandTotal,
        'parts' => $partTotals,
    ];
}

function formatRecordedTotal(float $total): string
{
    if ($total == (int) $total) {
        return number_format((int) $total);
    }
    return number_format($total, 2);
}

function indicatorValueColumns(array $ind): array
{
    $ageGroups = parseAgeGroups($ind['age_disaggregation']);
    $hasSex = $ind['sex_disaggregation'] === 'MFT';
    $sexes = $hasSex ? ['M', 'F', 'T'] : [null];
    $columns = [];

    foreach ($ageGroups as $ageGroup) {
        foreach ($sexes as $sex) {
            $label = '';
            if ($ageGroup) {
                $label = $ageGroup;
            }
            if ($sex) {
                $label .= ($label ? ' / ' : '') . sexLabels()[$sex];
            }
            if ($label === '') {
                $label = 'Value';
            }
            $columns[] = [
                'sex'      => $sex,
                'ageGroup' => $ageGroup,
                'label'    => $label,
            ];
        }
    }

    return $columns;
}

function columnStructureKey(array $columns): string
{
    $parts = [];
    foreach ($columns as $col) {
        $parts[] = ($col['sex'] ?? '') . '|' . ($col['ageGroup'] ?? '');
    }
    return implode(',', $parts);
}

/**
 * Build part list metadata for stepper navigation.
 *
 * @return list<array{name: string, count: int, filled: int}>
 */
function buildPartsList(array $grouped, array $stored = []): array
{
    $partsList = [];
    foreach ($grouped as $part => $categories) {
        $count = 0;
        $filled = 0;
        foreach ($categories as $inds) {
            foreach ($inds as $ind) {
                $count++;
                $hasValue = false;
                foreach (indicatorValueColumns($ind) as $col) {
                    if (getStoredValue($stored, (int) $ind['id'], $col['sex'], $col['ageGroup']) !== '') {
                        $hasValue = true;
                        break;
                    }
                }
                if ($hasValue) {
                    $filled++;
                }
            }
        }
        $partsList[] = ['name' => $part, 'count' => $count, 'filled' => $filled];
    }
    return $partsList;
}

/**
 * Render category blocks as read-only summary tables.
 */
function renderIndicatorCategoryBlocks(array $categories, array $stored): void
{
    foreach ($categories as $category => $indicators) {
        $groups = [];
        foreach ($indicators as $ind) {
            $columns = indicatorValueColumns($ind);
            $key = columnStructureKey($columns);
            $groups[$key]['columns'] = $columns;
            $groups[$key]['rows'][] = $ind;
        }
        ?>
        <div class="indicator-category-block">
            <div class="category-header"><?= e($category) ?></div>
            <?php foreach ($groups as $group): ?>
            <div class="table-responsive">
                <table class="table table-sm table-hover indicator-summary-table mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="indicator-desc-col">Indicator</th>
                            <?php foreach ($group['columns'] as $col): ?>
                            <th class="text-end value-col"><?= e($col['label']) ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($group['rows'] as $ind): ?>
                        <tr>
                            <td class="indicator-desc-col">
                                <div class="indicator-title"><?= e($ind['description']) ?></div>
                                <div class="indicator-code"><?= e($ind['code']) ?></div>
                            </td>
                            <?php foreach ($group['columns'] as $col):
                                $val = getStoredValue(
                                    $stored,
                                    (int) $ind['id'],
                                    $col['sex'],
                                    $col['ageGroup']
                                );
                            ?>
                            <td class="text-end value-cell"><?= e(formatIndicatorValue($val)) ?></td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endforeach; ?>
        </div>
        <?php
    }
}

/**
 * Render read-only indicator summary as aligned tables.
 */
function renderIndicatorSummary(array $grouped, array $stored): void
{
    $partIndex = 0;
    echo '<div class="accordion indicator-summary" id="indicatorAccordion">';
    foreach ($grouped as $part => $categories) {
        $partIndex++;
        $partId = 'part' . $partIndex;
        $partCount = 0;
        foreach ($categories as $inds) {
            $partCount += count($inds);
        }
        ?>
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button <?= $partIndex > 1 ? 'collapsed' : '' ?>" type="button"
                        data-bs-toggle="collapse" data-bs-target="#<?= $partId ?>">
                    <?= e($part) ?> <span class="badge bg-secondary ms-2"><?= $partCount ?></span>
                </button>
            </h2>
            <div id="<?= $partId ?>" class="accordion-collapse collapse <?= $partIndex === 1 ? 'show' : '' ?>"
                 data-bs-parent="#indicatorAccordion">
                <div class="accordion-body p-0">
                    <?php renderIndicatorCategoryBlocks($categories, $stored); ?>
                </div>
            </div>
        </div>
        <?php
    }
    echo '</div>';
}

/**
 * Render read-only indicator summary with encode-style part pagination.
 */
function renderIndicatorSummaryPaginated(array $grouped, array $stored): void
{
    $partIndex = 0;
    echo '<div id="partsContainer">';
    foreach ($grouped as $part => $categories) {
        $partIndex++;
        $partId = 'part' . $partIndex;
        $partCount = 0;
        $partFilled = 0;
        foreach ($categories as $inds) {
            foreach ($inds as $ind) {
                $partCount++;
                $hasValue = false;
                foreach (indicatorValueColumns($ind) as $col) {
                    if (getStoredValue($stored, (int) $ind['id'], $col['sex'], $col['ageGroup']) !== '') {
                        $hasValue = true;
                        break;
                    }
                }
                if ($hasValue) {
                    $partFilled++;
                }
            }
        }
        $display = $partIndex === 1 ? 'block' : 'none';
        $badgeClass = $partFilled === $partCount && $partCount > 0 ? 'bg-success' : 'bg-secondary';
        ?>
        <div class="part-card" id="<?= $partId ?>" data-part-index="<?= $partIndex ?>" style="display:<?= $display ?>">
            <div class="part-card-header d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <span class="part-card-step"><?= $partIndex ?></span>
                    <span class="part-card-title"><?= e($part) ?></span>
                </div>
                <span class="badge part-count-badge <?= $badgeClass ?>">
                    <?= (int) $partFilled ?>/<?= (int) $partCount ?>
                </span>
            </div>
            <div class="part-card-body p-0">
                <?php renderIndicatorCategoryBlocks($categories, $stored); ?>
            </div>
        </div>
        <?php
    }
    echo '</div>';
}

/**
 * Render indicator form fields (editable or read-only).
 */
function renderIndicatorForm(array $grouped, array $stored, bool $readonly = false, bool $paginated = false): void
{
    if ($readonly) {
        if ($paginated) {
            renderIndicatorSummaryPaginated($grouped, $stored);
        } else {
            renderIndicatorSummary($grouped, $stored);
        }
        return;
    }

    $partIndex = 0;
    echo '<div id="partsContainer">';
    foreach ($grouped as $part => $categories) {
        $partIndex++;
        $partId = 'part' . $partIndex;
        $partCount = 0;
        foreach ($categories as $inds) {
            $partCount += count($inds);
        }
        $display = $partIndex === 1 ? 'block' : 'none';
        ?>
        <div class="part-card" id="<?= $partId ?>" data-part-index="<?= $partIndex ?>" style="display:<?= $display ?>">
            <div class="part-card-header d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <span class="part-card-step"><?= $partIndex ?></span>
                    <span class="part-card-title"><?= e($part) ?></span>
                </div>
                <span class="badge part-count-badge bg-secondary" id="badge-<?= $partId ?>">
                    <span class="part-filled-count">0</span>/<span class="part-total-count"><?= $partCount ?></span>
                </span>
            </div>
            <div class="part-card-body">
                <?php foreach ($categories as $category => $indicators):
                    $groups = [];
                    foreach ($indicators as $ind) {
                        $columns = indicatorValueColumns($ind);
                        $key = columnStructureKey($columns);
                        $groups[$key]['columns'] = $columns;
                        $groups[$key]['rows'][] = $ind;
                    }
                ?>
                <div class="indicator-category-block">
                    <div class="category-header">
                        <i class="bi bi-tag-fill me-1" style="font-size:0.7rem;opacity:0.6;"></i>
                        <?= e($category) ?>
                    </div>
                    <?php foreach ($groups as $group):
                        $hasColumnHeaders = false;
                        foreach ($group['columns'] as $col) {
                            if ($col['sex'] !== null || $col['ageGroup'] !== null) {
                                $hasColumnHeaders = true;
                                break;
                            }
                        }
                    ?>
                    <div class="indicator-table-group">
                        <?php if ($hasColumnHeaders): ?>
                        <div class="indicator-column-headers d-none d-lg-flex">
                            <div class="indicator-desc-header">Indicator</div>
                            <div class="indicator-values-headers d-flex">
                                <?php foreach ($group['columns'] as $col): ?>
                                <div class="indicator-col-header">
                                    <span class="col-header-label"><?= e($col['label']) ?></span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php foreach ($group['rows'] as $ind):
                            $ageGroups = parseAgeGroups($ind['age_disaggregation']);
                            $hasSex = $ind['sex_disaggregation'] === 'MFT';
                            $sexes = $hasSex ? ['M', 'F', 'T'] : [null];
                        ?>
                        <div class="indicator-row">
                            <div class="indicator-row-inner d-flex flex-wrap align-items-start gap-2 gap-lg-0">
                                <div class="indicator-info-col">
                                    <div class="indicator-desc"><?= e($ind['description']) ?></div>
                                    <div class="indicator-code"><?= e($ind['code']) ?></div>
                                </div>
                                <div class="indicator-inputs-col d-flex flex-wrap gap-2">
                                    <?php foreach ($group['columns'] as $col):
                                        $val = getStoredValue($stored, (int)$ind['id'], $col['sex'], $col['ageGroup']);
                                        $name = 'values[' . $ind['id'] . '][' . ($col['sex'] ?? '_') . '][' . ($col['ageGroup'] ?? '_') . ']';
                                    ?>
                                    <div class="indicator-input-wrap">
                                        <label class="col-header-label d-lg-none"><?= e($col['label']) ?></label>
                                        <input type="number" step="0.01" min="0"
                                               name="<?= e($name) ?>"
                                               class="form-control form-control-sm indicator-input"
                                               value="<?= e($val) ?>"
                                               placeholder="0"
                                               autocomplete="off"
                                               data-indicator-id="<?= (int)$ind['id'] ?>"
                                               data-sex="<?= e($col['sex'] ?? '') ?>"
                                               data-age-group="<?= e($col['ageGroup'] ?? '') ?>">
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }
    echo '</div>';
}

/**
 * Save indicator values from POST data.
 */
function saveIndicatorValues(PDO $db, int $submissionId, array $values): int
{
    $upsert = $db->prepare(
        'INSERT INTO indicator_value (submission_id, indicator_id, sex, age_group, value)
         VALUES (?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE value = VALUES(value)'
    );

    $count = 0;
    foreach ($values as $indicatorId => $sexData) {
        if (!is_array($sexData)) {
            continue;
        }
        foreach ($sexData as $sexKey => $ageData) {
            if (!is_array($ageData)) {
                continue;
            }
            foreach ($ageData as $ageKey => $val) {
                $sex = $sexKey === '_' ? null : $sexKey;
                $ageGroup = $ageKey === '_' ? null : $ageKey;
                $numVal = ($val === '' || $val === null) ? 0 : (float) $val;
                $upsert->execute([$submissionId, (int)$indicatorId, $sex, $ageGroup, $numVal]);
                $count++;
            }
        }
    }
    return $count;
}

/**
 * Render compact read-only summary cards (no dropdown/accordion).
 */
function renderIndicatorCompactSummary(array $grouped, array $stored, array $partValueTotals = []): void
{
    $partIndex = 0;
    foreach ($grouped as $part => $categories) {
        $partIndex++;
        $partCount = 0;
        foreach ($categories as $inds) {
            $partCount += count($inds);
        }
        $partRecorded = $partValueTotals[$part] ?? null;
        ?>
        <div class="part-summary-card mb-3">
            <div class="part-summary-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span class="fw-semibold"><?= e($part) ?></span>
                    <span class="badge part-count-badge bg-secondary ms-1"><?= $partCount ?></span>
                    <?php if ($partRecorded !== null): ?>
                    <span class="badge part-value-total-badge" title="Total recorded count for this section">
                        <?= formatRecordedTotal((float) $partRecorded) ?> recorded
                    </span>
                    <?php endif; ?>
                </div>
                <button type="button" class="btn btn-sm btn-outline-primary edit-part-btn"
                        data-part-index="<?= $partIndex ?>">
                    <i class="bi bi-pencil me-1"></i>Edit
                </button>
            </div>
            <?php foreach ($categories as $category => $indicators):
                $groups = [];
                foreach ($indicators as $ind) {
                    $columns = indicatorValueColumns($ind);
                    $key = columnStructureKey($columns);
                    $groups[$key]['columns'] = $columns;
                    $groups[$key]['rows'][] = $ind;
                }
            ?>
            <div class="part-category-block">
                <div class="part-category-title"><?= e($category) ?></div>
                <?php foreach ($groups as $group): ?>
                <div class="table-responsive">
                    <table class="table table-sm table-borderless part-indicator-table mb-0">
                        <thead>
                            <tr>
                                <th class="ps-3">Indicator</th>
                                <?php foreach ($group['columns'] as $col): ?>
                                <th class="text-end" style="width:70px;"><?= e($col['label']) ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($group['rows'] as $ind): ?>
                            <tr>
                                <td class="ps-3">
                                    <div class="indicator-desc"><?= e($ind['description']) ?></div>
                                    <div class="indicator-code"><?= e($ind['code']) ?></div>
                                </td>
                                <?php foreach ($group['columns'] as $col):
                                    $val = getStoredValue(
                                        $stored,
                                        (int) $ind['id'],
                                        $col['sex'],
                                        $col['ageGroup']
                                    );
                                ?>
                                <td class="text-end fw-semibold value-cell"
                                    data-indicator-id="<?= (int) $ind['id'] ?>"
                                    data-sex="<?= e($col['sex'] ?? '') ?>"
                                    data-age-group="<?= e($col['ageGroup'] ?? '') ?>">
                                    <?= e(formatIndicatorValue($val)) ?>
                                </td>
                                <?php endforeach; ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php
    }
}

/**
 * Render one Bootstrap modal per part with editable indicator fields.
 */
function renderPartEditModals(array $grouped, array $stored): void
{
    $partIndex = 0;
    foreach ($grouped as $part => $categories) {
        $partIndex++;
        ?>
        <div class="modal fade part-edit-modal" id="partModal<?= $partIndex ?>" tabindex="-1"
             data-bs-backdrop="static" data-bs-keyboard="false">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="bi bi-pencil-square me-2" style="color:#1d9e75;"></i>Edit: <?= e($part) ?>
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-3">
                        <?php foreach ($categories as $category => $indicators): ?>
                        <div class="part-category-block">
                            <div class="part-category-title"><?= e($category) ?></div>
                            <?php foreach ($indicators as $ind):
                                $ageGroups = parseAgeGroups($ind['age_disaggregation']);
                                $hasSex = $ind['sex_disaggregation'] === 'MFT';
                                $sexes = $hasSex ? ['M', 'F', 'T'] : [null];
                            ?>
                            <div class="indicator-edit-row d-flex flex-wrap align-items-start gap-2 px-3 py-2">
                                <div class="flex-grow-1" style="min-width:180px;">
                                    <div class="small fw-medium"><?= e($ind['description']) ?></div>
                                    <div class="text-muted" style="font-size:0.72rem;"><?= e($ind['code']) ?></div>
                                </div>
                                <div class="d-flex flex-wrap align-items-center gap-2">
                                    <?php foreach ($ageGroups as $ageGroup):
                                        foreach ($sexes as $sex):
                                            $val = getStoredValue($stored, (int)$ind['id'], $sex, $ageGroup);
                                            $label = '';
                                            if ($ageGroup) {
                                                $label .= $ageGroup;
                                            }
                                            if ($sex) {
                                                $label .= ($label ? ' / ' : '') . sexLabels()[$sex];
                                            }
                                            $name = 'values[' . $ind['id'] . '][' . ($sex ?? '_') . '][' . ($ageGroup ?? '_') . ']';
                                    ?>
                                    <div class="text-center">
                                        <?php if ($label): ?>
                                        <label class="form-label d-block small text-muted mb-0" style="font-size:0.65rem;line-height:1.2;">
                                            <?= e($label) ?>
                                        </label>
                                        <?php endif; ?>
                                        <input type="number" step="0.01" min="0"
                                               name="<?= e($name) ?>"
                                               class="form-control form-control-sm indicator-input"
                                               value="<?= e($val) ?>"
                                               placeholder="0"
                                               autocomplete="off"
                                               data-indicator-id="<?= (int)$ind['id'] ?>"
                                               data-sex="<?= e($sex ?? '') ?>"
                                               data-age-group="<?= e($ageGroup ?? '') ?>"
                                               style="width:80px;">
                                    </div>
                                    <?php endforeach; endforeach; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="modal-footer">
                        <div id="modalSaveStatus<?= $partIndex ?>" class="alert d-none mb-0 py-1 px-3 small"></div>
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary save-part-btn" data-part-index="<?= $partIndex ?>">
                            <i class="bi bi-check-lg me-1"></i>Save Changes
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}

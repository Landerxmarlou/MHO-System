<?php

/**
 * Escape a value for CSV output (RFC-style, Excel-compatible).
 */
function csvField(mixed $value): string
{
    $str = (string) $value;
    if (str_contains($str, '"') || str_contains($str, ',') || str_contains($str, "\n") || str_contains($str, "\r")) {
        return '"' . str_replace('"', '""', $str) . '"';
    }
    return $str;
}

/**
 * Send a CSV download that opens cleanly in Microsoft Excel (UTF-8 BOM).
 */
function sendCsvDownload(string $filename, array $headers, array $rows): void
{
    if (!str_ends_with(strtolower($filename), '.csv')) {
        $filename .= '.csv';
    }

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    header('Pragma: public');

    echo "\xEF\xBB\xBF";

    $out = fopen('php://output', 'w');
    fputcsv($out, $headers);

    foreach ($rows as $row) {
        fputcsv($out, $row);
    }

    fclose($out);
    exit;
}

function exportIndicatorValue(mixed $value): string
{
    if ($value === null || $value === '') {
        return '';
    }
    $str = (string) $value;
    if (is_numeric($str) && (float) $str == (int) (float) $str) {
        return (string) (int) (float) $str;
    }
    return $str;
}

function exportSexLabel(?string $sex): string
{
    if ($sex === null || $sex === '') {
        return '';
    }
    return sexLabels()[$sex] ?? $sex;
}

function exportArchiveFilename(string $prefix, ?array $submission = null): string
{
    $date = date('Y-m-d');
    if ($submission) {
        $code = preg_replace('/[^A-Za-z0-9_-]/', '', (string) ($submission['report_code'] ?? 'report'));
        if ($code === '') {
            $code = 'report-' . (int) ($submission['id'] ?? 0);
        }
        return $prefix . '-' . $code . '-' . $date;
    }
    return $prefix . '-' . $date;
}

/**
 * Build archive list query filters shared by archive page and export.
 */
function archiveReportFilters(array $query): array
{
    $where = "rs.status IN ('validated', 'archived')";
    $params = [];

    if (!empty($query['barangay'])) {
        $where .= ' AND rs.barangay_id = ?';
        $params[] = (int) $query['barangay'];
    }
    if (!empty($query['program'])) {
        $where .= ' AND rs.program_id = ?';
        $params[] = (int) $query['program'];
    }

    return [$where, $params];
}

function fetchArchiveSummaryRows(PDO $db, array $query): array
{
    [$where, $params] = archiveReportFilters($query);

    $stmt = $db->prepare(
        "SELECT rs.id, b.name AS barangay, hp.name AS program, hp.code AS program_code,
                rp.year, rp.month, rs.status, rs.report_code, rs.submitted_at, rs.validated_at,
                rs.total_participants, rs.remarks, u.full_name AS submitted_by_name,
                uv.full_name AS validated_by_name
         FROM report_submission rs
         JOIN barangay b ON b.id = rs.barangay_id
         JOIN health_program hp ON hp.id = rs.program_id
         JOIN report_period rp ON rp.id = rs.period_id
         LEFT JOIN users u ON u.id = rs.submitted_by
         LEFT JOIN users uv ON uv.id = rs.validated_by
         WHERE $where
         ORDER BY COALESCE(rs.validated_at, rs.submitted_at) DESC, rp.year DESC, rp.month DESC"
    );
    $stmt->execute($params);

    $rows = [];
    foreach ($stmt->fetchAll() as $r) {
        $rows[] = [
            $r['report_code'] ?? '',
            periodLabel((int) $r['year'], (int) $r['month']),
            $r['barangay'],
            $r['program'],
            $r['program_code'] ?? '',
            ucfirst($r['status']),
            $r['submitted_by_name'] ?? '',
            $r['submitted_at'] ?? '',
            $r['validated_at'] ?? '',
            $r['validated_by_name'] ?? '',
            isset($r['total_participants']) ? formatRecordedTotal((float) $r['total_participants']) : '',
            $r['remarks'] ?? '',
        ];
    }

    return $rows;
}

function loadArchiveExportMeta(PDO $db, array $submission, ?array $grouped = null, ?array $stored = null): array
{
    $submittedBy = '';
    $validatedBy = '';

    if (!empty($submission['submitted_by'])) {
        $stmt = $db->prepare('SELECT full_name FROM users WHERE id = ?');
        $stmt->execute([(int) $submission['submitted_by']]);
        $submittedBy = (string) ($stmt->fetchColumn() ?: '');
    }
    if (!empty($submission['validated_by'])) {
        $stmt = $db->prepare('SELECT full_name FROM users WHERE id = ?');
        $stmt->execute([(int) $submission['validated_by']]);
        $validatedBy = (string) ($stmt->fetchColumn() ?: '');
    }

    if ($grouped === null) {
        $grouped = loadIndicatorsGrouped($db, (int) $submission['program_id']);
    }
    if ($stored === null) {
        $stored = loadIndicatorValues($db, (int) $submission['id']);
    }

    $totalParticipants = isset($submission['total_participants']) && $submission['total_participants'] !== null
        ? (float) $submission['total_participants']
        : sumRecordedIndicatorValues($grouped, $stored)['grand'];

    return [
        'report_code'        => $submission['report_code'] ?? '',
        'period'             => periodLabel((int) $submission['year'], (int) $submission['month']),
        'barangay'           => $submission['barangay_name'] ?? '',
        'program'            => $submission['program_name'] ?? '',
        'program_code'       => $submission['program_code'] ?? '',
        'status'             => ucfirst($submission['status'] ?? ''),
        'submitted_by_name'  => $submittedBy,
        'submitted_at'       => $submission['submitted_at'] ?? '',
        'validated_at'       => $submission['validated_at'] ?? '',
        'validated_by_name'  => $validatedBy,
        'total_participants' => formatRecordedTotal($totalParticipants),
    ];
}

/**
 * Build export rows from the same indicator structure shown on the archive page
 * (includes empty values, not only rows stored in indicator_value).
 */
function buildArchiveIndicatorExportRows(array $meta, array $grouped, array $stored): array
{
    $rows = [];

    foreach ($grouped as $part => $categories) {
        foreach ($categories as $category => $indicators) {
            foreach ($indicators as $ind) {
                foreach (indicatorValueColumns($ind) as $col) {
                    $value = getStoredValue(
                        $stored,
                        (int) $ind['id'],
                        $col['sex'],
                        $col['ageGroup']
                    );
                    $rows[] = [
                        $meta['report_code'],
                        $meta['period'],
                        $meta['barangay'],
                        $meta['program'],
                        $meta['program_code'],
                        $meta['status'],
                        $meta['submitted_by_name'],
                        $meta['submitted_at'],
                        $meta['validated_at'],
                        $meta['validated_by_name'],
                        $meta['total_participants'],
                        $part,
                        $category,
                        $ind['code'] ?? '',
                        $ind['description'] ?? '',
                        exportSexLabel($col['sex']),
                        $col['ageGroup'] ?? '',
                        exportIndicatorValue($value),
                    ];
                }
            }
        }
    }

    return $rows;
}

function fetchArchiveDetailRows(PDO $db, int $submissionId): array
{
    $submission = getSubmission($db, $submissionId);
    if (!$submission || !in_array($submission['status'], ['validated', 'archived'], true)) {
        return [];
    }

    $grouped = loadIndicatorsGrouped($db, (int) $submission['program_id']);
    $stored = loadIndicatorValues($db, $submissionId);
    $meta = loadArchiveExportMeta($db, $submission, $grouped, $stored);

    return buildArchiveIndicatorExportRows($meta, $grouped, $stored);
}

function fetchArchiveFullRows(PDO $db, array $query): array
{
    [$where, $params] = archiveReportFilters($query);

    $stmt = $db->prepare(
        "SELECT rs.id
         FROM report_submission rs
         WHERE $where
         ORDER BY COALESCE(rs.validated_at, rs.submitted_at) DESC"
    );
    $stmt->execute($params);

    $rows = [];
    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $submissionId) {
        $submission = getSubmission($db, (int) $submissionId);
        if (!$submission) {
            continue;
        }
        $grouped = loadIndicatorsGrouped($db, (int) $submission['program_id']);
        $stored = loadIndicatorValues($db, (int) $submissionId);
        $meta = loadArchiveExportMeta($db, $submission, $grouped, $stored);
        $rows = array_merge($rows, buildArchiveIndicatorExportRows($meta, $grouped, $stored));
    }

    return $rows;
}

function archiveSummaryHeaders(): array
{
    return [
        'Report Code',
        'Period',
        'Barangay',
        'Program',
        'Program Code',
        'Status',
        'Submitted By',
        'Submitted At',
        'Validated At',
        'Validated By',
        'Total Participants',
        'Validation Remarks',
    ];
}

function archiveDetailHeaders(): array
{
    return [
        'Report Code',
        'Period',
        'Barangay',
        'Program',
        'Program Code',
        'Status',
        'Submitted By',
        'Submitted At',
        'Validated At',
        'Validated By',
        'Total Participants',
        'Part',
        'Category',
        'Indicator Code',
        'Indicator',
        'Sex',
        'Age Group',
        'Value',
    ];
}

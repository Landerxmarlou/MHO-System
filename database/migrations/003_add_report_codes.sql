-- Barangay short codes for report reference numbers
ALTER TABLE barangay
    ADD COLUMN code VARCHAR(10) NULL DEFAULT NULL
    COMMENT 'Short code used in report references (e.g. AGG)'
    AFTER name;

UPDATE barangay SET code = 'AGG' WHERE name = 'Aggub';
UPDATE barangay SET code = 'BGH' WHERE name = 'Bagahabag';
UPDATE barangay SET code = 'BGA' WHERE name = 'Bangaan';
UPDATE barangay SET code = 'BGR' WHERE name = 'Bangar';
UPDATE barangay SET code = 'BSC' WHERE name = 'Bascaran';
UPDATE barangay SET code = 'COM' WHERE name = 'Communal';
UPDATE barangay SET code = 'CON' WHERE name = 'Concepcion';
UPDATE barangay SET code = 'CUR' WHERE name = 'Curifang';
UPDATE barangay SET code = 'DAD' WHERE name = 'Dadap';
UPDATE barangay SET code = 'LAC' WHERE name = 'Lactawan';
UPDATE barangay SET code = 'OSM' WHERE name = 'Osmena';
UPDATE barangay SET code = 'PDG' WHERE name = 'Pilar D Galima';
UPDATE barangay SET code = 'PBN' WHERE name = 'Poblacion North';
UPDATE barangay SET code = 'PBS' WHERE name = 'Poblacion South';
UPDATE barangay SET code = 'QUE' WHERE name = 'Quezon';
UPDATE barangay SET code = 'QUI' WHERE name = 'Quirino';

-- Unique report reference assigned when a nurse submits
ALTER TABLE report_submission
    ADD COLUMN report_code VARCHAR(40) NULL DEFAULT NULL
    COMMENT 'Format: BRGY-PROGRAM-YYYYMM (e.g. AGG-FP-202612)'
    AFTER program_id;

CREATE UNIQUE INDEX idx_report_submission_code ON report_submission (report_code);

-- Backfill codes for reports already submitted before this feature
UPDATE report_submission rs
JOIN barangay b ON b.id = rs.barangay_id
JOIN health_program hp ON hp.id = rs.program_id
JOIN report_period rp ON rp.id = rs.period_id
SET rs.report_code = CONCAT(
    UPPER(COALESCE(NULLIF(TRIM(b.code), ''), 'BRG')),
    '-',
    UPPER(hp.code),
    '-',
    LPAD(rp.year, 4, '0'),
    LPAD(rp.month, 2, '0')
)
WHERE rs.report_code IS NULL
  AND rs.status IN ('submitted', 'validated', 'rejected');

/*
 Navicat Premium Data Transfer

 Source Server         : localhost
 Source Server Type    : MySQL
 Source Server Version : 100432
 Source Host           : localhost:3306
 Source Schema         : mho_db

 Target Server Type    : MySQL
 Target Server Version : 100432
 File Encoding         : 65001

 Date: 22/06/2026 10:46:10
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS `mho_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `mho_db`;

-- ----------------------------
-- Table structure for audit_log
-- ----------------------------
DROP TABLE IF EXISTS `audit_log`;
CREATE TABLE `audit_log`  (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `table_name` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `record_id` int UNSIGNED NOT NULL,
  `action` enum('INSERT','UPDATE','DELETE') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `changed_by` int UNSIGNED NULL DEFAULT NULL COMMENT 'FK → users.id; NULL = system action',
  `changed_at` datetime NOT NULL DEFAULT current_timestamp,
  `old_value` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL,
  `new_value` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_audit_user`(`changed_by` ASC) USING BTREE,
  CONSTRAINT `fk_audit_user` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 95 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of audit_log
-- ----------------------------
INSERT INTO `audit_log` VALUES (94, 'report_submission', 28, 'INSERT', 5, '2026-06-22 10:45:08', NULL, '{\"status\":\"draft\"}');

-- ----------------------------
-- Table structure for barangay
-- ----------------------------
DROP TABLE IF EXISTS `barangay`;
CREATE TABLE `barangay`  (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `code` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT 'Short code used in report references (e.g. AGG)',
  `municipality` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Cauayan',
  `province` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Isabela',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `uq_barangay_name`(`name` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 17 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of barangay
-- ----------------------------
INSERT INTO `barangay` VALUES (1, 'Aggub', 'AGG', 'Cauayan', 'Isabela', 1);
INSERT INTO `barangay` VALUES (2, 'Bagahabag', 'BGH', 'Cauayan', 'Isabela', 1);
INSERT INTO `barangay` VALUES (3, 'Bangaan', 'BGA', 'Cauayan', 'Isabela', 1);
INSERT INTO `barangay` VALUES (4, 'Bangar', 'BGR', 'Cauayan', 'Isabela', 1);
INSERT INTO `barangay` VALUES (5, 'Bascaran', 'BSC', 'Cauayan', 'Isabela', 1);
INSERT INTO `barangay` VALUES (6, 'Communal', 'COM', 'Cauayan', 'Isabela', 1);
INSERT INTO `barangay` VALUES (7, 'Concepcion', 'CON', 'Cauayan', 'Isabela', 1);
INSERT INTO `barangay` VALUES (8, 'Curifang', 'CUR', 'Cauayan', 'Isabela', 1);
INSERT INTO `barangay` VALUES (9, 'Dadap', 'DAD', 'Cauayan', 'Isabela', 1);
INSERT INTO `barangay` VALUES (10, 'Lactawan', 'LAC', 'Cauayan', 'Isabela', 1);
INSERT INTO `barangay` VALUES (11, 'Osmena', 'OSM', 'Cauayan', 'Isabela', 1);
INSERT INTO `barangay` VALUES (12, 'Pilar D Galima', 'PDG', 'Cauayan', 'Isabela', 1);
INSERT INTO `barangay` VALUES (13, 'Poblacion North', 'PBN', 'Cauayan', 'Isabela', 1);
INSERT INTO `barangay` VALUES (14, 'Poblacion South', 'PBS', 'Cauayan', 'Isabela', 1);
INSERT INTO `barangay` VALUES (15, 'Quezon', 'QUE', 'Cauayan', 'Isabela', 1);
INSERT INTO `barangay` VALUES (16, 'Quirino', 'QUI', 'Cauayan', 'Isabela', 1);

-- ----------------------------
-- Table structure for health_program
-- ----------------------------
DROP TABLE IF EXISTS `health_program`;
CREATE TABLE `health_program`  (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `uq_program_code`(`code` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 7 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of health_program
-- ----------------------------
INSERT INTO `health_program` VALUES (1, 'CHILD', 'Child Health - Immunization and Nutrition');
INSERT INTO `health_program` VALUES (2, 'MATERNAL', 'Maternal Health Care');
INSERT INTO `health_program` VALUES (3, 'NATA', 'Natality and Mortality');
INSERT INTO `health_program` VALUES (4, 'FP', 'Family Planning');
INSERT INTO `health_program` VALUES (5, 'INFECTIOUS', 'Infectious Disease Prevention and Control');
INSERT INTO `health_program` VALUES (6, 'NCD', 'Non-Communicable Diseases');

-- ----------------------------
-- Table structure for indicator
-- ----------------------------
DROP TABLE IF EXISTS `indicator`;
CREATE TABLE `indicator`  (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `program_id` int UNSIGNED NOT NULL,
  `code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `part` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT 'Top-level section, e.g. Part 1 - Child Immunization',
  `category` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT 'Sub-section, e.g. A.1. Immunization Services (0-11 months)',
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `age_disaggregation` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'NONE' COMMENT 'Comma-separated age brackets or NONE',
  `sex_disaggregation` enum('MFT','NONE') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'MFT' COMMENT 'MFT = Male/Female/Total columns; NONE = single value',
  `sort_order` smallint NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `uq_indicator_code`(`program_id` ASC, `code` ASC) USING BTREE,
  CONSTRAINT `fk_ind_program` FOREIGN KEY (`program_id`) REFERENCES `health_program` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 364 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of indicator
-- ----------------------------
INSERT INTO `indicator` VALUES (1, 1, 'CHILD_A1_1', 'Part 1 - Child Immunization', 'A.1 Immunization Services (0-11 months, current year)', 'Children protected at birth (CPAB)', 'NONE', 'MFT', 10, 1);
INSERT INTO `indicator` VALUES (2, 1, 'CHILD_A1_2', 'Part 1 - Child Immunization', 'A.1 Immunization Services (0-11 months, current year)', 'BCG (within 24 hours)', 'NONE', 'MFT', 20, 1);
INSERT INTO `indicator` VALUES (3, 1, 'CHILD_A1_3', 'Part 1 - Child Immunization', 'A.1 Immunization Services (0-11 months, current year)', 'BCG (24 hours to 11 months and 29 days)', 'NONE', 'MFT', 30, 1);
INSERT INTO `indicator` VALUES (4, 1, 'CHILD_A1_4', 'Part 1 - Child Immunization', 'A.1 Immunization Services (0-11 months, current year)', 'Hep B antigen within 24 hrs after birth', 'NONE', 'MFT', 40, 1);
INSERT INTO `indicator` VALUES (5, 1, 'CHILD_A1_5', 'Part 1 - Child Immunization', 'A.1 Immunization Services (0-11 months, current year)', 'Hep B antigen more than 24 hrs up to 14 days', 'NONE', 'MFT', 50, 1);
INSERT INTO `indicator` VALUES (6, 1, 'CHILD_A1_6', 'Part 1 - Child Immunization', 'A.1 Immunization Services (0-11 months, current year)', 'DPT-HiB-HepB 1', 'NONE', 'MFT', 60, 1);
INSERT INTO `indicator` VALUES (7, 1, 'CHILD_A1_7', 'Part 1 - Child Immunization', 'A.1 Immunization Services (0-11 months, current year)', 'DPT-HiB-HepB 2', 'NONE', 'MFT', 70, 1);
INSERT INTO `indicator` VALUES (8, 1, 'CHILD_A1_8', 'Part 1 - Child Immunization', 'A.1 Immunization Services (0-11 months, current year)', 'DPT-HiB-HepB 3', 'NONE', 'MFT', 80, 1);
INSERT INTO `indicator` VALUES (9, 1, 'CHILD_A1_9', 'Part 1 - Child Immunization', 'A.1 Immunization Services (0-11 months, current year)', 'OPV 1', 'NONE', 'MFT', 90, 1);
INSERT INTO `indicator` VALUES (10, 1, 'CHILD_A1_10', 'Part 1 - Child Immunization', 'A.1 Immunization Services (0-11 months, current year)', 'OPV 2', 'NONE', 'MFT', 100, 1);
INSERT INTO `indicator` VALUES (11, 1, 'CHILD_A1_11', 'Part 1 - Child Immunization', 'A.1 Immunization Services (0-11 months, current year)', 'OPV 3', 'NONE', 'MFT', 110, 1);
INSERT INTO `indicator` VALUES (12, 1, 'CHILD_A1_12', 'Part 1 - Child Immunization', 'A.1 Immunization Services (0-11 months, current year)', 'IPV 1', 'NONE', 'MFT', 120, 1);
INSERT INTO `indicator` VALUES (13, 1, 'CHILD_A1_13', 'Part 1 - Child Immunization', 'A.1 Immunization Services (0-11 months, current year)', 'IPV 2', 'NONE', 'MFT', 130, 1);
INSERT INTO `indicator` VALUES (14, 1, 'CHILD_A1_14', 'Part 1 - Child Immunization', 'A.1 Immunization Services (0-11 months, current year)', 'PCV 1', 'NONE', 'MFT', 140, 1);
INSERT INTO `indicator` VALUES (15, 1, 'CHILD_A1_15', 'Part 1 - Child Immunization', 'A.1 Immunization Services (0-11 months, current year)', 'PCV 2', 'NONE', 'MFT', 150, 1);
INSERT INTO `indicator` VALUES (16, 1, 'CHILD_A1_16', 'Part 1 - Child Immunization', 'A.1 Immunization Services (0-11 months, current year)', 'PCV 3', 'NONE', 'MFT', 160, 1);
INSERT INTO `indicator` VALUES (17, 1, 'CHILD_A1_17', 'Part 1 - Child Immunization', 'A.1 Immunization Services (0-11 months, current year)', 'MMR 1', 'NONE', 'MFT', 170, 1);
INSERT INTO `indicator` VALUES (18, 1, 'CHILD_A2_1', 'Part 1 - Child Immunization', 'A.2 Immunization Services (0-11 months, previous year)', 'DPT-HiB-HepB 1', 'NONE', 'MFT', 180, 1);
INSERT INTO `indicator` VALUES (19, 1, 'CHILD_A2_2', 'Part 1 - Child Immunization', 'A.2 Immunization Services (0-11 months, previous year)', 'DPT-HiB-HepB 2', 'NONE', 'MFT', 190, 1);
INSERT INTO `indicator` VALUES (20, 1, 'CHILD_A2_3', 'Part 1 - Child Immunization', 'A.2 Immunization Services (0-11 months, previous year)', 'DPT-HiB-HepB 3', 'NONE', 'MFT', 200, 1);
INSERT INTO `indicator` VALUES (21, 1, 'CHILD_A2_4', 'Part 1 - Child Immunization', 'A.2 Immunization Services (0-11 months, previous year)', 'OPV 1', 'NONE', 'MFT', 210, 1);
INSERT INTO `indicator` VALUES (22, 1, 'CHILD_A2_5', 'Part 1 - Child Immunization', 'A.2 Immunization Services (0-11 months, previous year)', 'OPV 2', 'NONE', 'MFT', 220, 1);
INSERT INTO `indicator` VALUES (23, 1, 'CHILD_A2_6', 'Part 1 - Child Immunization', 'A.2 Immunization Services (0-11 months, previous year)', 'OPV 3', 'NONE', 'MFT', 230, 1);
INSERT INTO `indicator` VALUES (24, 1, 'CHILD_A2_7', 'Part 1 - Child Immunization', 'A.2 Immunization Services (0-11 months, previous year)', 'IPV 1', 'NONE', 'MFT', 240, 1);
INSERT INTO `indicator` VALUES (25, 1, 'CHILD_A2_8', 'Part 1 - Child Immunization', 'A.2 Immunization Services (0-11 months, previous year)', 'IPV 2', 'NONE', 'MFT', 250, 1);
INSERT INTO `indicator` VALUES (26, 1, 'CHILD_A2_9', 'Part 1 - Child Immunization', 'A.2 Immunization Services (0-11 months, previous year)', 'PCV 1', 'NONE', 'MFT', 260, 1);
INSERT INTO `indicator` VALUES (27, 1, 'CHILD_A2_10', 'Part 1 - Child Immunization', 'A.2 Immunization Services (0-11 months, previous year)', 'PCV 2', 'NONE', 'MFT', 270, 1);
INSERT INTO `indicator` VALUES (28, 1, 'CHILD_A2_11', 'Part 1 - Child Immunization', 'A.2 Immunization Services (0-11 months, previous year)', 'PCV 3', 'NONE', 'MFT', 280, 1);
INSERT INTO `indicator` VALUES (29, 1, 'CHILD_A2_12', 'Part 1 - Child Immunization', 'A.2 Immunization Services (0-11 months, previous year)', 'MMR 1', 'NONE', 'MFT', 290, 1);
INSERT INTO `indicator` VALUES (30, 1, 'CHILD_A2_13', 'Part 1 - Child Immunization', 'A.2 Immunization Services (0-11 months, previous year)', 'MMR 2', 'NONE', 'MFT', 300, 1);
INSERT INTO `indicator` VALUES (31, 1, 'CHILD_A2_14', 'Part 1 - Child Immunization', 'A.2 Immunization Services (0-11 months, previous year)', 'Fully Immunized Child (FIC)', 'NONE', 'MFT', 310, 1);
INSERT INTO `indicator` VALUES (32, 1, 'CHILD_A2_15', 'Part 1 - Child Immunization', 'A.2 Immunization Services (0-11 months, previous year)', 'Completely Immunized Child (CIC)', 'NONE', 'MFT', 320, 1);
INSERT INTO `indicator` VALUES (33, 1, 'CHILD_A3_1', 'Part 1 - Child Immunization', 'A.3 School-Based Immunization', 'Grade 1 learners given Td', 'NONE', 'MFT', 330, 1);
INSERT INTO `indicator` VALUES (34, 1, 'CHILD_A3_2', 'Part 1 - Child Immunization', 'A.3 School-Based Immunization', 'Grade 1 learners given MR', 'NONE', 'MFT', 340, 1);
INSERT INTO `indicator` VALUES (35, 1, 'CHILD_A3_3', 'Part 1 - Child Immunization', 'A.3 School-Based Immunization', 'Grade 7 learners given Td', 'NONE', 'MFT', 350, 1);
INSERT INTO `indicator` VALUES (36, 1, 'CHILD_A3_4', 'Part 1 - Child Immunization', 'A.3 School-Based Immunization', 'Grade 7 learners given MR', 'NONE', 'MFT', 360, 1);
INSERT INTO `indicator` VALUES (37, 1, 'CHILD_A3_5', 'Part 1 - Child Immunization', 'A.3 School-Based Immunization', 'HPV 1 (SBI)', 'NONE', 'MFT', 370, 1);
INSERT INTO `indicator` VALUES (38, 1, 'CHILD_A3_6', 'Part 1 - Child Immunization', 'A.3 School-Based Immunization', 'HPV 1 (CBI)', 'NONE', 'MFT', 380, 1);
INSERT INTO `indicator` VALUES (39, 1, 'CHILD_A3_7', 'Part 1 - Child Immunization', 'A.3 School-Based Immunization', 'HPV 2 (CBI)', 'NONE', 'MFT', 390, 1);
INSERT INTO `indicator` VALUES (40, 1, 'CHILD_N1', 'Part 2 - Nutrition Services', 'Nutrition', 'Newborns initiated on breastfeeding within 1 hour after birth', 'NONE', 'MFT', 400, 1);
INSERT INTO `indicator` VALUES (41, 1, 'CHILD_N2', 'Part 2 - Nutrition Services', 'Nutrition', 'Infants born with low birth weight (LBW) given complete Iron supplements', 'NONE', 'MFT', 410, 1);
INSERT INTO `indicator` VALUES (42, 1, 'CHILD_N3A', 'Part 2 - Nutrition Services', 'Nutrition', 'Infants 6-11 months old given Vitamin A', '6-11 months', 'MFT', 420, 1);
INSERT INTO `indicator` VALUES (43, 1, 'CHILD_N3B', 'Part 2 - Nutrition Services', 'Nutrition', 'Infants 12-59 months old given Vitamin A', '12-59 months', 'MFT', 430, 1);
INSERT INTO `indicator` VALUES (44, 1, 'CHILD_N4A', 'Part 2 - Nutrition Services', 'Nutrition', 'Infants 6-11 months old completed routine MNP supplementation', '6-11 months', 'MFT', 440, 1);
INSERT INTO `indicator` VALUES (45, 1, 'CHILD_N4B', 'Part 2 - Nutrition Services', 'Nutrition', 'Infants 12-23 months old completed routine MNP supplementation', '12-23 months', 'MFT', 450, 1);
INSERT INTO `indicator` VALUES (46, 1, 'CHILD_N5A', 'Part 2 - Nutrition Services', 'Nutrition', 'Infants 6-11 months old completed routine LNS-SQ supplementation', '6-11 months', 'MFT', 460, 1);
INSERT INTO `indicator` VALUES (47, 1, 'CHILD_N5B', 'Part 2 - Nutrition Services', 'Nutrition', 'Infants 12-23 months old completed routine LNS-SQ supplementation', '12-23 months', 'MFT', 470, 1);
INSERT INTO `indicator` VALUES (48, 1, 'CHILD_N6', 'Part 2 - Nutrition Services', 'Nutrition', 'Children 0-59 months old seen at health facilities', '0-59 months', 'MFT', 480, 1);
INSERT INTO `indicator` VALUES (49, 1, 'CHILD_N6A', 'Part 2 - Nutrition Services', 'Nutrition', 'Identified MAM', '0-59 months', 'MFT', 490, 1);
INSERT INTO `indicator` VALUES (50, 1, 'CHILD_N6B', 'Part 2 - Nutrition Services', 'Nutrition', 'Identified SAM', '0-59 months', 'MFT', 500, 1);
INSERT INTO `indicator` VALUES (51, 1, 'CHILD_N7', 'Part 2 - Nutrition Services', 'Nutrition', 'MAM enrolled to SFP', 'NONE', 'MFT', 510, 1);
INSERT INTO `indicator` VALUES (52, 1, 'CHILD_N7A', 'Part 2 - Nutrition Services', 'Nutrition', 'MAM enrolled to SFP - Cured', 'NONE', 'MFT', 520, 1);
INSERT INTO `indicator` VALUES (53, 1, 'CHILD_N7B', 'Part 2 - Nutrition Services', 'Nutrition', 'MAM enrolled to SFP - Non-cured', 'NONE', 'MFT', 530, 1);
INSERT INTO `indicator` VALUES (54, 1, 'CHILD_N7C', 'Part 2 - Nutrition Services', 'Nutrition', 'MAM enrolled to SFP - Defaulted', 'NONE', 'MFT', 540, 1);
INSERT INTO `indicator` VALUES (55, 1, 'CHILD_N7D', 'Part 2 - Nutrition Services', 'Nutrition', 'MAM enrolled to SFP - Died', 'NONE', 'MFT', 550, 1);
INSERT INTO `indicator` VALUES (56, 1, 'CHILD_N8', 'Part 2 - Nutrition Services', 'Nutrition', 'SAM admitted to OTC', 'NONE', 'MFT', 560, 1);
INSERT INTO `indicator` VALUES (57, 1, 'CHILD_N8A', 'Part 2 - Nutrition Services', 'Nutrition', 'SAM admitted to OTC - Cured', 'NONE', 'MFT', 570, 1);
INSERT INTO `indicator` VALUES (58, 1, 'CHILD_N8B', 'Part 2 - Nutrition Services', 'Nutrition', 'SAM admitted to OTC - Non-cured', 'NONE', 'MFT', 580, 1);
INSERT INTO `indicator` VALUES (59, 1, 'CHILD_N8C', 'Part 2 - Nutrition Services', 'Nutrition', 'SAM admitted to OTC - Defaulted', 'NONE', 'MFT', 590, 1);
INSERT INTO `indicator` VALUES (60, 1, 'CHILD_N8D', 'Part 2 - Nutrition Services', 'Nutrition', 'SAM admitted to OTC - Died', 'NONE', 'MFT', 600, 1);
INSERT INTO `indicator` VALUES (61, 1, 'CHILD_S1', 'Part 3 - Management of Sick Infants', 'Sick Children', 'Sick infants aged 6-11 months old seen', '6-11 months', 'MFT', 610, 1);
INSERT INTO `indicator` VALUES (62, 1, 'CHILD_S1A', 'Part 3 - Management of Sick Infants', 'Sick Children', 'Sick infants 6-11 months who received Vitamin A capsule (aside from routine)', '6-11 months', 'MFT', 620, 1);
INSERT INTO `indicator` VALUES (63, 1, 'CHILD_S2', 'Part 3 - Management of Sick Infants', 'Sick Children', 'Sick infants aged 12-59 months old seen', '12-59 months', 'MFT', 630, 1);
INSERT INTO `indicator` VALUES (64, 1, 'CHILD_S2A', 'Part 3 - Management of Sick Infants', 'Sick Children', 'Sick infants 12-59 months who received Vitamin A capsule (aside from routine)', '12-59 months', 'MFT', 640, 1);
INSERT INTO `indicator` VALUES (65, 1, 'CHILD_S3', 'Part 3 - Management of Sick Infants', 'Sick Children', 'Acute diarrhea cases 0-59 months old seen', '0-59 months', 'MFT', 650, 1);
INSERT INTO `indicator` VALUES (66, 1, 'CHILD_S3A', 'Part 3 - Management of Sick Infants', 'Sick Children', '0-59 months with acute diarrhea who received ORS only', '0-59 months', 'MFT', 660, 1);
INSERT INTO `indicator` VALUES (67, 1, 'CHILD_S3B', 'Part 3 - Management of Sick Infants', 'Sick Children', '0-59 months with acute diarrhea who received ORS and Zinc drops/syrup', '0-59 months', 'MFT', 670, 1);
INSERT INTO `indicator` VALUES (68, 1, 'CHILD_S4', 'Part 3 - Management of Sick Infants', 'Sick Children', 'Pneumonia cases 0-59 months old seen', '0-59 months', 'MFT', 680, 1);
INSERT INTO `indicator` VALUES (69, 1, 'CHILD_S4A', 'Part 3 - Management of Sick Infants', 'Sick Children', '0-59 months with pneumonia who received antibiotic treatment', '0-59 months', 'MFT', 690, 1);
INSERT INTO `indicator` VALUES (70, 2, 'MAT_I_1A', 'I. Prenatal Care', '8ANC', 'Women who delivered and completed at least 8ANC (a1+a2)', '10-14,15-19,20-49', 'NONE', 10, 1);
INSERT INTO `indicator` VALUES (71, 2, 'MAT_I_1A1', 'I. Prenatal Care', '8ANC', 'Women who delivered and provided 1st to 8th ANC on schedule', '10-14,15-19,20-49', 'NONE', 20, 1);
INSERT INTO `indicator` VALUES (72, 2, 'MAT_I_1A2', 'I. Prenatal Care', '8ANC', 'Women who delivered and completed 8ANC - Trans In from other LGUs', '10-14,15-19,20-49', 'NONE', 30, 1);
INSERT INTO `indicator` VALUES (73, 2, 'MAT_I_1B', 'I. Prenatal Care', '8ANC', 'Women who delivered and were tracked during pregnancy (b1+b2)', '10-14,15-19,20-49', 'NONE', 40, 1);
INSERT INTO `indicator` VALUES (74, 2, 'MAT_I_1B1', 'I. Prenatal Care', '8ANC', 'Women who delivered and tracked during pregnancy (Resident)', '10-14,15-19,20-49', 'NONE', 50, 1);
INSERT INTO `indicator` VALUES (75, 2, 'MAT_I_1B2', 'I. Prenatal Care', '8ANC', 'Trans-In from other LGUs (Non-Resident)', '10-14,15-19,20-49', 'NONE', 60, 1);
INSERT INTO `indicator` VALUES (76, 2, 'MAT_I_1B3', 'I. Prenatal Care', '8ANC', 'Trans-Out (with MOV) before completing 8ANC', '10-14,15-19,20-49', 'NONE', 70, 1);
INSERT INTO `indicator` VALUES (77, 2, 'MAT_I_2A', 'I. Prenatal Care', 'Nutritional Status', 'Pregnant women with Normal BMI (first trimester)', '10-14,15-19,20-49', 'NONE', 80, 1);
INSERT INTO `indicator` VALUES (78, 2, 'MAT_I_2B', 'I. Prenatal Care', 'Nutritional Status', 'Pregnant women with Low BMI (first trimester)', '10-14,15-19,20-49', 'NONE', 90, 1);
INSERT INTO `indicator` VALUES (79, 2, 'MAT_I_2C', 'I. Prenatal Care', 'Nutritional Status', 'Pregnant women with High BMI (first trimester)', '10-14,15-19,20-49', 'NONE', 100, 1);
INSERT INTO `indicator` VALUES (80, 2, 'MAT_I_3A', 'I. Prenatal Care', 'Tetanus diphtheria', 'Women pregnant for the first time given at least 2 doses of Td vaccination', '10-14,15-19,20-49', 'NONE', 110, 1);
INSERT INTO `indicator` VALUES (81, 2, 'MAT_I_3B', 'I. Prenatal Care', 'Tetanus diphtheria', 'Pregnant Women (2nd or more times) given at least 3 doses of Td vaccination (Td2 Plus)', '10-14,15-19,20-49', 'NONE', 120, 1);
INSERT INTO `indicator` VALUES (82, 2, 'MAT_I_4A', 'I. Prenatal Care', 'Prenatal Supplementation', 'Pregnant women who completed iron with folic acid supplementation', '10-14,15-19,20-49', 'NONE', 130, 1);
INSERT INTO `indicator` VALUES (83, 2, 'MAT_I_4B', 'I. Prenatal Care', 'Prenatal Supplementation', 'Pregnant women who completed Multiple Micronutrient Supplementation (MMS)', '10-14,15-19,20-49', 'NONE', 140, 1);
INSERT INTO `indicator` VALUES (84, 2, 'MAT_I_4C', 'I. Prenatal Care', 'Prenatal Supplementation', 'Pregnant women who completed calcium carbonate dose', '10-14,15-19,20-49', 'NONE', 150, 1);
INSERT INTO `indicator` VALUES (85, 2, 'MAT_I_4D', 'I. Prenatal Care', 'Prenatal Supplementation', 'Pregnant women given one dose of deworming tablet', '10-14,15-19,20-49', 'NONE', 160, 1);
INSERT INTO `indicator` VALUES (86, 2, 'MAT_I_5A', 'I. Prenatal Care', 'Anemia Screening', 'Pregnant women tested for CBC or Hgb & Hct count', '10-14,15-19,20-49', 'NONE', 170, 1);
INSERT INTO `indicator` VALUES (87, 2, 'MAT_I_5B', 'I. Prenatal Care', 'Anemia Screening', 'Pregnant women diagnosed with Anemia', '10-14,15-19,20-49', 'NONE', 180, 1);
INSERT INTO `indicator` VALUES (88, 2, 'MAT_I_6A', 'I. Prenatal Care', 'Gestational Diabetes', 'Pregnant women screened for Gestational Diabetes Mellitus', '10-14,15-19,20-49', 'NONE', 190, 1);
INSERT INTO `indicator` VALUES (89, 2, 'MAT_I_6B', 'I. Prenatal Care', 'Gestational Diabetes', 'Pregnant women tested positive for Gestational Diabetes Mellitus', '10-14,15-19,20-49', 'NONE', 200, 1);
INSERT INTO `indicator` VALUES (90, 2, 'MAT_I_7A', 'I. Prenatal Care', 'Deworming', 'Women pregnant given one (1) dose of deworming tablet', '10-14,15-19,20-49', 'NONE', 210, 1);
INSERT INTO `indicator` VALUES (91, 2, 'MAT_I_8A', 'I. Prenatal Care', 'Blood Pressure', 'Pregnant women who had their BP measured during each ANC visit', '10-14,15-19,20-49', 'NONE', 220, 1);
INSERT INTO `indicator` VALUES (92, 2, 'MAT_I_8B', 'I. Prenatal Care', 'Blood Pressure', 'Pregnant women with high BP or danger signs referred to higher-level facility', '10-14,15-19,20-49', 'NONE', 230, 1);
INSERT INTO `indicator` VALUES (93, 2, 'MAT_II_1', 'II. Intrapartum and Newborn Care', 'Deliveries', 'Total Deliveries', '10-14,15-19,20-49', 'NONE', 240, 1);
INSERT INTO `indicator` VALUES (94, 2, 'MAT_II_2A', 'II. Intrapartum and Newborn Care', 'Skilled Health Professionals', 'Deliveries attended by Physicians', '10-14,15-19,20-49', 'NONE', 250, 1);
INSERT INTO `indicator` VALUES (95, 2, 'MAT_II_2B', 'II. Intrapartum and Newborn Care', 'Skilled Health Professionals', 'Deliveries attended by Nurses', '10-14,15-19,20-49', 'NONE', 260, 1);
INSERT INTO `indicator` VALUES (96, 2, 'MAT_II_2C', 'II. Intrapartum and Newborn Care', 'Skilled Health Professionals', 'Deliveries attended by Midwives', '10-14,15-19,20-49', 'NONE', 270, 1);
INSERT INTO `indicator` VALUES (97, 2, 'MAT_II_3A', 'II. Intrapartum and Newborn Care', 'Facility-Based Delivery', 'Facility-Based Delivery - Public', '10-14,15-19,20-49', 'NONE', 280, 1);
INSERT INTO `indicator` VALUES (98, 2, 'MAT_II_3B', 'II. Intrapartum and Newborn Care', 'Facility-Based Delivery', 'Facility-Based Delivery - Private', '10-14,15-19,20-49', 'NONE', 290, 1);
INSERT INTO `indicator` VALUES (99, 2, 'MAT_II_4A', 'II. Intrapartum and Newborn Care', 'Delivery Type', 'Vaginal delivery', '10-14,15-19,20-49', 'NONE', 300, 1);
INSERT INTO `indicator` VALUES (100, 2, 'MAT_II_4B', 'II. Intrapartum and Newborn Care', 'Delivery Type', 'Cesarean Section', '10-14,15-19,20-49', 'NONE', 310, 1);
INSERT INTO `indicator` VALUES (101, 2, 'MAT_II_4C', 'II. Intrapartum and Newborn Care', 'Delivery Type', 'Combined Vaginal-Cesarean Delivery', '10-14,15-19,20-49', 'NONE', 320, 1);
INSERT INTO `indicator` VALUES (102, 2, 'MAT_II_5A', 'II. Intrapartum and Newborn Care', 'Delivery Outcome', 'Full Term', '10-14,15-19,20-49', 'NONE', 330, 1);
INSERT INTO `indicator` VALUES (103, 2, 'MAT_II_5B', 'II. Intrapartum and Newborn Care', 'Delivery Outcome', 'Preterm', '10-14,15-19,20-49', 'NONE', 340, 1);
INSERT INTO `indicator` VALUES (104, 2, 'MAT_II_5C', 'II. Intrapartum and Newborn Care', 'Delivery Outcome', 'Fetal Death', '10-14,15-19,20-49', 'NONE', 350, 1);
INSERT INTO `indicator` VALUES (105, 2, 'MAT_II_5D', 'II. Intrapartum and Newborn Care', 'Delivery Outcome', 'Abortion/Miscarriage', '10-14,15-19,20-49', 'NONE', 360, 1);
INSERT INTO `indicator` VALUES (106, 2, 'MAT_II_6A', 'II. Intrapartum and Newborn Care', 'Live Births by Birth Weight', 'Normal birth weight (> 2500 grams)', '10-14,15-19,20-49', 'MFT', 370, 1);
INSERT INTO `indicator` VALUES (107, 2, 'MAT_II_6B', 'II. Intrapartum and Newborn Care', 'Live Births by Birth Weight', 'Low birth weight (< 2500 grams)', '10-14,15-19,20-49', 'MFT', 380, 1);
INSERT INTO `indicator` VALUES (108, 3, 'NATA_I_1', 'Part I. Mortality', 'Maternal Mortality', 'Maternal Mortality - Total', '10-14,15-19,20-49', 'NONE', 10, 1);
INSERT INTO `indicator` VALUES (109, 3, 'NATA_I_1A', 'Part I. Mortality', 'Maternal Mortality', 'Maternal Mortality - Direct', '10-14,15-19,20-49', 'NONE', 20, 1);
INSERT INTO `indicator` VALUES (110, 3, 'NATA_I_1A1', 'Part I. Mortality', 'Maternal Mortality', 'Maternal Mortality - Direct - Resident', '10-14,15-19,20-49', 'NONE', 30, 1);
INSERT INTO `indicator` VALUES (111, 3, 'NATA_I_1A2', 'Part I. Mortality', 'Maternal Mortality', 'Maternal Mortality - Direct - Non-Resident', '10-14,15-19,20-49', 'NONE', 40, 1);
INSERT INTO `indicator` VALUES (112, 3, 'NATA_I_1B', 'Part I. Mortality', 'Maternal Mortality', 'Maternal Mortality - Indirect', '10-14,15-19,20-49', 'NONE', 50, 1);
INSERT INTO `indicator` VALUES (113, 3, 'NATA_I_1B1', 'Part I. Mortality', 'Maternal Mortality', 'Maternal Mortality - Indirect - Resident', '10-14,15-19,20-49', 'NONE', 60, 1);
INSERT INTO `indicator` VALUES (114, 3, 'NATA_I_1B2', 'Part I. Mortality', 'Maternal Mortality', 'Maternal Mortality - Indirect - Non-Resident', '10-14,15-19,20-49', 'NONE', 70, 1);
INSERT INTO `indicator` VALUES (115, 3, 'NATA_I_2', 'Part I. Mortality', 'Infant Mortality', 'Infant Mortality Rate', 'NONE', 'MFT', 80, 1);
INSERT INTO `indicator` VALUES (116, 3, 'NATA_II_1', 'Part II. Natality', 'Live Births', 'Live Births', 'NONE', 'MFT', 90, 1);
INSERT INTO `indicator` VALUES (117, 3, 'NATA_II_2', 'Part II. Natality', 'Adolescent Birth Rate', 'Adolescent Birth Rate (ABR)', 'NONE', 'MFT', 100, 1);
INSERT INTO `indicator` VALUES (118, 3, 'NATA_II_2A', 'Part II. Natality', 'Adolescent Birth Rate', 'ABR - younger than 10 years old', '<10', 'MFT', 110, 1);
INSERT INTO `indicator` VALUES (119, 3, 'NATA_II_2B', 'Part II. Natality', 'Adolescent Birth Rate', 'ABR - 10-14 years old', '10-14', 'MFT', 120, 1);
INSERT INTO `indicator` VALUES (120, 3, 'NATA_II_2C', 'Part II. Natality', 'Adolescent Birth Rate', 'ABR - 15-19 years old', '15-19', 'MFT', 130, 1);
INSERT INTO `indicator` VALUES (121, 3, 'NATA_II_3', 'Part II. Natality', 'Adolescent Repeat Births', 'Adolescent deliveries that are repeat births', 'NONE', 'MFT', 140, 1);
INSERT INTO `indicator` VALUES (122, 3, 'NATA_II_3A', 'Part II. Natality', 'Adolescent Repeat Births', 'Repeat births - 10-14 years old', '10-14', 'MFT', 150, 1);
INSERT INTO `indicator` VALUES (123, 3, 'NATA_II_3B', 'Part II. Natality', 'Adolescent Repeat Births', 'Repeat births - 15-19 years old', '15-19', 'MFT', 160, 1);
INSERT INTO `indicator` VALUES (124, 4, 'FP_WRA', 'FP Overview', 'WRA', 'No. of WRA (15-49) with demand for FP currently using any modern FP method', '15-49', 'NONE', 5, 1);
INSERT INTO `indicator` VALUES (125, 4, 'FP_CUB_1', 'Current User Beginning', '', 'FSTR/BTL', 'NONE', 'NONE', 10, 1);
INSERT INTO `indicator` VALUES (126, 4, 'FP_CUB_2', 'Current User Beginning', '', 'MSTR/NSV', 'NONE', 'NONE', 20, 1);
INSERT INTO `indicator` VALUES (127, 4, 'FP_CUB_3', 'Current User Beginning', '', 'Condom', 'NONE', 'NONE', 30, 1);
INSERT INTO `indicator` VALUES (128, 4, 'FP_CUB_4A', 'Current User Beginning', '', 'Pills - POP', 'NONE', 'NONE', 40, 1);
INSERT INTO `indicator` VALUES (129, 4, 'FP_CUB_4B', 'Current User Beginning', '', 'Pills - COC', 'NONE', 'NONE', 50, 1);
INSERT INTO `indicator` VALUES (130, 4, 'FP_CUB_5', 'Current User Beginning', '', 'Injectables (DMPA)', 'NONE', 'NONE', 60, 1);
INSERT INTO `indicator` VALUES (131, 4, 'FP_CUB_6A', 'Current User Beginning', '', 'Implant - Interval', 'NONE', 'NONE', 70, 1);
INSERT INTO `indicator` VALUES (132, 4, 'FP_CUB_6B', 'Current User Beginning', '', 'Implant - PP', 'NONE', 'NONE', 80, 1);
INSERT INTO `indicator` VALUES (133, 4, 'FP_CUB_7A', 'Current User Beginning', '', 'IUD - Interval', 'NONE', 'NONE', 90, 1);
INSERT INTO `indicator` VALUES (134, 4, 'FP_CUB_7B', 'Current User Beginning', '', 'IUD - PP', 'NONE', 'NONE', 100, 1);
INSERT INTO `indicator` VALUES (135, 4, 'FP_CUB_8', 'Current User Beginning', '', 'NFP-LAM', 'NONE', 'NONE', 110, 1);
INSERT INTO `indicator` VALUES (136, 4, 'FP_CUB_9', 'Current User Beginning', '', 'NFP-BBT', 'NONE', 'NONE', 120, 1);
INSERT INTO `indicator` VALUES (137, 4, 'FP_CUB_10', 'Current User Beginning', '', 'NFP-CMM', 'NONE', 'NONE', 130, 1);
INSERT INTO `indicator` VALUES (138, 4, 'FP_CUB_11', 'Current User Beginning', '', 'NFP-STM', 'NONE', 'NONE', 140, 1);
INSERT INTO `indicator` VALUES (139, 4, 'FP_CUB_12', 'Current User Beginning', '', 'NFP-SDM', 'NONE', 'NONE', 150, 1);
INSERT INTO `indicator` VALUES (140, 4, 'FP_NAP_1', 'New Acceptors Previous Month', '', 'BTL', 'NONE', 'NONE', 160, 1);
INSERT INTO `indicator` VALUES (141, 4, 'FP_NAP_2', 'New Acceptors Previous Month', '', 'NSV', 'NONE', 'NONE', 170, 1);
INSERT INTO `indicator` VALUES (142, 4, 'FP_NAP_3', 'New Acceptors Previous Month', '', 'Condom', 'NONE', 'NONE', 180, 1);
INSERT INTO `indicator` VALUES (143, 4, 'FP_NAP_4A', 'New Acceptors Previous Month', '', 'Pills - POP', 'NONE', 'NONE', 190, 1);
INSERT INTO `indicator` VALUES (144, 4, 'FP_NAP_4B', 'New Acceptors Previous Month', '', 'Pills - COC', 'NONE', 'NONE', 200, 1);
INSERT INTO `indicator` VALUES (145, 4, 'FP_NAP_5', 'New Acceptors Previous Month', '', 'Injectables (DMPA/POI)', 'NONE', 'NONE', 210, 1);
INSERT INTO `indicator` VALUES (146, 4, 'FP_NAP_6A', 'New Acceptors Previous Month', '', 'Implant - Interval', 'NONE', 'NONE', 220, 1);
INSERT INTO `indicator` VALUES (147, 4, 'FP_NAP_6B', 'New Acceptors Previous Month', '', 'Implant - PP', 'NONE', 'NONE', 230, 1);
INSERT INTO `indicator` VALUES (148, 4, 'FP_NAP_7A', 'New Acceptors Previous Month', '', 'IUD - Interval', 'NONE', 'NONE', 240, 1);
INSERT INTO `indicator` VALUES (149, 4, 'FP_NAP_7B', 'New Acceptors Previous Month', '', 'IUD - PP', 'NONE', 'NONE', 250, 1);
INSERT INTO `indicator` VALUES (150, 4, 'FP_NAP_8', 'New Acceptors Previous Month', '', 'NFP-LAM', 'NONE', 'NONE', 260, 1);
INSERT INTO `indicator` VALUES (151, 4, 'FP_NAP_9', 'New Acceptors Previous Month', '', 'NFP-BBT', 'NONE', 'NONE', 270, 1);
INSERT INTO `indicator` VALUES (152, 4, 'FP_NAP_10', 'New Acceptors Previous Month', '', 'NFP-CMM', 'NONE', 'NONE', 280, 1);
INSERT INTO `indicator` VALUES (153, 4, 'FP_NAP_11', 'New Acceptors Previous Month', '', 'NFP-STM', 'NONE', 'NONE', 290, 1);
INSERT INTO `indicator` VALUES (154, 4, 'FP_NAP_12', 'New Acceptors Previous Month', '', 'NFP-SDM', 'NONE', 'NONE', 300, 1);
INSERT INTO `indicator` VALUES (155, 4, 'FP_OA_1', 'Other Acceptors', '', 'BTL', 'NONE', 'NONE', 310, 1);
INSERT INTO `indicator` VALUES (156, 4, 'FP_OA_2', 'Other Acceptors', '', 'NSV', 'NONE', 'NONE', 320, 1);
INSERT INTO `indicator` VALUES (157, 4, 'FP_OA_3', 'Other Acceptors', '', 'Condom', 'NONE', 'NONE', 330, 1);
INSERT INTO `indicator` VALUES (158, 4, 'FP_OA_4A', 'Other Acceptors', '', 'Pills - POP', 'NONE', 'NONE', 340, 1);
INSERT INTO `indicator` VALUES (159, 4, 'FP_OA_4B', 'Other Acceptors', '', 'Pills - COC', 'NONE', 'NONE', 350, 1);
INSERT INTO `indicator` VALUES (160, 4, 'FP_OA_5', 'Other Acceptors', '', 'Injectables (DMPA/POI)', 'NONE', 'NONE', 360, 1);
INSERT INTO `indicator` VALUES (161, 4, 'FP_OA_6A', 'Other Acceptors', '', 'Implant - Interval', 'NONE', 'NONE', 370, 1);
INSERT INTO `indicator` VALUES (162, 4, 'FP_OA_6B', 'Other Acceptors', '', 'Implant - PP', 'NONE', 'NONE', 380, 1);
INSERT INTO `indicator` VALUES (163, 4, 'FP_OA_7A', 'Other Acceptors', '', 'IUD - Interval', 'NONE', 'NONE', 390, 1);
INSERT INTO `indicator` VALUES (164, 4, 'FP_OA_7B', 'Other Acceptors', '', 'IUD - PP', 'NONE', 'NONE', 400, 1);
INSERT INTO `indicator` VALUES (165, 4, 'FP_OA_8', 'Other Acceptors', '', 'NFP-LAM', 'NONE', 'NONE', 410, 1);
INSERT INTO `indicator` VALUES (166, 4, 'FP_OA_9', 'Other Acceptors', '', 'NFP-BBT', 'NONE', 'NONE', 420, 1);
INSERT INTO `indicator` VALUES (167, 4, 'FP_OA_10', 'Other Acceptors', '', 'NFP-CMM', 'NONE', 'NONE', 430, 1);
INSERT INTO `indicator` VALUES (168, 4, 'FP_OA_11', 'Other Acceptors', '', 'NFP-STM', 'NONE', 'NONE', 440, 1);
INSERT INTO `indicator` VALUES (169, 4, 'FP_OA_12', 'Other Acceptors', '', 'NFP-SDM', 'NONE', 'NONE', 450, 1);
INSERT INTO `indicator` VALUES (170, 4, 'FP_DO_1', 'Drop-outs', '', 'BTL', 'NONE', 'NONE', 460, 1);
INSERT INTO `indicator` VALUES (171, 4, 'FP_DO_2', 'Drop-outs', '', 'NSV', 'NONE', 'NONE', 470, 1);
INSERT INTO `indicator` VALUES (172, 4, 'FP_DO_3', 'Drop-outs', '', 'Condom', 'NONE', 'NONE', 480, 1);
INSERT INTO `indicator` VALUES (173, 4, 'FP_DO_4A', 'Drop-outs', '', 'Pills - POP', 'NONE', 'NONE', 490, 1);
INSERT INTO `indicator` VALUES (174, 4, 'FP_DO_4B', 'Drop-outs', '', 'Pills - COC', 'NONE', 'NONE', 500, 1);
INSERT INTO `indicator` VALUES (175, 4, 'FP_DO_5', 'Drop-outs', '', 'Injectables (DMPA/POI)', 'NONE', 'NONE', 510, 1);
INSERT INTO `indicator` VALUES (176, 4, 'FP_DO_6A', 'Drop-outs', '', 'Implant - Interval', 'NONE', 'NONE', 520, 1);
INSERT INTO `indicator` VALUES (177, 4, 'FP_DO_6B', 'Drop-outs', '', 'Implant - PP', 'NONE', 'NONE', 530, 1);
INSERT INTO `indicator` VALUES (178, 4, 'FP_DO_7A', 'Drop-outs', '', 'IUD - Interval', 'NONE', 'NONE', 540, 1);
INSERT INTO `indicator` VALUES (179, 4, 'FP_DO_7B', 'Drop-outs', '', 'IUD - PP', 'NONE', 'NONE', 550, 1);
INSERT INTO `indicator` VALUES (180, 4, 'FP_DO_8', 'Drop-outs', '', 'NFP-LAM', 'NONE', 'NONE', 560, 1);
INSERT INTO `indicator` VALUES (181, 4, 'FP_DO_9', 'Drop-outs', '', 'NFP-BBT', 'NONE', 'NONE', 570, 1);
INSERT INTO `indicator` VALUES (182, 4, 'FP_DO_10', 'Drop-outs', '', 'NFP-CMM', 'NONE', 'NONE', 580, 1);
INSERT INTO `indicator` VALUES (183, 4, 'FP_DO_11', 'Drop-outs', '', 'NFP-STM', 'NONE', 'NONE', 590, 1);
INSERT INTO `indicator` VALUES (184, 4, 'FP_DO_12', 'Drop-outs', '', 'NFP-SDM', 'NONE', 'NONE', 600, 1);
INSERT INTO `indicator` VALUES (185, 4, 'FP_CUE_1', 'Current User Ending', '', 'BTL', 'NONE', 'NONE', 610, 1);
INSERT INTO `indicator` VALUES (186, 4, 'FP_CUE_2', 'Current User Ending', '', 'NSV', 'NONE', 'NONE', 620, 1);
INSERT INTO `indicator` VALUES (187, 4, 'FP_CUE_3', 'Current User Ending', '', 'Condom', 'NONE', 'NONE', 630, 1);
INSERT INTO `indicator` VALUES (188, 4, 'FP_CUE_4A', 'Current User Ending', '', 'Pills - POP', 'NONE', 'NONE', 640, 1);
INSERT INTO `indicator` VALUES (189, 4, 'FP_CUE_4B', 'Current User Ending', '', 'Pills - COC', 'NONE', 'NONE', 650, 1);
INSERT INTO `indicator` VALUES (190, 4, 'FP_CUE_5', 'Current User Ending', '', 'Injectables (DMPA/POI)', 'NONE', 'NONE', 660, 1);
INSERT INTO `indicator` VALUES (191, 4, 'FP_CUE_6A', 'Current User Ending', '', 'Implant - Interval', 'NONE', 'NONE', 670, 1);
INSERT INTO `indicator` VALUES (192, 4, 'FP_CUE_6B', 'Current User Ending', '', 'Implant - PP', 'NONE', 'NONE', 680, 1);
INSERT INTO `indicator` VALUES (193, 4, 'FP_CUE_7A', 'Current User Ending', '', 'IUD - Interval', 'NONE', 'NONE', 690, 1);
INSERT INTO `indicator` VALUES (194, 4, 'FP_CUE_7B', 'Current User Ending', '', 'IUD - PP', 'NONE', 'NONE', 700, 1);
INSERT INTO `indicator` VALUES (195, 4, 'FP_CUE_8', 'Current User Ending', '', 'NFP-LAM', 'NONE', 'NONE', 710, 1);
INSERT INTO `indicator` VALUES (196, 4, 'FP_CUE_9', 'Current User Ending', '', 'NFP-BBT', 'NONE', 'NONE', 720, 1);
INSERT INTO `indicator` VALUES (197, 4, 'FP_CUE_10', 'Current User Ending', '', 'NFP-CMM', 'NONE', 'NONE', 730, 1);
INSERT INTO `indicator` VALUES (198, 4, 'FP_CUE_11', 'Current User Ending', '', 'NFP-STM', 'NONE', 'NONE', 740, 1);
INSERT INTO `indicator` VALUES (199, 4, 'FP_CUE_12', 'Current User Ending', '', 'NFP-SDM', 'NONE', 'NONE', 750, 1);
INSERT INTO `indicator` VALUES (200, 4, 'FP_NAM_1', 'New Acceptors Present Month', '', 'BTL', 'NONE', 'NONE', 760, 1);
INSERT INTO `indicator` VALUES (201, 4, 'FP_NAM_2', 'New Acceptors Present Month', '', 'NSV', 'NONE', 'NONE', 770, 1);
INSERT INTO `indicator` VALUES (202, 4, 'FP_NAM_3', 'New Acceptors Present Month', '', 'Condom', 'NONE', 'NONE', 780, 1);
INSERT INTO `indicator` VALUES (203, 4, 'FP_NAM_4A', 'New Acceptors Present Month', '', 'Pills - POP', 'NONE', 'NONE', 790, 1);
INSERT INTO `indicator` VALUES (204, 4, 'FP_NAM_4B', 'New Acceptors Present Month', '', 'Pills - COC', 'NONE', 'NONE', 800, 1);
INSERT INTO `indicator` VALUES (205, 4, 'FP_NAM_5', 'New Acceptors Present Month', '', 'Injectables (DMPA/POI)', 'NONE', 'NONE', 810, 1);
INSERT INTO `indicator` VALUES (206, 4, 'FP_NAM_6A', 'New Acceptors Present Month', '', 'Implant - Interval', 'NONE', 'NONE', 820, 1);
INSERT INTO `indicator` VALUES (207, 4, 'FP_NAM_6B', 'New Acceptors Present Month', '', 'Implant - PP', 'NONE', 'NONE', 830, 1);
INSERT INTO `indicator` VALUES (208, 4, 'FP_NAM_7A', 'New Acceptors Present Month', '', 'IUD - Interval', 'NONE', 'NONE', 840, 1);
INSERT INTO `indicator` VALUES (209, 4, 'FP_NAM_7B', 'New Acceptors Present Month', '', 'IUD - PP', 'NONE', 'NONE', 850, 1);
INSERT INTO `indicator` VALUES (210, 4, 'FP_NAM_8', 'New Acceptors Present Month', '', 'NFP-LAM', 'NONE', 'NONE', 860, 1);
INSERT INTO `indicator` VALUES (211, 4, 'FP_NAM_9', 'New Acceptors Present Month', '', 'NFP-BBT', 'NONE', 'NONE', 870, 1);
INSERT INTO `indicator` VALUES (212, 4, 'FP_NAM_10', 'New Acceptors Present Month', '', 'NFP-CMM', 'NONE', 'NONE', 880, 1);
INSERT INTO `indicator` VALUES (213, 4, 'FP_NAM_11', 'New Acceptors Present Month', '', 'NFP-STM', 'NONE', 'NONE', 890, 1);
INSERT INTO `indicator` VALUES (214, 4, 'FP_NAM_12', 'New Acceptors Present Month', NULL, 'NFP-SDM', 'NONE', 'MFT', 900, 1);
INSERT INTO `indicator` VALUES (215, 5, 'INF_A_1', 'Infectious Disease', 'A. Filariasis', 'Individuals examined and found positive for lymphatic filariasis', 'NONE', 'MFT', 10, 1);
INSERT INTO `indicator` VALUES (216, 5, 'INF_A_1A', 'Infectious Disease', 'A. Filariasis', 'Positive via Nocturnal Blood Examination', 'NONE', 'MFT', 20, 1);
INSERT INTO `indicator` VALUES (217, 5, 'INF_A_1B', 'Infectious Disease', 'A. Filariasis', 'Positive via Rapid Diagnostic Test', 'NONE', 'MFT', 30, 1);
INSERT INTO `indicator` VALUES (218, 5, 'INF_A_2A', 'Infectious Disease', 'A. Filariasis', 'Individuals examined for the 1st time with lymphedema', 'NONE', 'MFT', 40, 1);
INSERT INTO `indicator` VALUES (219, 5, 'INF_A_3A', 'Infectious Disease', 'A. Filariasis', 'Individuals examined for the 1st time with elephantiasis', 'NONE', 'MFT', 50, 1);
INSERT INTO `indicator` VALUES (220, 5, 'INF_A_4A', 'Infectious Disease', 'A. Filariasis', 'Individuals examined for hydrocele (transillumination, 1st time)', 'NONE', 'MFT', 60, 1);
INSERT INTO `indicator` VALUES (221, 5, 'INF_A_5A', 'Infectious Disease', 'A. Filariasis - MDA', 'MDA recipients - 1 year old', '1', 'MFT', 70, 1);
INSERT INTO `indicator` VALUES (222, 5, 'INF_A_5B', 'Infectious Disease', 'A. Filariasis - MDA', 'MDA recipients - 2-4 years old', '2-4', 'MFT', 80, 1);
INSERT INTO `indicator` VALUES (223, 5, 'INF_A_5C', 'Infectious Disease', 'A. Filariasis - MDA', 'MDA recipients - 5-14 years old', '5-14', 'MFT', 90, 1);
INSERT INTO `indicator` VALUES (224, 5, 'INF_A_5D', 'Infectious Disease', 'A. Filariasis - MDA', 'MDA recipients - 15 years old and above', '15+', 'MFT', 100, 1);
INSERT INTO `indicator` VALUES (225, 5, 'INF_B_1A', 'Infectious Disease', 'B. Rabies', 'Category I Rabies exposure', 'NONE', 'MFT', 110, 1);
INSERT INTO `indicator` VALUES (226, 5, 'INF_B_2A', 'Infectious Disease', 'B. Rabies', 'Category II Rabies exposure', 'NONE', 'MFT', 120, 1);
INSERT INTO `indicator` VALUES (227, 5, 'INF_B_3A', 'Infectious Disease', 'B. Rabies', 'Category II eligible for Anti-Rabies Vaccine (ARV)', 'NONE', 'MFT', 130, 1);
INSERT INTO `indicator` VALUES (228, 5, 'INF_B_4A', 'Infectious Disease', 'B. Rabies', 'Category II who received complete dose of ARV', 'NONE', 'MFT', 140, 1);
INSERT INTO `indicator` VALUES (229, 5, 'INF_B_5A', 'Infectious Disease', 'B. Rabies', 'Category III Rabies exposure', 'NONE', 'MFT', 150, 1);
INSERT INTO `indicator` VALUES (230, 5, 'INF_B_6A', 'Infectious Disease', 'B. Rabies', 'Category III received complete ARV and single dose RIG', 'NONE', 'MFT', 160, 1);
INSERT INTO `indicator` VALUES (231, 5, 'INF_B_6B', 'Infectious Disease', 'B. Rabies', 'Category III received complete ARV (Booster)', 'NONE', 'MFT', 170, 1);
INSERT INTO `indicator` VALUES (232, 5, 'INF_B_6C', 'Infectious Disease', 'B. Rabies', 'Category III without history of complete anti-rabies vaccine', 'NONE', 'MFT', 180, 1);
INSERT INTO `indicator` VALUES (233, 5, 'INF_B_6D', 'Infectious Disease', 'B. Rabies', 'Category III with history of complete anti-rabies vaccine', 'NONE', 'MFT', 190, 1);
INSERT INTO `indicator` VALUES (234, 5, 'INF_B_7A', 'Infectious Disease', 'B. Rabies', 'Dog-mediated Rabies exposure', 'NONE', 'MFT', 200, 1);
INSERT INTO `indicator` VALUES (235, 5, 'INF_B_7B', 'Infectious Disease', 'B. Rabies', 'Cat-mediated Rabies exposure', 'NONE', 'MFT', 210, 1);
INSERT INTO `indicator` VALUES (236, 5, 'INF_B_7C', 'Infectious Disease', 'B. Rabies', 'Other animal Rabies exposure', 'NONE', 'MFT', 220, 1);
INSERT INTO `indicator` VALUES (237, 5, 'INF_C_1A', 'Infectious Disease', 'C. Schistosomiasis', 'Patients Seen - 1-4 years old', '1-4', 'MFT', 230, 1);
INSERT INTO `indicator` VALUES (238, 5, 'INF_C_1B', 'Infectious Disease', 'C. Schistosomiasis', 'Patients Seen - 5-14 years old', '5-14', 'MFT', 240, 1);
INSERT INTO `indicator` VALUES (239, 5, 'INF_C_1C', 'Infectious Disease', 'C. Schistosomiasis', 'Patients Seen - 15-19 years old', '15-19', 'MFT', 250, 1);
INSERT INTO `indicator` VALUES (240, 5, 'INF_C_1D', 'Infectious Disease', 'C. Schistosomiasis', 'Patients Seen - 20-59 years old', '20-59', 'MFT', 260, 1);
INSERT INTO `indicator` VALUES (241, 5, 'INF_C_1E', 'Infectious Disease', 'C. Schistosomiasis', 'Patients Seen - 60 years old and above', '60+', 'MFT', 270, 1);
INSERT INTO `indicator` VALUES (242, 5, 'INF_C_2A', 'Infectious Disease', 'C. Schistosomiasis', 'Clinical/Suspected Cases Seen - 1-4 years old', '1-4', 'MFT', 280, 1);
INSERT INTO `indicator` VALUES (243, 5, 'INF_C_2B', 'Infectious Disease', 'C. Schistosomiasis', 'Clinical/Suspected Cases Seen - 5-14 years old', '5-14', 'MFT', 290, 1);
INSERT INTO `indicator` VALUES (244, 5, 'INF_C_2C', 'Infectious Disease', 'C. Schistosomiasis', 'Clinical/Suspected Cases Seen - 15-19 years old', '15-19', 'MFT', 300, 1);
INSERT INTO `indicator` VALUES (245, 5, 'INF_C_2D', 'Infectious Disease', 'C. Schistosomiasis', 'Clinical/Suspected Cases Seen - 20-59 years old', '20-59', 'MFT', 310, 1);
INSERT INTO `indicator` VALUES (246, 5, 'INF_C_2E', 'Infectious Disease', 'C. Schistosomiasis', 'Clinical/Suspected Cases Seen - 60 years old and above', '60+', 'MFT', 320, 1);
INSERT INTO `indicator` VALUES (247, 5, 'INF_C_3A', 'Infectious Disease', 'C. Schistosomiasis', 'Clinical/Suspected Cases Treated - 5-14 years old', '5-14', 'MFT', 330, 1);
INSERT INTO `indicator` VALUES (248, 5, 'INF_C_3B', 'Infectious Disease', 'C. Schistosomiasis', 'Clinical/Suspected Cases Treated - 15-19 years old', '15-19', 'MFT', 340, 1);
INSERT INTO `indicator` VALUES (249, 5, 'INF_C_3C', 'Infectious Disease', 'C. Schistosomiasis', 'Clinical/Suspected Cases Treated - 20-59 years old', '20-59', 'MFT', 350, 1);
INSERT INTO `indicator` VALUES (250, 5, 'INF_C_3D', 'Infectious Disease', 'C. Schistosomiasis', 'Clinical/Suspected Cases Treated - 60 years old and above', '60+', 'MFT', 360, 1);
INSERT INTO `indicator` VALUES (251, 5, 'INF_C_4A', 'Infectious Disease', 'C. Schistosomiasis', 'Clinical/Suspected Cases Cured - 5-14 years old', '5-14', 'MFT', 370, 1);
INSERT INTO `indicator` VALUES (252, 5, 'INF_C_4B', 'Infectious Disease', 'C. Schistosomiasis', 'Clinical/Suspected Cases Cured - 15-19 years old', '15-19', 'MFT', 380, 1);
INSERT INTO `indicator` VALUES (253, 5, 'INF_C_4C', 'Infectious Disease', 'C. Schistosomiasis', 'Clinical/Suspected Cases Cured - 20-59 years old', '20-59', 'MFT', 390, 1);
INSERT INTO `indicator` VALUES (254, 5, 'INF_C_4D', 'Infectious Disease', 'C. Schistosomiasis', 'Clinical/Suspected Cases Cured - 60 years old and above', '60+', 'MFT', 400, 1);
INSERT INTO `indicator` VALUES (255, 5, 'INF_C_9A', 'Infectious Disease', 'C. Schistosomiasis - MDA', 'Individuals dewormed with Praziquantel - 5-14 years old', '5-14', 'MFT', 410, 1);
INSERT INTO `indicator` VALUES (256, 5, 'INF_C_9B', 'Infectious Disease', 'C. Schistosomiasis - MDA', 'Individuals dewormed with Praziquantel - 15-19 years old', '15-19', 'MFT', 420, 1);
INSERT INTO `indicator` VALUES (257, 5, 'INF_C_9C', 'Infectious Disease', 'C. Schistosomiasis - MDA', 'Individuals dewormed with Praziquantel - 20-59 years old', '20-59', 'MFT', 430, 1);
INSERT INTO `indicator` VALUES (258, 5, 'INF_C_9D', 'Infectious Disease', 'C. Schistosomiasis - MDA', 'Individuals dewormed with Praziquantel - 60 years old and above', '60+', 'MFT', 440, 1);
INSERT INTO `indicator` VALUES (259, 5, 'INF_D_1A', 'Infectious Disease', 'D. STH', 'Screened for STH - 1-4 years old', '1-4', 'MFT', 450, 1);
INSERT INTO `indicator` VALUES (260, 5, 'INF_D_1B', 'Infectious Disease', 'D. STH', 'Screened for STH - 5-14 years old', '5-14', 'MFT', 460, 1);
INSERT INTO `indicator` VALUES (261, 5, 'INF_D_1C', 'Infectious Disease', 'D. STH', 'Screened for STH - 15-19 years old', '15-19', 'MFT', 470, 1);
INSERT INTO `indicator` VALUES (262, 5, 'INF_D_1D', 'Infectious Disease', 'D. STH', 'Screened for STH - 20-59 years old', '20-59', 'MFT', 480, 1);
INSERT INTO `indicator` VALUES (263, 5, 'INF_D_1E', 'Infectious Disease', 'D. STH', 'Screened for STH - 60 years old and above', '60+', 'MFT', 490, 1);
INSERT INTO `indicator` VALUES (264, 5, 'INF_D_3A', 'Infectious Disease', 'D. STH', 'Confirmed STH Cases - 1-4 years old', '1-4', 'MFT', 500, 1);
INSERT INTO `indicator` VALUES (265, 5, 'INF_D_3B', 'Infectious Disease', 'D. STH', 'Confirmed STH Cases - 5-14 years old', '5-14', 'MFT', 510, 1);
INSERT INTO `indicator` VALUES (266, 5, 'INF_D_3C', 'Infectious Disease', 'D. STH', 'Confirmed STH Cases - 15-19 years old', '15-19', 'MFT', 520, 1);
INSERT INTO `indicator` VALUES (267, 5, 'INF_D_3D', 'Infectious Disease', 'D. STH', 'Confirmed STH Cases - 20-59 years old', '20-59', 'MFT', 530, 1);
INSERT INTO `indicator` VALUES (268, 5, 'INF_D_3E', 'Infectious Disease', 'D. STH', 'Confirmed STH Cases - 60 years old and above', '60+', 'MFT', 540, 1);
INSERT INTO `indicator` VALUES (269, 5, 'INF_D_4A', 'Infectious Disease', 'D. STH', 'Confirmed STH Cases Treated - 1-4 years old', '1-4', 'MFT', 550, 1);
INSERT INTO `indicator` VALUES (270, 5, 'INF_D_4B', 'Infectious Disease', 'D. STH', 'Confirmed STH Cases Treated - 5-14 years old', '5-14', 'MFT', 560, 1);
INSERT INTO `indicator` VALUES (271, 5, 'INF_D_4C', 'Infectious Disease', 'D. STH', 'Confirmed STH Cases Treated - 15-19 years old', '15-19', 'MFT', 570, 1);
INSERT INTO `indicator` VALUES (272, 5, 'INF_D_4D', 'Infectious Disease', 'D. STH', 'Confirmed STH Cases Treated - 20-59 years old', '20-59', 'MFT', 580, 1);
INSERT INTO `indicator` VALUES (273, 5, 'INF_D_4E', 'Infectious Disease', 'D. STH', 'Confirmed STH Cases Treated - 60 years old and above', '60+', 'MFT', 590, 1);
INSERT INTO `indicator` VALUES (274, 5, 'INF_E_1A', 'Infectious Disease', 'E. Leprosy', 'Leprosy cases on treatment - 0-14 years old', '0-14', 'MFT', 600, 1);
INSERT INTO `indicator` VALUES (275, 5, 'INF_E_1B', 'Infectious Disease', 'E. Leprosy', 'Leprosy cases on treatment - 15-18 years old', '15-18', 'MFT', 610, 1);
INSERT INTO `indicator` VALUES (276, 5, 'INF_E_1C', 'Infectious Disease', 'E. Leprosy', 'Leprosy cases on treatment - 19 years old and above', '19+', 'MFT', 620, 1);
INSERT INTO `indicator` VALUES (277, 5, 'INF_E_2A', 'Infectious Disease', 'E. Leprosy', 'Newly detected leprosy cases - 0-14 years old', '0-14', 'MFT', 630, 1);
INSERT INTO `indicator` VALUES (278, 5, 'INF_E_2B', 'Infectious Disease', 'E. Leprosy', 'Newly detected leprosy cases - 15-18 years old', '15-18', 'MFT', 640, 1);
INSERT INTO `indicator` VALUES (279, 5, 'INF_E_2C', 'Infectious Disease', 'E. Leprosy', 'Newly detected leprosy cases - 19 years old and above', '19+', 'MFT', 650, 1);
INSERT INTO `indicator` VALUES (280, 5, 'INF_E_3A', 'Infectious Disease', 'E. Leprosy', 'Confirmed Leprosy Cases - 0-14 years old', '0-14', 'MFT', 660, 1);
INSERT INTO `indicator` VALUES (281, 5, 'INF_E_3B', 'Infectious Disease', 'E. Leprosy', 'Confirmed Leprosy Cases - 15-18 years old', '15-18', 'MFT', 670, 1);
INSERT INTO `indicator` VALUES (282, 5, 'INF_E_3C', 'Infectious Disease', 'E. Leprosy', 'Confirmed Leprosy Cases - 19 years old and above', '19+', 'MFT', 680, 1);
INSERT INTO `indicator` VALUES (283, 5, 'INF_E_4A', 'Infectious Disease', 'E. Leprosy', 'Completed MDT - 0-14 years old', '0-14', 'MFT', 690, 1);
INSERT INTO `indicator` VALUES (284, 5, 'INF_E_4B', 'Infectious Disease', 'E. Leprosy', 'Completed MDT - 15-18 years old', '15-18', 'MFT', 700, 1);
INSERT INTO `indicator` VALUES (285, 5, 'INF_E_4C', 'Infectious Disease', 'E. Leprosy', 'Completed MDT - 19 years old and above', '19+', 'MFT', 710, 1);
INSERT INTO `indicator` VALUES (286, 6, 'NCD_1A', 'NCD', '1. PhilPEN Risk Assessment', 'Adults (20-59 years old) risk assessed using PhilPEN protocol', '20-59', 'NONE', 10, 1);
INSERT INTO `indicator` VALUES (287, 6, 'NCD_1B', 'NCD', '1. PhilPEN Risk Assessment', 'Adults identified as current smokers', '20-59', 'NONE', 20, 1);
INSERT INTO `indicator` VALUES (288, 6, 'NCD_1B1', 'NCD', '1. PhilPEN Risk Assessment', 'Adults - current smokers via Tobacco product', '20-59', 'NONE', 30, 1);
INSERT INTO `indicator` VALUES (289, 6, 'NCD_1B2', 'NCD', '1. PhilPEN Risk Assessment', 'Adults - current smokers via Vaporized Nicotine Products', '20-59', 'NONE', 40, 1);
INSERT INTO `indicator` VALUES (290, 6, 'NCD_1B3', 'NCD', '1. PhilPEN Risk Assessment', 'Adults - current smokers via Both Tobacco and VNP', '20-59', 'NONE', 50, 1);
INSERT INTO `indicator` VALUES (291, 6, 'NCD_1C', 'NCD', '1. PhilPEN Risk Assessment', 'Adults provided with Brief Tobacco Intervention', '20-59', 'NONE', 60, 1);
INSERT INTO `indicator` VALUES (292, 6, 'NCD_1D', 'NCD', '1. PhilPEN Risk Assessment', 'Adults identified as binge drinkers', '20-59', 'NONE', 70, 1);
INSERT INTO `indicator` VALUES (293, 6, 'NCD_1E', 'NCD', '1. PhilPEN Risk Assessment', 'Adults with insufficient physical activities', '20-59', 'NONE', 80, 1);
INSERT INTO `indicator` VALUES (294, 6, 'NCD_1F', 'NCD', '1. PhilPEN Risk Assessment', 'Adults who consumed unhealthy diet', '20-59', 'NONE', 90, 1);
INSERT INTO `indicator` VALUES (295, 6, 'NCD_1G', 'NCD', '1. PhilPEN Risk Assessment', 'Adults who are overweight', '20-59', 'NONE', 100, 1);
INSERT INTO `indicator` VALUES (296, 6, 'NCD_1H', 'NCD', '1. PhilPEN Risk Assessment', 'Adults who are obese', '20-59', 'NONE', 110, 1);
INSERT INTO `indicator` VALUES (297, 6, 'NCD_1I', 'NCD', '1. PhilPEN Risk Assessment', 'Senior Citizens (60+) risk assessed using PhilPEN protocol', '60+', 'NONE', 120, 1);
INSERT INTO `indicator` VALUES (298, 6, 'NCD_1J', 'NCD', '1. PhilPEN Risk Assessment', 'Senior Citizens identified as current smokers', '60+', 'NONE', 130, 1);
INSERT INTO `indicator` VALUES (299, 6, 'NCD_1J1', 'NCD', '1. PhilPEN Risk Assessment', 'Senior Citizens - smokers via Tobacco product', '60+', 'NONE', 140, 1);
INSERT INTO `indicator` VALUES (300, 6, 'NCD_1J2', 'NCD', '1. PhilPEN Risk Assessment', 'Senior Citizens - smokers via Vaporized Nicotine Products', '60+', 'NONE', 150, 1);
INSERT INTO `indicator` VALUES (301, 6, 'NCD_1J3', 'NCD', '1. PhilPEN Risk Assessment', 'Senior Citizens - smokers via Both Tobacco and VNP', '60+', 'NONE', 160, 1);
INSERT INTO `indicator` VALUES (302, 6, 'NCD_1K', 'NCD', '1. PhilPEN Risk Assessment', 'Senior Citizens provided with Brief Tobacco Intervention', '60+', 'NONE', 170, 1);
INSERT INTO `indicator` VALUES (303, 6, 'NCD_1L', 'NCD', '1. PhilPEN Risk Assessment', 'Senior Citizens identified as binge drinkers', '60+', 'NONE', 180, 1);
INSERT INTO `indicator` VALUES (304, 6, 'NCD_1M', 'NCD', '1. PhilPEN Risk Assessment', 'Senior Citizens with insufficient physical activities', '60+', 'NONE', 190, 1);
INSERT INTO `indicator` VALUES (305, 6, 'NCD_1N', 'NCD', '1. PhilPEN Risk Assessment', 'Senior Citizens who consumed unhealthy diet', '60+', 'NONE', 200, 1);
INSERT INTO `indicator` VALUES (306, 6, 'NCD_1O', 'NCD', '1. PhilPEN Risk Assessment', 'Senior Citizens who are overweight', '60+', 'NONE', 210, 1);
INSERT INTO `indicator` VALUES (307, 6, 'NCD_1P', 'NCD', '1. PhilPEN Risk Assessment', 'Senior Citizens who are obese', '60+', 'NONE', 220, 1);
INSERT INTO `indicator` VALUES (308, 6, 'NCD_2A', 'NCD', '2. Cardiovascular Disease', 'Adults identified as hypertensive using PhilPEN protocol', '20-59', 'NONE', 230, 1);
INSERT INTO `indicator` VALUES (309, 6, 'NCD_2B', 'NCD', '2. Cardiovascular Disease', 'Adults identified as hypertensive with complete antihypertensive medications', '20-59', 'NONE', 240, 1);
INSERT INTO `indicator` VALUES (310, 6, 'NCD_2B1', 'NCD', '2. Cardiovascular Disease', 'Hypertensive adults - medications provided by facility (100%)', '20-59', 'NONE', 250, 1);
INSERT INTO `indicator` VALUES (311, 6, 'NCD_2B2', 'NCD', '2. Cardiovascular Disease', 'Hypertensive adults - medications out of pocket', '20-59', 'NONE', 260, 1);
INSERT INTO `indicator` VALUES (312, 6, 'NCD_2B3', 'NCD', '2. Cardiovascular Disease', 'Hypertensive adults - medications both facility and out of pocket', '20-59', 'NONE', 270, 1);
INSERT INTO `indicator` VALUES (313, 6, 'NCD_2C', 'NCD', '2. Cardiovascular Disease', 'Senior Citizens identified as hypertensive using PhilPEN protocol', '60+', 'NONE', 280, 1);
INSERT INTO `indicator` VALUES (314, 6, 'NCD_2D', 'NCD', '2. Cardiovascular Disease', 'Senior Citizens hypertensive with complete antihypertensive medications', '60+', 'NONE', 290, 1);
INSERT INTO `indicator` VALUES (315, 6, 'NCD_2D1', 'NCD', '2. Cardiovascular Disease', 'SC hypertensive - medications provided by facility (100%)', '60+', 'NONE', 300, 1);
INSERT INTO `indicator` VALUES (316, 6, 'NCD_2D2', 'NCD', '2. Cardiovascular Disease', 'SC hypertensive - medications out of pocket', '60+', 'NONE', 310, 1);
INSERT INTO `indicator` VALUES (317, 6, 'NCD_2D3', 'NCD', '2. Cardiovascular Disease', 'SC hypertensive - medications both facility and out of pocket', '60+', 'NONE', 320, 1);
INSERT INTO `indicator` VALUES (318, 6, 'NCD_3A', 'NCD', '3. Diabetes Mellitus', 'Adults identified with Type II DM using PhilPEN protocol', '20-59', 'NONE', 330, 1);
INSERT INTO `indicator` VALUES (319, 6, 'NCD_3B', 'NCD', '3. Diabetes Mellitus', 'Adults with Type II DM with complete antidiabetic medications', '20-59', 'NONE', 340, 1);
INSERT INTO `indicator` VALUES (320, 6, 'NCD_3B1', 'NCD', '3. Diabetes Mellitus', 'DM adults - medications provided by facility (100%)', '20-59', 'NONE', 350, 1);
INSERT INTO `indicator` VALUES (321, 6, 'NCD_3B2', 'NCD', '3. Diabetes Mellitus', 'DM adults - medications out of pocket', '20-59', 'NONE', 360, 1);
INSERT INTO `indicator` VALUES (322, 6, 'NCD_3B3', 'NCD', '3. Diabetes Mellitus', 'DM adults - medications both facility and out of pocket', '20-59', 'NONE', 370, 1);
INSERT INTO `indicator` VALUES (323, 6, 'NCD_3C', 'NCD', '3. Diabetes Mellitus', 'Senior Citizens identified with Type II DM using PhilPEN', '60+', 'NONE', 380, 1);
INSERT INTO `indicator` VALUES (324, 6, 'NCD_3D', 'NCD', '3. Diabetes Mellitus', 'Senior Citizens with Type II DM with complete antidiabetic medications', '60+', 'NONE', 390, 1);
INSERT INTO `indicator` VALUES (325, 6, 'NCD_3D1', 'NCD', '3. Diabetes Mellitus', 'SC DM - medications provided by facility (100%)', '60+', 'NONE', 400, 1);
INSERT INTO `indicator` VALUES (326, 6, 'NCD_3D2', 'NCD', '3. Diabetes Mellitus', 'SC DM - medications out of pocket', '60+', 'NONE', 410, 1);
INSERT INTO `indicator` VALUES (327, 6, 'NCD_3D3', 'NCD', '3. Diabetes Mellitus', 'SC DM - medications both facility and out of pocket', '60+', 'NONE', 420, 1);
INSERT INTO `indicator` VALUES (328, 6, 'NCD_4A1', 'NCD', '4. Blindness Prevention', 'Screened for eye ailment - 0-9 years old', '0-9', 'NONE', 430, 1);
INSERT INTO `indicator` VALUES (329, 6, 'NCD_4A2', 'NCD', '4. Blindness Prevention', 'Screened for eye ailment - 10-19 years old', '10-19', 'NONE', 440, 1);
INSERT INTO `indicator` VALUES (330, 6, 'NCD_4A3', 'NCD', '4. Blindness Prevention', 'Screened for eye ailment - 20-59 years old', '20-59', 'NONE', 450, 1);
INSERT INTO `indicator` VALUES (331, 6, 'NCD_4A4', 'NCD', '4. Blindness Prevention', 'Screened for eye ailment - 60 years old and above', '60+', 'NONE', 460, 1);
INSERT INTO `indicator` VALUES (332, 6, 'NCD_4B1', 'NCD', '4. Blindness Prevention', 'Screened and identified with eye ailment - 0-9 years old', '0-9', 'NONE', 470, 1);
INSERT INTO `indicator` VALUES (333, 6, 'NCD_4B2', 'NCD', '4. Blindness Prevention', 'Screened and identified with eye ailment - 10-19 years old', '10-19', 'NONE', 480, 1);
INSERT INTO `indicator` VALUES (334, 6, 'NCD_4B3', 'NCD', '4. Blindness Prevention', 'Screened and identified with eye ailment - 20-59 years old', '20-59', 'NONE', 490, 1);
INSERT INTO `indicator` VALUES (335, 6, 'NCD_4B4', 'NCD', '4. Blindness Prevention', 'Screened and identified with eye ailment - 60 years old and above', '60+', 'NONE', 500, 1);
INSERT INTO `indicator` VALUES (336, 6, 'NCD_4C1', 'NCD', '4. Blindness Prevention', 'Identified with eye disease and referred to eye care - 0-9 years old', '0-9', 'NONE', 510, 1);
INSERT INTO `indicator` VALUES (337, 6, 'NCD_4C2', 'NCD', '4. Blindness Prevention', 'Identified with eye disease and referred to eye care - 10-19 years old', '10-19', 'NONE', 520, 1);
INSERT INTO `indicator` VALUES (338, 6, 'NCD_4C3', 'NCD', '4. Blindness Prevention', 'Identified with eye disease and referred to eye care - 20-59 years old', '20-59', 'NONE', 530, 1);
INSERT INTO `indicator` VALUES (339, 6, 'NCD_4C4', 'NCD', '4. Blindness Prevention', 'Identified with eye disease and referred to eye care - 60 years old and above', '60+', 'NONE', 540, 1);
INSERT INTO `indicator` VALUES (340, 6, 'NCD_5A', 'NCD', '5. Senior Citizen Immunization', 'Senior citizens (60+) who received Pneumococcal Polysaccharide Vaccine', '60+', 'NONE', 550, 1);
INSERT INTO `indicator` VALUES (341, 6, 'NCD_5B', 'NCD', '5. Senior Citizen Immunization', 'Senior citizens (60+) who received Influenza Vaccine', '60+', 'NONE', 560, 1);
INSERT INTO `indicator` VALUES (342, 6, 'NCD_6A1', 'NCD', '6. Cervical Cancer', 'Women (30-65) screened using VIA', '30-65', 'NONE', 570, 1);
INSERT INTO `indicator` VALUES (343, 6, 'NCD_6A2', 'NCD', '6. Cervical Cancer', 'Women (30-65) screened using PapSmear', '30-65', 'NONE', 580, 1);
INSERT INTO `indicator` VALUES (344, 6, 'NCD_6A3', 'NCD', '6. Cervical Cancer', 'Women (30-65) screened using HPV DNA', '30-65', 'NONE', 590, 1);
INSERT INTO `indicator` VALUES (345, 6, 'NCD_6A4', 'NCD', '6. Cervical Cancer', 'Women (30-65) assessed only', '30-65', 'NONE', 600, 1);
INSERT INTO `indicator` VALUES (346, 6, 'NCD_6B', 'NCD', '6. Cervical Cancer', 'Women (30-65) found suspicious for cervical cancer', '30-65', 'NONE', 610, 1);
INSERT INTO `indicator` VALUES (347, 6, 'NCD_6C1', 'NCD', '6. Cervical Cancer', 'Suspicious cases linked to care - Treated', '30-65', 'NONE', 620, 1);
INSERT INTO `indicator` VALUES (348, 6, 'NCD_6C2', 'NCD', '6. Cervical Cancer', 'Suspicious cases linked to care - Referred', '30-65', 'NONE', 630, 1);
INSERT INTO `indicator` VALUES (349, 6, 'NCD_6D', 'NCD', '6. Cervical Cancer', 'Women (30-65) found positive for precancerous lesions', '30-65', 'NONE', 640, 1);
INSERT INTO `indicator` VALUES (350, 6, 'NCD_6E1', 'NCD', '6. Cervical Cancer', 'Precancerous lesions linked to care - Treated', '30-65', 'NONE', 650, 1);
INSERT INTO `indicator` VALUES (351, 6, 'NCD_6E2', 'NCD', '6. Cervical Cancer', 'Precancerous lesions linked to care - Referred', '30-65', 'NONE', 660, 1);
INSERT INTO `indicator` VALUES (352, 6, 'NCD_7A1', 'NCD', '7. Breast Cancer', 'High-risk women (30-69) provided Clinical Breast Examination', '30-69', 'NONE', 670, 1);
INSERT INTO `indicator` VALUES (353, 6, 'NCD_7A2', 'NCD', '7. Breast Cancer', 'High-risk women (30-69) provided Mammogram/Ultrasound', '30-69', 'NONE', 680, 1);
INSERT INTO `indicator` VALUES (354, 6, 'NCD_7B1', 'NCD', '7. Breast Cancer', 'High-risk women (30-69) with remarkable CBE results', '30-69', 'NONE', 690, 1);
INSERT INTO `indicator` VALUES (355, 6, 'NCD_7B2', 'NCD', '7. Breast Cancer', 'High-risk women (30-69) with remarkable Mammogram/Ultrasound results', '30-69', 'NONE', 700, 1);
INSERT INTO `indicator` VALUES (356, 6, 'NCD_7C', 'NCD', '7. Breast Cancer', 'High-risk women with remarkable results linked to care', '30-69', 'NONE', 710, 1);
INSERT INTO `indicator` VALUES (357, 6, 'NCD_7D1', 'NCD', '7. Breast Cancer', 'Asymptomatic women (50-69) screened - Clinical Breast Examination', '50-69', 'NONE', 720, 1);
INSERT INTO `indicator` VALUES (358, 6, 'NCD_7D2', 'NCD', '7. Breast Cancer', 'Asymptomatic women (50-69) screened - Mammogram/Ultrasound', '50-69', 'NONE', 730, 1);
INSERT INTO `indicator` VALUES (359, 6, 'NCD_7E1', 'NCD', '7. Breast Cancer', 'Asymptomatic women (50-69) with remarkable CBE results', '50-69', 'NONE', 740, 1);
INSERT INTO `indicator` VALUES (360, 6, 'NCD_7E2', 'NCD', '7. Breast Cancer', 'Asymptomatic women (50-69) with remarkable Mammogram/Ultrasound results', '50-69', 'NONE', 750, 1);
INSERT INTO `indicator` VALUES (361, 6, 'NCD_7F1', 'NCD', '7. Breast Cancer', 'Asymptomatic women (50-69) remarkable CBE results linked to care', '50-69', 'NONE', 760, 1);
INSERT INTO `indicator` VALUES (362, 6, 'NCD_7F2', 'NCD', '7. Breast Cancer', 'Asymptomatic women (50-69) remarkable Mammogram results linked to care', '50-69', 'NONE', 770, 1);
INSERT INTO `indicator` VALUES (363, 6, 'NCD_8', 'NCD', '8. Mental Health (mhGAP)', 'Mental Health cases managed using mhGAP', 'NONE', 'NONE', 780, 1);

-- ----------------------------
-- Table structure for report_period
-- ----------------------------
DROP TABLE IF EXISTS `report_period`;
CREATE TABLE `report_period`  (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `year` smallint NOT NULL,
  `month` tinyint NOT NULL,
  `period_label` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci GENERATED ALWAYS AS (concat(lpad(`month`,2,'0'),'-',`year`)) STORED,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `uq_period`(`year` ASC, `month` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 38 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of report_period
-- ----------------------------
INSERT INTO `report_period` (`id`, `year`, `month`) VALUES (1, 2026, 6);
INSERT INTO `report_period` (`id`, `year`, `month`) VALUES (2, 2026, 1);
INSERT INTO `report_period` (`id`, `year`, `month`) VALUES (3, 2026, 2);
INSERT INTO `report_period` (`id`, `year`, `month`) VALUES (4, 2026, 3);
INSERT INTO `report_period` (`id`, `year`, `month`) VALUES (5, 2026, 4);
INSERT INTO `report_period` (`id`, `year`, `month`) VALUES (6, 2026, 5);
INSERT INTO `report_period` (`id`, `year`, `month`) VALUES (8, 2026, 7);
INSERT INTO `report_period` (`id`, `year`, `month`) VALUES (9, 2026, 8);
INSERT INTO `report_period` (`id`, `year`, `month`) VALUES (10, 2026, 9);
INSERT INTO `report_period` (`id`, `year`, `month`) VALUES (11, 2026, 10);
INSERT INTO `report_period` (`id`, `year`, `month`) VALUES (12, 2026, 11);
INSERT INTO `report_period` (`id`, `year`, `month`) VALUES (13, 2026, 12);

-- ----------------------------
-- Table structure for report_submission
-- ----------------------------
DROP TABLE IF EXISTS `report_submission`;
CREATE TABLE `report_submission`  (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `barangay_id` int UNSIGNED NOT NULL,
  `period_id` int UNSIGNED NOT NULL,
  `program_id` int UNSIGNED NOT NULL,
  `report_code` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT 'Format: BRGY-PROGRAM-YYYYMM (e.g. AGG-FP-202612)',
  `status` enum('draft','submitted','validated','rejected') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'draft',
  `submitted_by` int UNSIGNED NULL DEFAULT NULL COMMENT 'FK → users.id (nurse/midwife who submitted)',
  `submitted_at` datetime NULL DEFAULT NULL,
  `validated_by` int UNSIGNED NULL DEFAULT NULL COMMENT 'FK → users.id (admin/doctor who validated)',
  `validated_at` datetime NULL DEFAULT NULL,
  `remarks` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `uq_submission`(`barangay_id` ASC, `period_id` ASC, `program_id` ASC) USING BTREE,
  INDEX `fk_sub_period`(`period_id` ASC) USING BTREE,
  INDEX `fk_sub_program`(`program_id` ASC) USING BTREE,
  INDEX `fk_sub_submitted`(`submitted_by` ASC) USING BTREE,
  INDEX `fk_sub_validated`(`validated_by` ASC) USING BTREE,
  UNIQUE INDEX `idx_report_submission_code`(`report_code` ASC) USING BTREE,
  CONSTRAINT `fk_sub_brgy` FOREIGN KEY (`barangay_id`) REFERENCES `barangay` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_sub_period` FOREIGN KEY (`period_id`) REFERENCES `report_period` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_sub_program` FOREIGN KEY (`program_id`) REFERENCES `health_program` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_sub_submitted` FOREIGN KEY (`submitted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT,
  CONSTRAINT `fk_sub_validated` FOREIGN KEY (`validated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 29 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of report_submission
-- ----------------------------
INSERT INTO `report_submission` VALUES (10, 1, 13, 2, 'AGG-MATERNAL-202612', 'rejected', 5, '2026-06-18 10:57:28', 4, '2026-06-22 09:48:50', 'for revisions', '2026-06-18 10:55:18', '2026-06-22 10:25:30');
INSERT INTO `report_submission` VALUES (11, 1, 12, 3, NULL, 'draft', NULL, NULL, NULL, NULL, NULL, '2026-06-19 09:28:12', '2026-06-19 10:00:14');
INSERT INTO `report_submission` VALUES (19, 1, 13, 5, NULL, 'draft', NULL, NULL, NULL, NULL, NULL, '2026-06-19 14:31:55', '2026-06-19 14:31:55');
INSERT INTO `report_submission` VALUES (22, 1, 13, 4, NULL, 'draft', NULL, NULL, NULL, NULL, NULL, '2026-06-22 08:54:38', '2026-06-22 08:54:38');
INSERT INTO `report_submission` VALUES (25, 1, 13, 1, NULL, 'validated', 15, '2026-06-22 10:25:51', 16, '2026-06-22 10:25:51', 'Clinical review: data looks consistent.', '2026-06-22 10:25:51', '2026-06-22 10:25:51');
INSERT INTO `report_submission` VALUES (26, 1, 13, 6, 'AGG-NCD-202612', 'submitted', 5, '2026-06-22 10:26:58', NULL, NULL, NULL, '2026-06-22 10:26:29', '2026-06-22 10:26:58');
INSERT INTO `report_submission` VALUES (27, 1, 1, 4, 'AGG-FP-202606', 'validated', 5, '2026-06-30 10:35:49', 4, '2026-06-22 10:41:24', NULL, '2026-06-30 10:35:19', '2026-06-22 10:41:24');
INSERT INTO `report_submission` VALUES (28, 1, 12, 1, NULL, 'draft', 5, NULL, NULL, NULL, NULL, '2026-06-22 10:45:08', '2026-06-22 10:45:08');

-- ----------------------------
-- Table structure for indicator_value
-- ----------------------------
DROP TABLE IF EXISTS `indicator_value`;
CREATE TABLE `indicator_value`  (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `submission_id` int UNSIGNED NOT NULL,
  `indicator_id` int UNSIGNED NOT NULL,
  `sex` enum('M','F','T') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT 'NULL for non-sex-disaggregated indicators',
  `age_group` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT 'NULL when no age breakdown',
  `value` decimal(10, 2) NOT NULL DEFAULT 0.00,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `uq_value`(`submission_id` ASC, `indicator_id` ASC, `sex` ASC, `age_group` ASC) USING BTREE,
  INDEX `fk_val_indicator`(`indicator_id` ASC) USING BTREE,
  CONSTRAINT `fk_val_indicator` FOREIGN KEY (`indicator_id`) REFERENCES `indicator` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_val_submission` FOREIGN KEY (`submission_id`) REFERENCES `report_submission` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 6676 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of indicator_value
-- ----------------------------

-- ----------------------------
-- Table structure for report_target
-- ----------------------------
DROP TABLE IF EXISTS `report_target`;
CREATE TABLE `report_target`  (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `barangay_id` int UNSIGNED NOT NULL,
  `period_id` int UNSIGNED NOT NULL,
  `indicator_id` int UNSIGNED NOT NULL,
  `target_value` decimal(10, 2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `uq_target`(`barangay_id` ASC, `period_id` ASC, `indicator_id` ASC) USING BTREE,
  INDEX `fk_tgt_period`(`period_id` ASC) USING BTREE,
  INDEX `fk_tgt_indicator`(`indicator_id` ASC) USING BTREE,
  CONSTRAINT `fk_tgt_brgy` FOREIGN KEY (`barangay_id`) REFERENCES `barangay` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_tgt_indicator` FOREIGN KEY (`indicator_id`) REFERENCES `indicator` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_tgt_period` FOREIGN KEY (`period_id`) REFERENCES `report_period` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of report_target
-- ----------------------------

-- ----------------------------
-- Table structure for user_barangay
-- ----------------------------
DROP TABLE IF EXISTS `user_barangay`;
CREATE TABLE `user_barangay`  (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int UNSIGNED NOT NULL,
  `barangay_id` int UNSIGNED NOT NULL,
  `assigned_at` datetime NOT NULL DEFAULT current_timestamp,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `uq_user_barangay`(`user_id` ASC, `barangay_id` ASC) USING BTREE,
  INDEX `fk_ub_barangay`(`barangay_id` ASC) USING BTREE,
  CONSTRAINT `fk_ub_barangay` FOREIGN KEY (`barangay_id`) REFERENCES `barangay` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `fk_ub_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 6 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = 'Maps nurses/midwives to their assigned barangay(s).' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of user_barangay
-- ----------------------------
INSERT INTO `user_barangay` VALUES (4, 5, 1, '2026-06-21 19:04:10');
INSERT INTO `user_barangay` VALUES (5, 15, 1, '2026-06-22 10:25:51');

-- ----------------------------
-- Table structure for users
-- ----------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users`  (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `password_hash` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Bcrypt or Argon2 hash — never plaintext',
  `role` enum('superadmin','admin','doctor','nurse') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'nurse',
  `full_name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `position` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT 'e.g. Barangay Midwife, Rural Health Physician',
  `phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `profile_image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT 'Relative path to uploaded profile photo',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_logged_in` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 = user is currently logged in; prevents double login',
  `last_login_at` datetime NULL DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `uq_username`(`username` ASC) USING BTREE,
  UNIQUE INDEX `uq_email`(`email` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 18 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = 'System users. Nurses/midwives are scoped to barangays via user_barangay.' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of users
-- ----------------------------
INSERT INTO `users` VALUES (1, 'superadmin', 'admin@mho-cauayan.gov.ph', '$2y$10$wcL2XE2XcXVsDjGPbtmwlO8TmBobgvnUBcOAqWbyb2XltZvcDmp/m', 'superadmin', 'System Administrator', 'MHO System Administrator', NULL, NULL, 1, 0, '2026-06-17 11:57:23', '2026-06-17 11:37:51', '2026-06-22 10:25:50');
INSERT INTO `users` VALUES (3, 'adminsuper', 'superadmin@gmail.com', '$2y$10$4iIX6ReO5rowetZUQjXfw.fdjwdqEjxH3QF6ycULiSLZ2tAtLFoHy', 'superadmin', 'Super Admin', '', '0912313123123', NULL, 1, 0, NULL, '2026-06-17 11:52:57', '2026-06-17 11:56:54');
INSERT INTO `users` VALUES (4, 'testuser', 'TEST@gmail.com', '$2y$10$HuhjUyBBxlenUh2G4KARauJVzIuhTd1G0Htmv2qCaG5enoTDrU/im', 'superadmin', 'TEST', 'test', '0987654', NULL, 1, 1, '2026-06-22 09:52:59', '2026-06-17 11:54:29', '2026-06-22 09:52:59');
INSERT INTO `users` VALUES (5, 'aggub', 'daimoon175@gmail.com', '$2y$10$S3nGLI/Yj3FZpmd24O15gugrMGlwhZaDM.KdNgC9deyxZUG/8i9bW', 'nurse', 'test', 'test', '1234567', 'uploads/profiles/user_5_1782039850.jpg', 1, 1, '2026-06-22 09:55:43', '2026-06-17 12:58:18', '2026-06-22 09:55:43');
INSERT INTO `users` VALUES (14, 'testdoctor', 'jhonllanderpangangaan@gmail.com', '$2y$10$wzvqHmf9UiPv/AIxmvdzl.dJlIddD.Kjf7F/fB/ufyhOPbcITMURC', 'doctor', 'testdoctor', 'testdoc', '2123456', 'uploads/profiles/user_14_1782088018.jpg', 1, 0, '2026-06-19 16:23:54', '2026-06-19 14:46:35', '2026-06-22 08:26:58');
INSERT INTO `users` VALUES (15, 'nurse1', 'nurse@test.ph', '$2y$10$zF3tNlZSULU4wC0QKg3qfec1.VGC05TCBm5MWGqjBz95VCetoMUUS', 'nurse', 'Test Nurse', 'Test', NULL, NULL, 1, 0, NULL, '2026-06-22 10:25:50', '2026-06-22 10:25:50');
INSERT INTO `users` VALUES (16, 'admin1', 'admin@test.ph', '$2y$10$rXDsjMPproQwPY6x.TaheeREcbMObCncsoChDChZVJr8fVEx5.7Ia', 'admin', 'Test Admin', 'Test', NULL, NULL, 1, 0, NULL, '2026-06-22 10:25:50', '2026-06-22 10:25:50');
INSERT INTO `users` VALUES (17, 'doctor1', 'doctor@test.ph', '$2y$10$/rbmx0jl1tZmUuxwE06YZ.dRffd7fYgFWihBqeZfrogoelRXPYIPK', 'doctor', 'Test Doctor', 'Test', NULL, NULL, 1, 0, NULL, '2026-06-22 10:25:51', '2026-06-22 10:25:51');

-- ----------------------------
-- View structure for vw_indicator_values_full
-- ----------------------------
DROP VIEW IF EXISTS `vw_indicator_values_full`;
CREATE ALGORITHM = UNDEFINED SQL SECURITY DEFINER VIEW `vw_indicator_values_full` AS SELECT
    b.name           AS barangay,
    rp.year,
    rp.month,
    hp.code          AS program_code,
    hp.name          AS program,
    i.code           AS indicator_code,
    i.part,
    i.category,
    i.description    AS indicator,
    iv.sex,
    iv.age_group,
    iv.value,
    rs.status        AS report_status
FROM indicator_value iv
JOIN report_submission rs ON rs.id  = iv.submission_id
JOIN barangay          b  ON b.id   = rs.barangay_id
JOIN report_period     rp ON rp.id  = rs.period_id
JOIN health_program    hp ON hp.id  = rs.program_id
JOIN indicator          i ON i.id   = iv.indicator_id;

-- ----------------------------
-- View structure for vw_submission_summary
-- ----------------------------
DROP VIEW IF EXISTS `vw_submission_summary`;
CREATE ALGORITHM = UNDEFINED SQL SECURITY DEFINER VIEW `vw_submission_summary` AS SELECT
    rs.id            AS submission_id,
    b.name           AS barangay,
    rp.year,
    rp.month,
    hp.name          AS program,
    rs.status,
    rs.submitted_by,
    rs.submitted_at,
    COUNT(iv.id)     AS indicator_count
FROM report_submission rs
JOIN barangay        b  ON b.id  = rs.barangay_id
JOIN report_period   rp ON rp.id = rs.period_id
JOIN health_program  hp ON hp.id = rs.program_id
LEFT JOIN indicator_value iv ON iv.submission_id = rs.id
GROUP BY rs.id, b.name, rp.year, rp.month, hp.name, rs.status, rs.submitted_by, rs.submitted_at;

-- ----------------------------
-- View structure for vw_target_vs_actual
-- ----------------------------
DROP VIEW IF EXISTS `vw_target_vs_actual`;
CREATE ALGORITHM = UNDEFINED SQL SECURITY DEFINER VIEW `vw_target_vs_actual` AS SELECT
    b.name           AS barangay,
    rp.year,
    rp.month,
    hp.code          AS program_code,
    i.code           AS indicator_code,
    i.description    AS indicator,
    COALESCE(rt.target_value, 0) AS target,
    COALESCE(SUM(CASE WHEN iv.sex = 'T' OR i.sex_disaggregation = 'NONE' THEN iv.value ELSE 0 END), 0) AS actual,
    ROUND(
        COALESCE(SUM(CASE WHEN iv.sex = 'T' OR i.sex_disaggregation = 'NONE' THEN iv.value ELSE 0 END), 0)
        / NULLIF(rt.target_value, 0) * 100, 1
    ) AS pct_accomplished
FROM indicator i
JOIN health_program hp ON hp.id = i.program_id
LEFT JOIN report_target rt ON rt.indicator_id = i.id
LEFT JOIN report_period rp ON rp.id = rt.period_id
LEFT JOIN barangay b ON b.id = rt.barangay_id
LEFT JOIN report_submission rs ON rs.barangay_id = b.id AND rs.period_id = rp.id AND rs.program_id = hp.id
LEFT JOIN indicator_value iv ON iv.submission_id = rs.id AND iv.indicator_id = i.id
GROUP BY b.name, rp.year, rp.month, hp.code, i.code, i.description, rt.target_value;

-- ----------------------------
-- View structure for vw_users_summary
-- ----------------------------
DROP VIEW IF EXISTS `vw_users_summary`;
CREATE ALGORITHM = UNDEFINED SQL SECURITY DEFINER VIEW `vw_users_summary` AS SELECT
    u.id,
    u.username,
    u.full_name,
    u.role,
    u.position,
    u.email,
    u.phone,
    u.is_active,
    u.last_login_at,
    GROUP_CONCAT(b.name ORDER BY b.name SEPARATOR ', ') AS assigned_barangays
FROM users u
LEFT JOIN user_barangay ub ON ub.user_id = u.id
LEFT JOIN barangay       b  ON b.id = ub.barangay_id
GROUP BY u.id, u.username, u.full_name, u.role, u.position,
         u.email, u.phone, u.is_active, u.last_login_at;

SET FOREIGN_KEY_CHECKS = 1;

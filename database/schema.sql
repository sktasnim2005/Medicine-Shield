-- ==========================================================
-- Medicine-Shield (Oushodh-Shield) Database Schema
-- Counterfeit Drug Verification & Serialization Ledger System
-- Smart Context, In-Store Browsing & Post-Purchase Claim
-- 100% Standard SQL for MySQL 8.0+ / MariaDB / MySQL Workbench
-- ==========================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- 1. Create and Select Database
CREATE DATABASE IF NOT EXISTS `medicine_shield` 
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `medicine_shield`;

-- ----------------------------------------------------------
-- 2. Users & Multi-Role Authentication Table
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(150) NOT NULL,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `role` ENUM('citizen', 'manufacturer', 'dgda') NOT NULL DEFAULT 'citizen',
    `manufacturer_id` INT DEFAULT NULL,
    `badge_or_license_no` VARCHAR(100) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_users_role` (`role`),
    INDEX `idx_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 3. Manufacturers Table
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `manufacturers`;
CREATE TABLE `manufacturers` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(150) NOT NULL,
    `code` VARCHAR(50) NOT NULL UNIQUE,
    `dgda_license_no` VARCHAR(100) NOT NULL,
    `contact_email` VARCHAR(120) DEFAULT NULL,
    `contact_phone` VARCHAR(30) DEFAULT NULL,
    `headquarters` VARCHAR(255) DEFAULT 'Dhaka, Bangladesh',
    `is_verified` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 4. Medicines Catalog Table (Merits, Demerits, Dosage, AI Reference)
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `medicines`;
CREATE TABLE `medicines` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `manufacturer_id` INT NOT NULL,
    `brand_name` VARCHAR(150) NOT NULL,
    `generic_name` VARCHAR(150) NOT NULL,
    `strength` VARCHAR(50) NOT NULL,
    `dosage_form` ENUM('Tablet', 'Capsule', 'Syrup', 'Injection', 'Suspension', 'Eye Drops') NOT NULL DEFAULT 'Tablet',
    `dar_number` VARCHAR(100) NOT NULL COMMENT 'Drug Administration Registration (DAR) No',
    `mrp_bdt` DECIMAL(10,2) NOT NULL,
    `pack_size` VARCHAR(50) DEFAULT '10 x 10 Strip',
    `indications_merits` TEXT NOT NULL COMMENT 'Benefits and clinical indications',
    `side_effects_demerits` TEXT NOT NULL COMMENT 'Possible side effects and adverse reactions',
    `dosage_instructions` TEXT NOT NULL COMMENT 'Standard usage instructions',
    `precautions` TEXT DEFAULT NULL COMMENT 'Contraindications and precautions',
    `primary_color_hex` VARCHAR(20) DEFAULT '#0284c7' COMMENT 'Primary packaging color for AI visual check',
    `packaging_ref_image` VARCHAR(255) DEFAULT NULL COMMENT 'Reference packaging image for AI inspection',
    `approval_status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'approved',
    `approved_at` DATETIME DEFAULT NULL,
    `approved_by` VARCHAR(150) DEFAULT NULL,
    `dgda_remarks` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_medicines_manufacturer` FOREIGN KEY (`manufacturer_id`) REFERENCES `manufacturers`(`id`) ON DELETE CASCADE,
    INDEX `idx_medicines_approval` (`approval_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 5. Batches Table
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `batches`;
CREATE TABLE `batches` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `medicine_id` INT NOT NULL,
    `batch_number` VARCHAR(100) NOT NULL UNIQUE,
    `manufacturing_date` DATE NOT NULL,
    `expiry_date` DATE NOT NULL,
    `total_units` INT NOT NULL DEFAULT 1000,
    `production_facility` VARCHAR(200) DEFAULT 'Main Plant, Gazipur, Bangladesh',
    `status` ENUM('Active', 'Recalled', 'Expired') NOT NULL DEFAULT 'Active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_batches_medicine` FOREIGN KEY (`medicine_id`) REFERENCES `medicines`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 6. Barcodes Ledger Table (Smart Context & Post-Purchase State Machine)
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `barcodes_ledger`;
CREATE TABLE `barcodes_ledger` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `barcode_serial` VARCHAR(100) NOT NULL UNIQUE COMMENT 'Unique Code128 serial string e.g. MS-2026-NAPA-7821A',
    `crypto_hash` VARCHAR(64) NOT NULL COMMENT 'SHA-256 validation token',
    `medicine_id` INT NOT NULL,
    `batch_id` INT NOT NULL,
    `scan_count` INT NOT NULL DEFAULT 0 COMMENT '0 = Unused, 1+ = Scanned',
    `lifecycle_status` ENUM('on_shelf', 'sold_claimed') NOT NULL DEFAULT 'on_shelf' COMMENT 'on_shelf = browsing permitted; sold_claimed = locked to buyer',
    `scratch_pin` VARCHAR(30) DEFAULT NULL COMMENT 'Optional scratch-off key revealed after purchase',
    `first_scanned_at` DATETIME DEFAULT NULL,
    `first_scanned_location` VARCHAR(255) DEFAULT NULL,
    `first_scanned_ip` VARCHAR(60) DEFAULT NULL,
    `first_scanned_device` VARCHAR(150) DEFAULT NULL,
    `last_scanned_at` DATETIME DEFAULT NULL,
    `last_scanned_location` VARCHAR(255) DEFAULT NULL,
    `claimed_at` DATETIME DEFAULT NULL,
    `claimed_by` VARCHAR(150) DEFAULT NULL,
    `claimed_location` VARCHAR(255) DEFAULT NULL,
    `is_flagged_counterfeit` TINYINT(1) NOT NULL DEFAULT 0,
    `flag_reason` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_barcodes_medicine` FOREIGN KEY (`medicine_id`) REFERENCES `medicines`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_barcodes_batch` FOREIGN KEY (`batch_id`) REFERENCES `batches`(`id`) ON DELETE CASCADE,
    INDEX `idx_barcode_serial` (`barcode_serial`),
    INDEX `idx_barcode_scan_count` (`scan_count`),
    INDEX `idx_barcode_lifecycle` (`lifecycle_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 7. Scan Logs Table (Audit Trail with AI Velocity / Context Status)
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `scan_logs`;
CREATE TABLE `scan_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `barcode_serial` VARCHAR(100) NOT NULL,
    `scanned_by_user` VARCHAR(100) DEFAULT 'Guest Citizen',
    `verification_status` ENUM('GENUINE', 'GENUINE_SHELF_BROWSING', 'ALREADY_SOLD_ALERT', 'CLONED_COUNTERFEIT', 'INVALID_FAKE', 'FLAGGED_RECALLED') NOT NULL,
    `ai_visual_score` DECIMAL(5,2) DEFAULT NULL COMMENT 'AI visual similarity score 0-100%',
    `scanned_location` VARCHAR(255) DEFAULT 'Dhaka, Bangladesh',
    `latitude` DECIMAL(10,7) DEFAULT NULL,
    `longitude` DECIMAL(10,7) DEFAULT NULL,
    `device_info` VARCHAR(255) DEFAULT NULL,
    `ip_address` VARCHAR(60) DEFAULT NULL,
    `scanned_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_scan_serial` (`barcode_serial`),
    INDEX `idx_scan_status` (`verification_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 8. Counterfeit Reports Table (Crowdsourced Citizen Reports)
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `counterfeit_reports`;
CREATE TABLE `counterfeit_reports` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `barcode_serial` VARCHAR(100) DEFAULT NULL,
    `medicine_name` VARCHAR(150) NOT NULL,
    `pharmacy_name` VARCHAR(200) NOT NULL,
    `pharmacy_address` VARCHAR(255) NOT NULL,
    `city` VARCHAR(100) NOT NULL DEFAULT 'Dhaka',
    `district` VARCHAR(100) NOT NULL DEFAULT 'Dhaka',
    `reporter_name` VARCHAR(100) DEFAULT 'Anonymous Guardian',
    `reporter_phone` VARCHAR(30) DEFAULT NULL,
    `description` TEXT NOT NULL,
    `evidence_image` VARCHAR(255) DEFAULT NULL,
    `ai_suspicion_level` ENUM('Low', 'Medium', 'High', 'Critical') NOT NULL DEFAULT 'High',
    `status` ENUM('Under Review', 'Investigating', 'Enforcement Dispatched', 'Raid Conducted / Seized', 'Dismissed') NOT NULL DEFAULT 'Under Review',
    `dgda_case_notes` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_report_status` (`status`),
    INDEX `idx_report_district` (`district`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 9. Rewards & Gamification Ledger
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `user_rewards`;
CREATE TABLE `user_rewards` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` VARCHAR(100) NOT NULL DEFAULT 'guest_user',
    `points` INT NOT NULL DEFAULT 100,
    `badge_title` VARCHAR(100) DEFAULT 'Health Guardian Lv. 1',
    `genuine_scans_count` INT NOT NULL DEFAULT 0,
    `fake_reports_count` INT NOT NULL DEFAULT 0,
    `last_activity_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_reward_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `reward_redemptions`;
CREATE TABLE `reward_redemptions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` VARCHAR(100) NOT NULL,
    `reward_type` ENUM('Mobile Recharge BDT 50', 'Mobile Recharge BDT 100', 'Pharmacy 10% Discount Voucher', 'Health Guardian T-Shirt') NOT NULL,
    `points_spent` INT NOT NULL,
    `voucher_code` VARCHAR(50) NOT NULL,
    `status` ENUM('Issued', 'Redeemed', 'Expired') NOT NULL DEFAULT 'Issued',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_redemptions_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================================
-- SEED DATA: Bangladeshi Pharmaceutical Ecosystem & Users
-- ==========================================================

-- Seed Initial Users (Default Password for demo: password123)
-- bcrypt hash for password123: $2y$10$eA0t4jE79yDphQ235kX0E.5aN2OQeJ00c8bL98vQk2o0r5/4z7Y3a
INSERT INTO `users` (`id`, `name`, `email`, `password_hash`, `role`, `manufacturer_id`, `badge_or_license_no`) VALUES
(1, 'Tasnimur Rahman (Citizen Guardian)', 'citizen@guardian.bd', '$2y$10$eA0t4jE79yDphQ235kX0E.5aN2OQeJ00c8bL98vQk2o0r5/4z7Y3a', 'citizen', NULL, 'CITIZEN-DHAKA-2026'),
(2, 'Square Pharma Quality Control', 'square@pharma.com.bd', '$2y$10$eA0t4jE79yDphQ235kX0E.5aN2OQeJ00c8bL98vQk2o0r5/4z7Y3a', 'manufacturer', 1, 'DGDA-MFG-00124'),
(3, 'Beximco Pharma Security Unit', 'beximco@pharma.com.bd', '$2y$10$eA0t4jE79yDphQ235kX0E.5aN2OQeJ00c8bL98vQk2o0r5/4z7Y3a', 'manufacturer', 2, 'DGDA-MFG-00088'),
(4, 'Dr. M. Rahman (DGDA Drug Inspector)', 'inspector@dgda.gov.bd', '$2y$10$eA0t4jE79yDphQ235kX0E.5aN2OQeJ00c8bL98vQk2o0r5/4z7Y3a', 'dgda', NULL, 'DGDA-INSPECT-77');

-- Insert Top Bangladeshi Manufacturers
INSERT INTO `manufacturers` (`id`, `name`, `code`, `dgda_license_no`, `contact_email`, `contact_phone`, `headquarters`) VALUES
(1, 'Square Pharmaceuticals PLC', 'SQUARE', 'DGDA-MFG-00124', 'info@squarepharma.com.bd', '+88028833047', 'Square Centre, 48 Mohakhali C/A, Dhaka-1212'),
(2, 'Beximco Pharmaceuticals Ltd.', 'BEXIMCO', 'DGDA-MFG-00088', 'info@bpl.net', '+880258611001', '19 Dhanmondi R/A, Road 7, Dhaka-1205'),
(3, 'Incepta Pharmaceuticals Ltd.', 'INCEPTA', 'DGDA-MFG-00192', 'incepta@inceptapharma.com', '+88028891688', '40 Shahid Tajuddin Ahmed Sarani, Tejgaon, Dhaka'),
(4, 'Renata Limited', 'RENATA', 'DGDA-MFG-00045', 'info@renata-ltd.com', '+88028001450', 'Plot # 1, Milk Vita Road, Section-7, Mirpur, Dhaka'),
(5, 'The ACME Laboratories Ltd.', 'ACME', 'DGDA-MFG-00031', 'headoffice@acmeglobal.com', '+88029014524', '1/4, Kallayanpur, Mirpur Road, Dhaka-1207');

-- Insert Common High-Volume Medicines
INSERT INTO `medicines` (`id`, `manufacturer_id`, `brand_name`, `generic_name`, `strength`, `dosage_form`, `dar_number`, `mrp_bdt`, `pack_size`, `indications_merits`, `side_effects_demerits`, `dosage_instructions`, `precautions`, `primary_color_hex`, `packaging_ref_image`) VALUES
(1, 1, 'Napa Extra', 'Paracetamol + Caffeine', '500mg + 65mg', 'Tablet', 'DAR-024-0312-054', 2.50, '10 x 10 Tablet Strip', 
 'Relief of mild to moderate pain including headache, migraine, toothache, neuralgia, fever, sore throat, backache, and rheumatic pain. Caffeine enhances analgesic potency.', 
 'Generally well tolerated at recommended doses. Occasional skin rash, insomnia, restlessness due to caffeine. High doses over prolonged periods may cause liver toxicity.', 
 'Adults: 1-2 tablets every 4 to 6 hours as needed. Maximum 8 tablets in 24 hours. Do not exceed recommended dosage.', 
 'Caution in patients with severe hepatic or renal impairment. Do not take with other paracetamol-containing products.', 
 '#e11d48', 'assets/images/medicines/napa_extra.svg'),

(2, 1, 'Seclo 20', 'Omeprazole', '20mg', 'Capsule', 'DAR-024-0089-012', 6.00, '10 x 10 Capsule Strip', 
 'Treatment of gastric and duodenal ulcers, gastroesophageal reflux disease (GERD), Zollinger-Ellison syndrome, and NSAID-induced ulcers. Acid reduction.', 
 'Headache, diarrhea, abdominal pain, nausea, flatulence, constipation. Prolonged use may reduce Vitamin B12 and magnesium absorption.', 
 '1 capsule (20mg) once daily in the morning before breakfast, swallowed whole with water for 2-4 weeks.', 
 'Before initiating therapy, possibility of gastric malignancy should be excluded.', 
 '#0284c7', 'assets/images/medicines/seclo_20.svg'),

(3, 5, 'Monas 10', 'Montelukast Sodium', '10mg', 'Tablet', 'DAR-031-0452-098', 16.00, '3 x 10 Box', 
 'Prophylaxis and chronic treatment of asthma in adults, prevention of exercise-induced bronchoconstriction, and relief of seasonal allergic rhinitis.', 
 'Upper respiratory infection, fever, headache, pharyngitis, cough, abdominal pain, diarrhea. Rare neuropsychiatric events (mood changes).', 
 'Adults: One 10mg tablet daily taken in the evening with or without food.', 
 'Not indicated for the reversal of bronchospasm in acute asthma attacks (including status asthmaticus).', 
 '#16a34a', 'assets/images/medicines/monas_10.svg'),

(4, 2, 'Ace Plus', 'Paracetamol + Caffeine', '500mg + 65mg', 'Tablet', 'DAR-088-0219-063', 2.50, '10 x 10 Strip', 
 'Indicated for rapid relief of headache, migraine, musculoskeletal pain, dysmenorrhea, toothache, and fever associated with cold and flu.', 
 'Low toxicity at standard doses. Skin rash, transient dizziness, caffeine-related palpitation if consumed with heavy tea/coffee.', 
 '1-2 tablets every 4-6 hours. Maximum 8 tablets in 24 hours. Swallow with a full glass of water.', 
 'Avoid chronic consumption with alcohol. Patients with liver dysfunction require physician guidance.', 
 '#2563eb', 'assets/images/medicines/ace_plus.svg'),

(5, 4, 'Maxpro 20', 'Esomeprazole Magnesium Trihydrate', '20mg', 'Tablet', 'DAR-045-0187-033', 7.00, '10 x 10 Strip', 
 'Gastroesophageal Reflux Disease (GERD), healing of erosive esophagitis, eradication of H. pylori in combination with antibiotics, acid suppression.', 
 'Headache, abdominal pain, diarrhea, flatulence, dry mouth, nausea/vomiting. Long term use may increase risk of bone fractures.', 
 '20mg or 40mg once daily at least 1 hour before meal for 4-8 weeks as prescribed by physician.', 
 'Patients with severe liver disease should not exceed 20mg daily dose.', 
 '#9333ea', 'assets/images/medicines/maxpro_20.svg'),

(6, 1, 'Ciprocin 500', 'Ciprofloxacin', '500mg', 'Tablet', 'DAR-024-0156-077', 15.00, '10 x 10 Strip', 
 'Broad-spectrum antibiotic for respiratory tract infections, urinary tract infections (UTI), infectious diarrhea, typhoid fever, and skin/soft tissue infections.', 
 'Nausea, diarrhea, abnormal liver function tests, vomiting, rash. Risk of tendinitis and tendon rupture in rare instances.', 
 '500mg every 12 hours (twice daily) for 7 to 14 days depending on infection severity. Drink plenty of water.', 
 'Avoid with dairy products alone or calcium-fortified juices. Complete full course as prescribed.', 
 '#ea580c', 'assets/images/medicines/ciprocin_500.svg'),

(7, 2, 'Bexitrol-F', 'Salmeterol + Fluticasone', '25mcg + 125mcg', 'Inhaler', 'DAR-088-0341-019', 275.00, '120 Puffs Canister', 
 'Regular treatment of asthma and symptomatic treatment of severe COPD.', 
 'Hoarseness, candidiasis of mouth and throat, headache.', 
 '2 puffs twice daily for adults.', 
 'Rinse mouth with water after inhalation.', 
 '#0284c7', NULL);

-- Mark id 7 as pending DGDA review
UPDATE `medicines` SET `approval_status` = 'pending' WHERE `id` = 7;


-- Insert Batches
INSERT INTO `batches` (`id`, `medicine_id`, `batch_number`, `manufacturing_date`, `expiry_date`, `total_units`, `production_facility`, `status`) VALUES
(1, 1, 'SQ-NPA-2026B1', '2026-01-15', '2028-01-14', 50000, 'Dhaka Unit-1, Kaliakair Plant', 'Active'),
(2, 2, 'SQ-SCL-2026A4', '2026-02-10', '2028-02-09', 35000, 'Pabna Main Production Facility', 'Active'),
(3, 3, 'AC-MNS-2025C9', '2025-11-20', '2027-11-19', 25000, 'Dhamrai Plant, Dhaka', 'Active'),
(4, 4, 'BX-ACE-2026X2', '2026-03-01', '2028-02-28', 40000, 'Tongi Plant, Gazipur', 'Active'),
(5, 5, 'RN-MXP-2026D8', '2026-01-28', '2028-01-27', 30000, 'Rajendrapur Industrial Complex', 'Active'),
(6, 6, 'SQ-CIP-2025Z1', '2025-10-05', '2027-10-04', 20000, 'Dhaka Unit-2, Kaliakair', 'Active');

-- Insert Seed Barcodes for Verification Testing
-- Demonstrates:
-- 1) Fresh Genuine (Unused, on_shelf)
-- 2) In-Store Browsing (Scanned multiple times in same pharmacy - stays GENUINE!)
-- 3) Already Sold / Claimed Medicine (Resale / refill duplicate alert)
-- 4) Cloned Barcode / Impossible Travel Anomaly (Dhaka vs Chittagong)
-- 5) Blacklisted / Recalled Raid Batch
INSERT INTO `barcodes_ledger` 
(`id`, `barcode_serial`, `crypto_hash`, `medicine_id`, `batch_id`, `scan_count`, `lifecycle_status`, `scratch_pin`, `first_scanned_at`, `first_scanned_location`, `first_scanned_ip`, `first_scanned_device`, `last_scanned_at`, `last_scanned_location`, `claimed_at`, `claimed_by`, `claimed_location`, `is_flagged_counterfeit`, `flag_reason`) 
VALUES
-- 1. Fresh Genuine (Never scanned before)
(1, 'MS-2026-NAPA-7821A', SHA2('MS-2026-NAPA-7821A-SALT2026', 256), 1, 1, 0, 'on_shelf', '9482', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(2, 'MS-2026-SECLO-9914C', SHA2('MS-2026-SECLO-9914C-SALT2026', 256), 2, 2, 0, 'on_shelf', '5510', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),

-- 2. In-Store Browsing (Scanned 3 times today at Bismillah Pharmacy, Dhanmondi - 100% Genuine Shelf Browsing!)
(3, 'MS-2026-NAPA-SHELF-102', SHA2('MS-2026-NAPA-SHELF-102-SALT2026', 256), 1, 1, 3, 'on_shelf', '7731', DATE_SUB(NOW(), INTERVAL 45 MINUTE), 'Bismillah Pharmacy, Dhanmondi 27, Dhaka', '103.114.152.12', 'Chrome / Android', DATE_SUB(NOW(), INTERVAL 5 MINUTE), 'Bismillah Pharmacy, Dhanmondi 27, Dhaka', NULL, NULL, NULL, 0, NULL),

-- 3. Already Sold & Claimed (Customer bought it and claimed ownership on April 10. Resale / duplicate alerts!)
(4, 'MS-2026-SECL-SOLD-555', SHA2('MS-2026-SECL-SOLD-555-SALT2026', 256), 2, 2, 2, 'sold_claimed', '8834', '2026-04-10 14:20:00', 'Green Life Pharmacy, Green Road, Dhaka', '118.67.214.5', 'iPhone 15', '2026-04-10 14:25:00', 'Green Life Pharmacy, Green Road, Dhaka', '2026-04-10 14:25:00', 'Tasnimur Rahman (Citizen)', 'Green Life Pharmacy, Green Road, Dhaka', 0, NULL),

-- 4. Impossible Travel / Cloned Barcode Anomaly (Scanned in Chittagong, then immediately in Dhaka)
(5, 'MS-2026-ACEP-CLONE-771', SHA2('MS-2026-ACEP-CLONE-771-SALT2026', 256), 4, 4, 4, 'on_shelf', '6629', DATE_SUB(NOW(), INTERVAL 20 MINUTE), 'Anderkilla Wholesale Drug Market, Chittagong', '118.67.214.5', 'Samsung Galaxy A54', DATE_SUB(NOW(), INTERVAL 2 MINUTE), 'Mitford Market, Old Dhaka', NULL, NULL, NULL, 0, 'AI Velocity Alert: Scanned across 250km within 20 minutes'),

-- 5. Blacklisted Batch (Seized in Police Raid)
(6, 'MS-2026-FAKE-RAID-001', SHA2('MS-2026-FAKE-RAID-001-SALT2026', 256), 1, 1, 12, 'on_shelf', '0000', '2026-03-20 09:12:00', 'Babubazar Wholesale Market, Dhaka', '180.234.41.90', 'Unknown Device', '2026-03-20 09:12:00', 'Babubazar Wholesale Market, Dhaka', NULL, NULL, NULL, 1, 'Identified in DGDA Police Raid #2026-08 (Toxic Starch Substitute)');

-- Seed Past Counterfeit Outbreak Reports
INSERT INTO `counterfeit_reports` (`barcode_serial`, `medicine_name`, `pharmacy_name`, `pharmacy_address`, `city`, `district`, `reporter_name`, `description`, `ai_suspicion_level`, `status`, `dgda_case_notes`) VALUES
('MS-2026-FAKE-RAID-001', 'Napa Extra (Counterfeit Batch)', 'Bismillah Pharmacy', 'Shop 14, Mitford Medicine Market, Kotwali', 'Old Dhaka', 'Dhaka', 'Tasnimur Rahman (Pharmacist)', 'Tablet foil packaging color looks washed out pink instead of vibrant crimson red. Font is blurry and barcode printed multiple times.', 'Critical', 'Raid Conducted / Seized', 'Joint raid with DB Police & DGDA inspector seized 12,000 fake strips on May 15. Factory sealed.'),
('MS-2026-ACEP-CLONE-771', 'Ace Plus 500mg', 'Janata Medical Hall', 'Station Road, Chittagong', 'Chittagong', 'Chittagong', 'Dr. Mahmudul Hasan', 'Patient reported sudden dizziness. When scanned, barcode had already been scanned simultaneously in Dhaka.', 'High', 'Enforcement Dispatched', 'Sample sent to DGDA laboratory for HPLC chemical potency test.'),
(NULL, 'Monas 10 mg', 'Al-Madina Drug House', 'Chawkbazar, Sylhet', 'Sylhet', 'Sylhet', 'Tasnimur Rahman (Citizen Guardian)', 'Packaging logo lacks the official embossing and holographic shine. QR/Barcode label looks photocopied.', 'High', 'Investigating', 'Field inspector assigned for sample collection.');

-- Seed Initial User Rewards
INSERT INTO `user_rewards` (`user_id`, `points`, `badge_title`, `genuine_scans_count`, `fake_reports_count`) VALUES
('1', 350, 'Vigilant Health Guardian', 5, 1),
('citizen@guardian.bd', 350, 'Vigilant Health Guardian', 5, 1);

SET FOREIGN_KEY_CHECKS = 1;

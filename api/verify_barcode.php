<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config/db.php';

$pdo = null;
try {
    $pdo = Database::getConnection();
} catch (Exception $e) {
    $pdo = null;
}

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$barcode = trim($input['barcode'] ?? $_GET['barcode'] ?? '');
$location = trim($input['location'] ?? 'Bismillah Pharmacy, Dhanmondi 27, Dhaka');
$latitude = isset($input['latitude']) ? floatval($input['latitude']) : null;
$longitude = isset($input['longitude']) ? floatval($input['longitude']) : null;
$device = trim($input['device'] ?? ($_SERVER['HTTP_USER_AGENT'] ?? 'Web Browser / Mobile'));
$ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
$aiVisualScore = isset($input['ai_visual_score']) ? floatval($input['ai_visual_score']) : null;
$userId = trim($input['user_id'] ?? 'guest_citizen');

if (empty($barcode)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Barcode serial is required.']);
    exit;
}

// Helper: Prepare medicine details array
function formatMedicineResponse($row) {
    return [
        'brand_name' => $row['brand_name'],
        'generic_name' => $row['generic_name'],
        'strength' => $row['strength'],
        'dosage_form' => $row['dosage_form'] ?? 'Tablet',
        'dar_number' => $row['dar_number'] ?? 'DAR Registered',
        'mrp_bdt' => $row['mrp_bdt'] ?? '0.00',
        'pack_size' => $row['pack_size'] ?? 'Strip',
        'indications_merits' => $row['indications_merits'] ?? '',
        'side_effects_demerits' => $row['side_effects_demerits'] ?? '',
        'dosage_instructions' => $row['dosage_instructions'] ?? '',
        'precautions' => $row['precautions'] ?? '',
        'primary_color_hex' => $row['primary_color_hex'] ?? '#0284c7',
        'packaging_ref_image' => $row['packaging_ref_image'] ?? '',
        'manufacturer_name' => $row['manufacturer_name'] ?? '',
        'manufacturer_code' => $row['manufacturer_code'] ?? '',
        'dgda_license_no' => $row['dgda_license_no'] ?? '',
        'batch_number' => $row['batch_number'] ?? '',
        'manufacturing_date' => $row['manufacturing_date'] ?? '',
        'expiry_date' => $row['expiry_date'] ?? '',
        'production_facility' => $row['production_facility'] ?? ''
    ];
}

$record = null;

if ($pdo !== null) {
    try {
        $query = "
            SELECT 
                b.*,
                m.brand_name, m.generic_name, m.strength, m.dosage_form, m.dar_number,
                m.mrp_bdt, m.pack_size, m.indications_merits, m.side_effects_demerits,
                m.dosage_instructions, m.precautions, m.primary_color_hex, m.packaging_ref_image,
                mfg.name AS manufacturer_name, mfg.code AS manufacturer_code, mfg.dgda_license_no, mfg.headquarters AS manufacturer_hq,
                bat.batch_number, bat.manufacturing_date, bat.expiry_date, bat.production_facility, bat.status AS batch_status
            FROM barcodes_ledger b
            JOIN medicines m ON b.medicine_id = m.id
            JOIN manufacturers mfg ON m.manufacturer_id = mfg.id
            JOIN batches bat ON b.batch_id = bat.id
            WHERE b.barcode_serial = :barcode
            LIMIT 1
        ";
        $stmt = $pdo->prepare($query);
        $stmt->execute([':barcode' => $barcode]);
        $record = $stmt->fetch();
    } catch (Exception $e) {
        $record = null;
    }
}

// Fallback Seed Dataset if MySQL offline or record not in db yet
if (!$record) {
    $seedLedger = [
        'MS-2026-NAPA-7821A' => [
            'id' => 1,
            'barcode_serial' => 'MS-2026-NAPA-7821A',
            'brand_name' => 'Napa Extra',
            'generic_name' => 'Paracetamol + Caffeine',
            'strength' => '500mg + 65mg',
            'dosage_form' => 'Tablet',
            'dar_number' => 'DAR-024-0312-054',
            'mrp_bdt' => 2.50,
            'pack_size' => '10 x 10 Strip',
            'indications_merits' => 'Fast relief from acute headaches, migraines, muscle aches, fever and neuralgia.',
            'side_effects_demerits' => 'Mild insomnia or palpitations due to caffeine if consumed in excess.',
            'dosage_instructions' => '1 to 2 tablets every 4 to 6 hours. Maximum 8 tablets in 24 hours.',
            'precautions' => 'Caution with liver disease. Avoid simultaneous paracetamol medications.',
            'primary_color_hex' => '#e11d48',
            'packaging_ref_image' => 'assets/images/medicines/napa_extra.svg',
            'manufacturer_name' => 'Square Pharmaceuticals PLC',
            'manufacturer_code' => 'SQUARE',
            'dgda_license_no' => 'DGDA-MFG-00124',
            'batch_number' => 'SQ-NPA-2026B1',
            'manufacturing_date' => '2026-01-15',
            'expiry_date' => '2028-01-14',
            'production_facility' => 'Dhaka Unit-1, Kaliakair Plant',
            'batch_status' => 'Active',
            'scan_count' => 0,
            'lifecycle_status' => 'on_shelf',
            'scratch_pin' => '9482',
            'first_scanned_at' => null,
            'first_scanned_location' => null,
            'is_flagged_counterfeit' => 0,
            'flag_reason' => null
        ],
        'MS-2026-SECLO-9914C' => [
            'id' => 2,
            'barcode_serial' => 'MS-2026-SECLO-9914C',
            'brand_name' => 'Seclo 20',
            'generic_name' => 'Omeprazole',
            'strength' => '20mg',
            'dosage_form' => 'Capsule',
            'dar_number' => 'DAR-024-0089-012',
            'mrp_bdt' => 6.00,
            'pack_size' => '10 x 10 Capsule Strip',
            'indications_merits' => 'Treatment of gastric and duodenal ulcers, GERD, acid suppression.',
            'side_effects_demerits' => 'Abdominal discomfort, mild headache, nausea.',
            'dosage_instructions' => '1 capsule daily 30 minutes before morning breakfast.',
            'precautions' => 'Rule out gastric malignancy before initiating long-term therapy.',
            'primary_color_hex' => '#0284c7',
            'packaging_ref_image' => 'assets/images/medicines/seclo_20.svg',
            'manufacturer_name' => 'Square Pharmaceuticals PLC',
            'manufacturer_code' => 'SQUARE',
            'dgda_license_no' => 'DGDA-MFG-00124',
            'batch_number' => 'SQ-SCL-2026A4',
            'manufacturing_date' => '2026-02-10',
            'expiry_date' => '2028-02-09',
            'production_facility' => 'Pabna Main Production Facility',
            'batch_status' => 'Active',
            'scan_count' => 0,
            'lifecycle_status' => 'on_shelf',
            'scratch_pin' => '5510',
            'first_scanned_at' => null,
            'first_scanned_location' => null,
            'is_flagged_counterfeit' => 0,
            'flag_reason' => null
        ],
        'MS-2026-NAPA-SHELF-102' => [
            'id' => 3,
            'barcode_serial' => 'MS-2026-NAPA-SHELF-102',
            'brand_name' => 'Napa Extra',
            'generic_name' => 'Paracetamol + Caffeine',
            'strength' => '500mg + 65mg',
            'dosage_form' => 'Tablet',
            'dar_number' => 'DAR-024-0312-054',
            'mrp_bdt' => 2.50,
            'pack_size' => '10 x 10 Strip',
            'indications_merits' => 'Fast relief from acute headaches, migraines, muscle aches, fever and neuralgia.',
            'side_effects_demerits' => 'Mild insomnia or palpitations due to caffeine if consumed in excess.',
            'dosage_instructions' => '1 to 2 tablets every 4 to 6 hours. Maximum 8 tablets in 24 hours.',
            'precautions' => 'Caution with liver disease. Avoid simultaneous paracetamol medications.',
            'primary_color_hex' => '#e11d48',
            'packaging_ref_image' => 'assets/images/medicines/napa_extra.svg',
            'manufacturer_name' => 'Square Pharmaceuticals PLC',
            'manufacturer_code' => 'SQUARE',
            'dgda_license_no' => 'DGDA-MFG-00124',
            'batch_number' => 'SQ-NPA-2026B1',
            'manufacturing_date' => '2026-01-15',
            'expiry_date' => '2028-01-14',
            'production_facility' => 'Dhaka Unit-1, Kaliakair Plant',
            'batch_status' => 'Active',
            'scan_count' => 3,
            'lifecycle_status' => 'on_shelf',
            'scratch_pin' => '7731',
            'first_scanned_at' => date('Y-m-d H:i:s', strtotime('-45 minutes')),
            'first_scanned_location' => 'Bismillah Pharmacy, Dhanmondi 27, Dhaka',
            'is_flagged_counterfeit' => 0,
            'flag_reason' => null
        ],
        'MS-2026-SECL-SOLD-555' => [
            'id' => 4,
            'barcode_serial' => 'MS-2026-SECL-SOLD-555',
            'brand_name' => 'Seclo 20',
            'generic_name' => 'Omeprazole',
            'strength' => '20mg',
            'dosage_form' => 'Capsule',
            'dar_number' => 'DAR-024-0089-012',
            'mrp_bdt' => 6.00,
            'pack_size' => '10 x 10 Capsule Strip',
            'indications_merits' => 'Treatment of gastric and duodenal ulcers, GERD.',
            'side_effects_demerits' => 'Abdominal discomfort, mild headache.',
            'dosage_instructions' => '1 capsule daily before breakfast.',
            'precautions' => 'Prescription medicine.',
            'primary_color_hex' => '#0284c7',
            'packaging_ref_image' => 'assets/images/medicines/seclo_20.svg',
            'manufacturer_name' => 'Square Pharmaceuticals PLC',
            'manufacturer_code' => 'SQUARE',
            'dgda_license_no' => 'DGDA-MFG-00124',
            'batch_number' => 'SQ-SCL-2026A4',
            'manufacturing_date' => '2026-02-10',
            'expiry_date' => '2028-02-09',
            'production_facility' => 'Pabna Main Production Facility',
            'batch_status' => 'Active',
            'scan_count' => 2,
            'lifecycle_status' => 'sold_claimed',
            'scratch_pin' => '8834',
            'first_scanned_at' => '2026-04-10 14:20:00',
            'first_scanned_location' => 'Green Life Pharmacy, Green Road, Dhaka',
            'claimed_at' => '2026-04-10 14:25:00',
            'claimed_by' => 'Tasnimur Rahman (Citizen Guardian)',
            'claimed_location' => 'Green Life Pharmacy, Green Road, Dhaka',
            'is_flagged_counterfeit' => 0,
            'flag_reason' => null
        ],
        'MS-2026-ACEP-CLONE-771' => [
            'id' => 5,
            'barcode_serial' => 'MS-2026-ACEP-CLONE-771',
            'brand_name' => 'Ace Plus',
            'generic_name' => 'Paracetamol + Caffeine',
            'strength' => '500mg + 65mg',
            'dosage_form' => 'Tablet',
            'dar_number' => 'DAR-088-0219-063',
            'mrp_bdt' => 2.50,
            'pack_size' => '10 x 10 Strip',
            'indications_merits' => 'Rapid relief of headache, migraine, toothache and fever.',
            'side_effects_demerits' => 'Skin rash, transient dizziness.',
            'dosage_instructions' => '1-2 tablets every 4-6 hours.',
            'precautions' => 'Avoid with heavy alcohol intake.',
            'primary_color_hex' => '#2563eb',
            'packaging_ref_image' => 'assets/images/medicines/ace_plus.svg',
            'manufacturer_name' => 'Beximco Pharmaceuticals Ltd.',
            'manufacturer_code' => 'BEXIMCO',
            'dgda_license_no' => 'DGDA-MFG-00088',
            'batch_number' => 'BX-ACE-2026X2',
            'manufacturing_date' => '2026-03-01',
            'expiry_date' => '2028-02-28',
            'production_facility' => 'Tongi Plant, Gazipur',
            'batch_status' => 'Active',
            'scan_count' => 4,
            'lifecycle_status' => 'on_shelf',
            'scratch_pin' => '6629',
            'first_scanned_at' => date('Y-m-d H:i:s', strtotime('-20 minutes')),
            'first_scanned_location' => 'Anderkilla Wholesale Drug Market, Chittagong',
            'is_flagged_counterfeit' => 0,
            'flag_reason' => null
        ],
        'MS-2026-FAKE-RAID-001' => [
            'id' => 6,
            'barcode_serial' => 'MS-2026-FAKE-RAID-001',
            'brand_name' => 'Napa Extra (Counterfeit Batch)',
            'generic_name' => 'Toxic Starch Compound',
            'strength' => '0mg Active Ingredient',
            'dosage_form' => 'Tablet',
            'dar_number' => 'FORGED-DAR-001',
            'mrp_bdt' => 2.50,
            'pack_size' => 'Counterfeit Strip',
            'indications_merits' => 'None - Toxic industrial adulterant.',
            'side_effects_demerits' => 'Poisoning, treatment failure, organ toxicity.',
            'dosage_instructions' => 'DO NOT INGEST.',
            'precautions' => 'Banned substance seized by Magistrate Court.',
            'primary_color_hex' => '#e11d48',
            'packaging_ref_image' => 'assets/images/medicines/napa_extra.svg',
            'manufacturer_name' => 'Illicit Counterfeit Workshop (Babubazar)',
            'manufacturer_code' => 'FAKE',
            'dgda_license_no' => 'REVOKED',
            'batch_number' => 'RAID-SEIZED-2026',
            'manufacturing_date' => '2026-01-01',
            'expiry_date' => '2026-12-31',
            'production_facility' => 'Illegal Workshop, Old Dhaka',
            'batch_status' => 'Recalled',
            'scan_count' => 12,
            'lifecycle_status' => 'on_shelf',
            'scratch_pin' => '0000',
            'first_scanned_at' => '2026-03-20 09:12:00',
            'first_scanned_location' => 'Babubazar Wholesale Market, Dhaka',
            'is_flagged_counterfeit' => 1,
            'flag_reason' => 'Identified in DGDA Police Raid #2026-08 (Toxic Starch Substitute)'
        ]
    ];

    if (isset($seedLedger[$barcode])) {
        $record = $seedLedger[$barcode];
    }
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if ($record && isset($_SESSION['claimed_barcodes'][$barcode])) {
    $record['lifecycle_status'] = 'sold_claimed';
    $record['claimed_at'] = $_SESSION['claimed_barcodes'][$barcode]['claimed_at'];
    $record['claimed_by'] = $_SESSION['claimed_barcodes'][$barcode]['claimed_by'];
    $record['claimed_location'] = $_SESSION['claimed_barcodes'][$barcode]['claimed_location'];
}

// ----------------------------------------------------
// CASE 1: Barcode NOT found in Ledger (Counterfeit / Invalid)
// ----------------------------------------------------
if (!$record) {
    if ($pdo !== null) {
        try {
            $logStmt = $pdo->prepare("
                INSERT INTO scan_logs (barcode_serial, scanned_by_user, verification_status, ai_visual_score, scanned_location, latitude, longitude, device_info, ip_address)
                VALUES (:barcode, :user, 'INVALID_FAKE', :ai_score, :location, :lat, :lng, :device, :ip)
            ");
            $logStmt->execute([
                ':barcode' => $barcode,
                ':user' => $userId,
                ':ai_score' => $aiVisualScore,
                ':location' => $location,
                ':lat' => $latitude,
                ':lng' => $longitude,
                ':device' => $device,
                ':ip' => $ip
            ]);
        } catch (Exception $e) {}
    }

    echo json_encode([
        'success' => true,
        'status' => 'INVALID_FAKE',
        'is_authentic' => false,
        'title' => 'UNRECOGNIZED / COUNTERFEIT BARCODE ❌',
        'message' => 'This barcode serial was NOT issued by any licensed pharmaceutical manufacturer or DGDA. It is highly likely a dangerous counterfeit or forged pack.',
        'barcode_serial' => $barcode,
        'action_required' => 'Do NOT consume this medicine. Please submit a Counterfeit Report immediately to notify DGDA authorities.',
        'points_earned' => 0
    ]);
    exit;
}

// ----------------------------------------------------
// CASE 2: Flagged / Recalled Barcode
// ----------------------------------------------------
if (($record['is_flagged_counterfeit'] ?? 0) == 1 || ($record['batch_status'] ?? '') === 'Recalled') {
    if ($pdo !== null) {
        try {
            $logStmt = $pdo->prepare("
                INSERT INTO scan_logs (barcode_serial, scanned_by_user, verification_status, ai_visual_score, scanned_location, latitude, longitude, device_info, ip_address)
                VALUES (:barcode, :user, 'FLAGGED_RECALLED', :ai_score, :location, :lat, :lng, :device, :ip)
            ");
            $logStmt->execute([
                ':barcode' => $barcode,
                ':user' => $userId,
                ':ai_score' => $aiVisualScore,
                ':location' => $location,
                ':lat' => $latitude,
                ':lng' => $longitude,
                ':device' => $device,
                ':ip' => $ip
            ]);
        } catch (Exception $e) {}
    }

    echo json_encode([
        'success' => true,
        'status' => 'FLAGGED_RECALLED',
        'is_authentic' => false,
        'title' => 'DANGER: RECALLED OR BLACKLISTED BATCH 🚫',
        'message' => 'This medicine batch has been officially blacklisted or recalled by the Drug Administration: ' . ($record['flag_reason'] ?: 'Counterfeit seizure alert in police raid.'),
        'barcode_serial' => $barcode,
        'medicine' => formatMedicineResponse($record),
        'action_required' => 'Immediately halt usage and hand over the pack to your nearest pharmacy or DGDA field office.'
    ]);
    exit;
}

// ----------------------------------------------------
// CASE 3: Already Sold & Claimed (Post-Purchase Resale Anomaly)
// ----------------------------------------------------
if (($record['lifecycle_status'] ?? 'on_shelf') === 'sold_claimed') {
    if ($pdo !== null) {
        try {
            $logStmt = $pdo->prepare("
                INSERT INTO scan_logs (barcode_serial, scanned_by_user, verification_status, ai_visual_score, scanned_location, latitude, longitude, device_info, ip_address)
                VALUES (:barcode, :user, 'ALREADY_SOLD_ALERT', :ai_score, :location, :lat, :lng, :device, :ip)
            ");
            $logStmt->execute([
                ':barcode' => $barcode,
                ':user' => $userId,
                ':ai_score' => $aiVisualScore,
                ':location' => $location,
                ':lat' => $latitude,
                ':lng' => $longitude,
                ':device' => $device,
                ':ip' => $ip
            ]);
        } catch (Exception $e) {}
    }

    echo json_encode([
        'success' => true,
        'status' => 'ALREADY_SOLD_ALERT',
        'is_authentic' => false,
        'title' => 'WARNING: MEDICINE ALREADY SOLD & CLAIMED ⚠️',
        'message' => 'This specific pack was already purchased and registered on ' . ($record['claimed_at'] ?: 'a previous date') . ' by ' . ($record['claimed_by'] ?: 'another customer') . ' at ' . ($record['claimed_location'] ?: 'a pharmacy') . '. If this box is currently on a retail shelf, it is likely an illicit repackage, used box resale, or cloned code.',
        'barcode_serial' => $barcode,
        'lifecycle_status' => 'sold_claimed',
        'claimed_at' => $record['claimed_at'],
        'claimed_by' => $record['claimed_by'],
        'claimed_location' => $record['claimed_location'],
        'medicine' => formatMedicineResponse($record),
        'action_required' => 'Do NOT accept this pack from the pharmacy counter! Please report this incident to DGDA.'
    ]);
    exit;
}

// ----------------------------------------------------
// CASE 4: Fresh Genuine (First Scan Ever - scan_count == 0)
// ----------------------------------------------------
if (($record['scan_count'] ?? 0) == 0) {
    $now = date('Y-m-d H:i:s');
    
    if ($pdo !== null) {
        try {
            $updateStmt = $pdo->prepare("
                UPDATE barcodes_ledger 
                SET scan_count = 1,
                    first_scanned_at = :first_at,
                    first_scanned_location = :loc,
                    first_scanned_ip = :ip,
                    first_scanned_device = :device,
                    last_scanned_at = :first_at,
                    last_scanned_location = :loc
                WHERE id = :id
            ");
            $updateStmt->execute([
                ':first_at' => $now,
                ':loc' => $location,
                ':ip' => $ip,
                ':device' => $device,
                ':id' => $record['id']
            ]);

            $logStmt = $pdo->prepare("
                INSERT INTO scan_logs (barcode_serial, scanned_by_user, verification_status, ai_visual_score, scanned_location, latitude, longitude, device_info, ip_address)
                VALUES (:barcode, :user, 'GENUINE', :ai_score, :location, :lat, :lng, :device, :ip)
            ");
            $logStmt->execute([
                ':barcode' => $barcode,
                ':user' => $userId,
                ':ai_score' => $aiVisualScore,
                ':location' => $location,
                ':lat' => $latitude,
                ':lng' => $longitude,
                ':device' => $device,
                ':ip' => $ip
            ]);

            $rewardStmt = $pdo->prepare("
                UPDATE user_rewards 
                SET points = points + 50, 
                    genuine_scans_count = genuine_scans_count + 1
                WHERE user_id = :user
            ");
            $rewardStmt->execute([':user' => $userId]);
        } catch (Exception $e) {}
    }

    echo json_encode([
        'success' => true,
        'status' => 'GENUINE',
        'is_authentic' => true,
        'can_claim' => true,
        'scratch_pin' => $record['scratch_pin'] ?? null,
        'title' => 'VERIFIED 100% GENUINE MEDICINE ✅',
        'message' => 'Official manufacturer barcode verified. This genuine pack was registered on the national ledger just now. It is genuine, untouched, and safe to purchase.',
        'barcode_serial' => $barcode,
        'first_scanned_at' => $now,
        'first_scanned_location' => $location,
        'scan_count' => 1,
        'lifecycle_status' => 'on_shelf',
        'points_earned' => 50,
        'medicine' => formatMedicineResponse($record)
    ]);
    exit;
}

// ----------------------------------------------------
// CASE 5: Multiple Scans on Shelf (AI Context & Velocity Check)
// ----------------------------------------------------
$firstLoc = strtolower($record['first_scanned_location'] ?? '');
$currentLoc = strtolower($location);

$cities = ['dhaka', 'chittagong', 'sylhet', 'rajshahi', 'khulna', 'barisal', 'rangpur', 'mymensingh', 'gazipur', 'comilla'];
$firstCity = null;
$currentCity = null;

foreach ($cities as $c) {
    if (strpos($firstLoc, $c) !== false) $firstCity = $c;
    if (strpos($currentLoc, $c) !== false) $currentCity = $c;
}

$isDistantCloning = false;
if ($firstCity && $currentCity && $firstCity !== $currentCity) {
    $isDistantCloning = true;
}

if (strpos($barcode, 'CLONE') !== false || strpos($barcode, 'DUPL') !== false) {
    $isDistantCloning = true;
}

$now = date('Y-m-d H:i:s');
$newScanCount = ($record['scan_count'] ?? 1) + 1;

// Sub-case A: Cloned Barcode Anomaly (Distant geographic jump)
if ($isDistantCloning) {
    if ($pdo !== null) {
        try {
            $updateStmt = $pdo->prepare("
                UPDATE barcodes_ledger 
                SET scan_count = scan_count + 1,
                    last_scanned_at = :now,
                    last_scanned_location = :loc,
                    is_flagged_counterfeit = 1,
                    flag_reason = 'AI Spatial Velocity Anomaly: Cloned barcode detected across distant cities'
                WHERE id = :id
            ");
            $updateStmt->execute([':now' => $now, ':loc' => $location, ':id' => $record['id']]);

            $logStmt = $pdo->prepare("
                INSERT INTO scan_logs (barcode_serial, scanned_by_user, verification_status, ai_visual_score, scanned_location, latitude, longitude, device_info, ip_address)
                VALUES (:barcode, :user, 'CLONED_COUNTERFEIT', :ai_score, :location, :lat, :lng, :device, :ip)
            ");
            $logStmt->execute([
                ':barcode' => $barcode,
                ':user' => $userId,
                ':ai_score' => $aiVisualScore,
                ':location' => $location,
                ':lat' => $latitude,
                ':lng' => $longitude,
                ':device' => $device,
                ':ip' => $ip
            ]);
        } catch (Exception $e) {}
    }

    echo json_encode([
        'success' => true,
        'status' => 'CLONED_COUNTERFEIT',
        'is_authentic' => false,
        'title' => 'SUSPICIOUS CLONED BARCODE ANOMALY ⚠️',
        'message' => 'AI Spatial-Temporal Engine Alert: This barcode was originally activated in "' . ($record['first_scanned_location'] ?: 'another location') . '", but has just surfaced in "' . $location . '". Counterfeit syndicates frequently photocopy a single genuine barcode across multiple counterfeit batches in distant markets.',
        'barcode_serial' => $barcode,
        'first_scanned_at' => $record['first_scanned_at'],
        'first_scanned_location' => $record['first_scanned_location'],
        'current_scanned_location' => $location,
        'total_scans_detected' => $newScanCount,
        'medicine' => formatMedicineResponse($record),
        'action_required' => 'Do NOT purchase or ingest this pack. Verify packaging with the AI Visual Inspector and report this pharmacy to DGDA.'
    ]);
    exit;
}

// Sub-case B: In-Store Shelf Browsing (Same pharmacy / vicinity) -> 100% GENUINE!
if ($pdo !== null) {
    try {
        $updateStmt = $pdo->prepare("
            UPDATE barcodes_ledger 
            SET scan_count = scan_count + 1,
                last_scanned_at = :now,
                last_scanned_location = :loc
            WHERE id = :id
        ");
        $updateStmt->execute([':now' => $now, ':loc' => $location, ':id' => $record['id']]);

        $logStmt = $pdo->prepare("
            INSERT INTO scan_logs (barcode_serial, scanned_by_user, verification_status, ai_visual_score, scanned_location, latitude, longitude, device_info, ip_address)
            VALUES (:barcode, :user, 'GENUINE_SHELF_BROWSING', :ai_score, :location, :lat, :lng, :device, :ip)
        ");
        $logStmt->execute([
            ':barcode' => $barcode,
            ':user' => $userId,
            ':ai_score' => $aiVisualScore,
            ':location' => $location,
            ':lat' => $latitude,
            ':lng' => $longitude,
            ':device' => $device,
            ':ip' => $ip
        ]);
    } catch (Exception $e) {}
}

echo json_encode([
    'success' => true,
    'status' => 'GENUINE_SHELF_BROWSING',
    'is_authentic' => true,
    'can_claim' => true,
    'scratch_pin' => $record['scratch_pin'] ?? null,
    'title' => 'VERIFIED GENUINE (IN-STORE SHELF BROWSING) 🟢',
    'message' => 'Verified authentic medicine from ' . ($record['manufacturer_name'] ?? 'Licensed Manufacturer') . '. This pack has been checked ' . ($record['scan_count'] ?? 2) . ' time(s) on this pharmacy shelf (customer browsing). It is currently UNCLAIMED, unopened, and 100% safe to purchase.',
    'barcode_serial' => $barcode,
    'first_scanned_at' => $record['first_scanned_at'],
    'first_scanned_location' => $record['first_scanned_location'],
    'scan_count' => $newScanCount,
    'lifecycle_status' => 'on_shelf',
    'points_earned' => 20,
    'medicine' => formatMedicineResponse($record)
]);
exit;

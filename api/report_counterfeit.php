<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config/db.php';

try {
    $pdo = Database::getConnection();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

$barcode = trim($input['barcode_serial'] ?? '');
$medicineName = trim($input['medicine_name'] ?? 'Unknown Medicine');
$pharmacyName = trim($input['pharmacy_name'] ?? 'Local Pharmacy');
$pharmacyAddress = trim($input['pharmacy_address'] ?? 'Dhaka');
$city = trim($input['city'] ?? 'Dhaka');
$district = trim($input['district'] ?? 'Dhaka');
$reporterName = trim($input['reporter_name'] ?? 'Vigilant Citizen');
$reporterPhone = trim($input['reporter_phone'] ?? '');
$description = trim($input['description'] ?? 'Suspicious packaging, defective seal, or duplicate barcode detected.');
$suspicionLevel = in_array($input['ai_suspicion_level'] ?? '', ['Low', 'Medium', 'High', 'Critical']) ? $input['ai_suspicion_level'] : 'High';
$userId = trim($input['user_id'] ?? 'guest_citizen');

$stmt = $pdo->prepare("
    INSERT INTO counterfeit_reports (barcode_serial, medicine_name, pharmacy_name, pharmacy_address, city, district, reporter_name, reporter_phone, description, ai_suspicion_level, status)
    VALUES (:barcode, :med, :pharm, :addr, :city, :dist, :rep_name, :rep_phone, :desc, :level, 'Under Review')
");

$stmt->execute([
    ':barcode' => $barcode ?: null,
    ':med' => $medicineName,
    ':pharm' => $pharmacyName,
    ':addr' => $pharmacyAddress,
    ':city' => $city,
    ':dist' => $district,
    ':rep_name' => $reporterName,
    ':rep_phone' => $reporterPhone,
    ':desc' => $description,
    ':level' => $suspicionLevel
]);

$reportId = $pdo->lastInsertId();
$caseNumber = 'DGDA-RPT-' . date('Y') . '-' . str_pad($reportId, 5, '0', STR_PAD_LEFT);

// Award gamified points for reporting fake medicine (+150 points)
$rewardStmt = $pdo->prepare("
    UPDATE user_rewards 
    SET points = points + 150, 
        fake_reports_count = fake_reports_count + 1,
        badge_title = 'Community Health Guardian Lv. 2'
    WHERE user_id = :user
");
$rewardStmt->execute([':user' => $userId]);

echo json_encode([
    'success' => true,
    'case_number' => $caseNumber,
    'report_id' => $reportId,
    'points_awarded' => 150,
    'message' => 'Report submitted successfully to the Directorate General of Drug Administration (DGDA). You earned 150 Health Guardian points!'
]);

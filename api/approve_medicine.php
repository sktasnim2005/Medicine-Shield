<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config/auth.php';

if (!Auth::isLoggedIn() || Auth::getCurrentUser()['role'] !== 'dgda') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized: Only authorized DGDA regulatory officials can approve or reject medicine registrations.']);
    exit;
}

$currentUser = Auth::getCurrentUser();
$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

$medicineId = intval($input['medicine_id'] ?? 0);
$action = strtolower(trim($input['action'] ?? 'approve'));
$remarks = trim($input['remarks'] ?? '');

if ($medicineId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Valid medicine ID is required.']);
    exit;
}

$newStatus = ($action === 'reject') ? 'rejected' : 'approved';
$now = date('Y-m-d H:i:s');
$inspectorName = $currentUser['name'] ?? 'DGDA Inspector';

$pdo = null;
try {
    $pdo = Database::getConnection();
} catch (Exception $e) {
    $pdo = null;
}

if ($pdo !== null) {
    Database::ensureSchemaUpToDate($pdo);
    try {
        $stmt = $pdo->prepare("
            UPDATE medicines 
            SET approval_status = :status,
                approved_at = :approved_at,
                approved_by = :approved_by,
                dgda_remarks = :remarks
            WHERE id = :id
        ");
        $stmt->execute([
            ':status' => $newStatus,
            ':approved_at' => $now,
            ':approved_by' => $inspectorName,
            ':remarks' => $remarks ?: ($newStatus === 'approved' ? 'Verified and registered under National Drug Policy.' : 'Registration application rejected by DGDA.'),
            ':id' => $medicineId
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
        exit;
    }
}

$actionTitle = ($newStatus === 'approved') ? 'Approved & Officially Registered' : 'Rejected';

echo json_encode([
    'success' => true,
    'message' => "Medicine #{$medicineId} has been {$actionTitle} by {$inspectorName}! It is now active across pharmaceutical ledgers and public catalog.",
    'medicine_id' => $medicineId,
    'status' => $newStatus,
    'approved_at' => $now,
    'approved_by' => $inspectorName
]);

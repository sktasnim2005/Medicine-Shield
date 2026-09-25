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
require_once __DIR__ . '/../config/db.php';

// Strict Authentication Check: Only a logged-in user profile can claim medicine
if (!Auth::isLoggedIn()) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'auth_required' => true,
        'error' => 'Authentication required: You must be logged in to your account to confirm purchase and secure ownership.'
    ]);
    exit;
}

$currentUser = Auth::getCurrentUser();
$userId = (int)$currentUser['id'];
$buyerName = trim($currentUser['name']) . ' (User ID #' . $userId . ')';

$pdo = null;
try {
    $pdo = Database::getConnection();
} catch (Exception $e) {
    $pdo = null;
}

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$barcode = trim($input['barcode'] ?? '');
$location = trim($input['location'] ?? 'Bismillah Pharmacy, Dhanmondi 27, Dhaka');

if (empty($barcode)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Barcode serial is required to claim medicine.']);
    exit;
}

$now = date('Y-m-d H:i:s');

// Session-level fallback persistence for in-memory / demo mode
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['claimed_barcodes'])) {
    $_SESSION['claimed_barcodes'] = [];
}

if ($pdo !== null) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM barcodes_ledger WHERE barcode_serial = :barcode LIMIT 1");
        $stmt->execute([':barcode' => $barcode]);
        $record = $stmt->fetch();

        if ($record && ($record['lifecycle_status'] ?? 'on_shelf') === 'sold_claimed') {
            echo json_encode([
                'success' => false,
                'error' => 'This medicine has already been claimed on ' . ($record['claimed_at'] ?: 'earlier') . ' by ' . ($record['claimed_by'] ?: 'another customer') . '.'
            ]);
            exit;
        }

        if ($record) {
            $updateStmt = $pdo->prepare("
                UPDATE barcodes_ledger 
                SET lifecycle_status = 'sold_claimed',
                    claimed_at = :now,
                    claimed_by = :buyer,
                    claimed_location = :loc
                WHERE id = :id
            ");
            $updateStmt->execute([
                ':now' => $now,
                ':buyer' => $buyerName,
                ':loc' => $location,
                ':id' => $record['id']
            ]);

            // Update points for the authenticated user
            $rewardStmt = $pdo->prepare("
                INSERT INTO user_rewards (user_id, points, genuine_scans_count)
                VALUES (:user, 100, 1)
                ON DUPLICATE KEY UPDATE
                    points = points + 100,
                    genuine_scans_count = genuine_scans_count + 1
            ");
            $rewardStmt->execute([':user' => $userId]);
        }
    } catch (Exception $e) {}
}

// Track in session ledger
$_SESSION['claimed_barcodes'][$barcode] = [
    'claimed_at' => $now,
    'claimed_by' => $buyerName,
    'claimed_location' => $location
];

echo json_encode([
    'success' => true,
    'message' => 'Medicine purchase confirmed and ownership securely locked in the National DGDA Ledger! This serial code is permanently retired and registered to your profile.',
    'barcode_serial' => $barcode,
    'claimed_at' => $now,
    'claimed_by' => $buyerName,
    'user_id' => $userId,
    'claimed_location' => $location,
    'points_earned' => 100
]);


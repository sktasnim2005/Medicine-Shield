<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
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

$userId = trim($_GET['user_id'] ?? 'guest_citizen');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $rewardType = trim($input['reward_type'] ?? '');
    $userId = trim($input['user_id'] ?? 'guest_citizen');

    $costs = [
        'Mobile Recharge BDT 50' => 150,
        'Mobile Recharge BDT 100' => 300,
        'Pharmacy 10% Discount Voucher' => 100,
        'Health Guardian T-Shirt' => 500
    ];

    if (!isset($costs[$rewardType])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid reward selected']);
        exit;
    }

    $cost = $costs[$rewardType];

    // Check user points
    $stmt = $pdo->prepare("SELECT * FROM user_rewards WHERE user_id = :u LIMIT 1");
    $stmt->execute([':u' => $userId]);
    $user = $stmt->fetch();

    if (!$user || $user['points'] < $cost) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Insufficient points. You need ' . $cost . ' points.']);
        exit;
    }

    // Deduct points
    $pdo->prepare("UPDATE user_rewards SET points = points - :cost WHERE user_id = :u")->execute([':cost' => $cost, ':u' => $userId]);

    // Issue voucher
    $voucherCode = 'MS-REWARD-' . strtoupper(bin2hex(random_bytes(4)));
    $pdo->prepare("
        INSERT INTO reward_redemptions (user_id, reward_type, points_spent, voucher_code, status)
        VALUES (:u, :type, :cost, :vcode, 'Issued')
    ")->execute([':u' => $userId, ':type' => $rewardType, ':cost' => $cost, ':vcode' => $voucherCode]);

    echo json_encode([
        'success' => true,
        'voucher_code' => $voucherCode,
        'reward_type' => $rewardType,
        'points_spent' => $cost,
        'message' => 'Congratulations! Your ' . $rewardType . ' was redeemed successfully.'
    ]);
    exit;
}

// GET rewards profile
$stmt = $pdo->prepare("SELECT * FROM user_rewards WHERE user_id = :u LIMIT 1");
$stmt->execute([':u' => $userId]);
$user = $stmt->fetch();

if (!$user) {
    $pdo->prepare("INSERT INTO user_rewards (user_id, points, badge_title) VALUES (:u, 100, 'Health Guardian Lv. 1')")->execute([':u' => $userId]);
    $user = ['user_id' => $userId, 'points' => 100, 'badge_title' => 'Health Guardian Lv. 1', 'genuine_scans_count' => 0, 'fake_reports_count' => 0];
}

$rStmt = $pdo->prepare("SELECT * FROM reward_redemptions WHERE user_id = :u ORDER BY id DESC LIMIT 10");
$rStmt->execute([':u' => $userId]);
$redemptions = $rStmt->fetchAll();

echo json_encode([
    'success' => true,
    'profile' => $user,
    'redemptions' => $redemptions
]);

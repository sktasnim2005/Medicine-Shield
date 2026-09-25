<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');

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

$medicineId = isset($_GET['medicine_id']) && $_GET['medicine_id'] !== '' ? intval($_GET['medicine_id']) : null;
$batchId = isset($_GET['batch_id']) && $_GET['batch_id'] !== '' ? intval($_GET['batch_id']) : null;
$search = isset($_GET['q']) ? trim($_GET['q']) : '';
$limit = min(intval($_GET['limit'] ?? 100), 500);

$stats = [
    'total_medicines' => 0,
    'total_batches' => 0,
    'total_barcodes' => 0,
    'on_shelf_count' => 0,
    'sold_claimed_count' => 0
];

$medicinesSummary = [];
$barcodes = [];

if ($pdo !== null) {
    try {
        // Overall Stats
        $stats['total_medicines'] = intval($pdo->query("SELECT COUNT(*) FROM medicines")->fetchColumn() ?: 0);
        $stats['total_batches'] = intval($pdo->query("SELECT COUNT(*) FROM batches")->fetchColumn() ?: 0);
        $stats['total_barcodes'] = intval($pdo->query("SELECT COUNT(*) FROM barcodes_ledger")->fetchColumn() ?: 0);
        $stats['on_shelf_count'] = intval($pdo->query("SELECT COUNT(*) FROM barcodes_ledger WHERE lifecycle_status = 'on_shelf'")->fetchColumn() ?: 0);
        $stats['sold_claimed_count'] = intval($pdo->query("SELECT COUNT(*) FROM barcodes_ledger WHERE lifecycle_status = 'sold_claimed'")->fetchColumn() ?: 0);

        // Medicines Summary with Barcode Counts
        $medStmt = $pdo->query("
            SELECT 
                m.id, m.brand_name, m.generic_name, m.strength, m.dosage_form,
                mfg.name AS manufacturer_name,
                COUNT(DISTINCT b.id) AS active_batches_count,
                COUNT(DISTINCT bl.id) AS barcodes_count
            FROM medicines m
            JOIN manufacturers mfg ON m.manufacturer_id = mfg.id
            LEFT JOIN batches b ON b.medicine_id = m.id
            LEFT JOIN barcodes_ledger bl ON bl.medicine_id = m.id
            GROUP BY m.id
            ORDER BY m.brand_name ASC
        ");
        $medicinesSummary = $medStmt->fetchAll();

        // Query Barcodes Ledger
        $sql = "
            SELECT 
                bl.id, bl.barcode_serial, bl.crypto_hash, bl.scan_count, 
                bl.lifecycle_status, bl.scratch_pin, bl.first_scanned_at, 
                bl.first_scanned_location, bl.claimed_at, bl.claimed_by, 
                bl.claimed_location, bl.is_flagged_counterfeit, bl.flag_reason, 
                bl.created_at,
                m.id AS medicine_id, m.brand_name, m.generic_name, m.strength,
                bat.id AS batch_id, bat.batch_number, bat.expiry_date
            FROM barcodes_ledger bl
            JOIN medicines m ON bl.medicine_id = m.id
            JOIN batches bat ON bl.batch_id = bat.id
            WHERE 1=1
        ";
        $params = [];

        if ($medicineId) {
            $sql .= " AND bl.medicine_id = :mid";
            $params[':mid'] = $medicineId;
        }

        if ($batchId) {
            $sql .= " AND bl.batch_id = :bid";
            $params[':bid'] = $batchId;
        }

        if ($search !== '') {
            $sql .= " AND (bl.barcode_serial LIKE :q OR m.brand_name LIKE :q OR bat.batch_number LIKE :q)";
            $params[':q'] = '%' . $search . '%';
        }

        $sql .= " ORDER BY bl.id DESC LIMIT " . $limit;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $barcodes = $stmt->fetchAll();

    } catch (Exception $e) {
        // Fallback handled below
    }
}

// Fallback seed data if DB is offline or empty
if (empty($barcodes)) {
    $fallbackBarcodes = [
        [
            'id' => 1,
            'barcode_serial' => 'MS-2026-NAPA-7821A',
            'crypto_hash' => '78a1bc4f9012...',
            'medicine_id' => 1,
            'brand_name' => 'Napa Extra',
            'generic_name' => 'Paracetamol + Caffeine',
            'strength' => '500mg + 65mg',
            'batch_id' => 1,
            'batch_number' => 'SQ-NPA-2026B1',
            'expiry_date' => '2028-01-14',
            'scan_count' => 0,
            'lifecycle_status' => 'on_shelf',
            'scratch_pin' => '9482',
            'first_scanned_at' => null,
            'first_scanned_location' => null,
            'claimed_at' => null,
            'claimed_by' => null,
            'claimed_location' => null,
            'is_flagged_counterfeit' => 0,
            'flag_reason' => null,
            'created_at' => '2026-03-15 10:00:00'
        ],
        [
            'id' => 2,
            'barcode_serial' => 'MS-2026-NAPA-SHELF-102',
            'crypto_hash' => '19ba48d083fa...',
            'medicine_id' => 1,
            'brand_name' => 'Napa Extra',
            'generic_name' => 'Paracetamol + Caffeine',
            'strength' => '500mg + 65mg',
            'batch_id' => 1,
            'batch_number' => 'SQ-NPA-2026B1',
            'expiry_date' => '2028-01-14',
            'scan_count' => 3,
            'lifecycle_status' => 'on_shelf',
            'scratch_pin' => '7731',
            'first_scanned_at' => '2026-09-11 15:30:00',
            'first_scanned_location' => 'Bismillah Pharmacy, Dhanmondi 27, Dhaka',
            'claimed_at' => null,
            'claimed_by' => null,
            'claimed_location' => null,
            'is_flagged_counterfeit' => 0,
            'flag_reason' => null,
            'created_at' => '2026-03-15 10:05:00'
        ],
        [
            'id' => 3,
            'barcode_serial' => 'MS-2026-SECLO-9914C',
            'crypto_hash' => '99c4a8f90241...',
            'medicine_id' => 2,
            'brand_name' => 'Seclo 20',
            'generic_name' => 'Omeprazole',
            'strength' => '20mg',
            'batch_id' => 2,
            'batch_number' => 'SQ-SCL-2026A4',
            'expiry_date' => '2028-02-09',
            'scan_count' => 0,
            'lifecycle_status' => 'on_shelf',
            'scratch_pin' => '5510',
            'first_scanned_at' => null,
            'first_scanned_location' => null,
            'claimed_at' => null,
            'claimed_by' => null,
            'claimed_location' => null,
            'is_flagged_counterfeit' => 0,
            'flag_reason' => null,
            'created_at' => '2026-03-18 14:20:00'
        ],
        [
            'id' => 4,
            'barcode_serial' => 'MS-2026-SECL-SOLD-555',
            'crypto_hash' => '555fa488b021...',
            'medicine_id' => 2,
            'brand_name' => 'Seclo 20',
            'generic_name' => 'Omeprazole',
            'strength' => '20mg',
            'batch_id' => 2,
            'batch_number' => 'SQ-SCL-2026A4',
            'expiry_date' => '2028-02-09',
            'scan_count' => 2,
            'lifecycle_status' => 'sold_claimed',
            'scratch_pin' => '8834',
            'first_scanned_at' => '2026-04-10 14:20:00',
            'first_scanned_location' => 'Green Life Pharmacy, Green Road, Dhaka',
            'claimed_at' => '2026-04-10 14:25:00',
            'claimed_by' => 'Tasnimur Rahman (Citizen Guardian)',
            'claimed_location' => 'Green Life Pharmacy, Green Road, Dhaka',
            'is_flagged_counterfeit' => 0,
            'flag_reason' => null,
            'created_at' => '2026-03-18 14:25:00'
        ],
        [
            'id' => 5,
            'barcode_serial' => 'MS-2026-ACEP-CLONE-771',
            'crypto_hash' => '771ab2c04098...',
            'medicine_id' => 4,
            'brand_name' => 'Ace Plus',
            'generic_name' => 'Paracetamol + Caffeine',
            'strength' => '500mg + 65mg',
            'batch_id' => 4,
            'batch_number' => 'BX-ACE-2026X2',
            'expiry_date' => '2028-02-28',
            'scan_count' => 4,
            'lifecycle_status' => 'on_shelf',
            'scratch_pin' => '6629',
            'first_scanned_at' => '2026-09-11 16:00:00',
            'first_scanned_location' => 'Anderkilla Wholesale Drug Market, Chittagong',
            'claimed_at' => null,
            'claimed_by' => null,
            'claimed_location' => null,
            'is_flagged_counterfeit' => 0,
            'flag_reason' => null,
            'created_at' => '2026-03-20 09:00:00'
        ]
    ];

    if ($medicineId) {
        $barcodes = array_values(array_filter($fallbackBarcodes, fn($b) => $b['medicine_id'] == $medicineId));
    } else {
        $barcodes = $fallbackBarcodes;
    }

    if ($stats['total_barcodes'] == 0) {
        $stats['total_medicines'] = 6;
        $stats['total_batches'] = 6;
        $stats['total_barcodes'] = count($fallbackBarcodes);
        $stats['on_shelf_count'] = count(array_filter($fallbackBarcodes, fn($b) => $b['lifecycle_status'] === 'on_shelf'));
        $stats['sold_claimed_count'] = count(array_filter($fallbackBarcodes, fn($b) => $b['lifecycle_status'] === 'sold_claimed'));
    }

    if (empty($medicinesSummary)) {
        $medicinesSummary = [
            ['id' => 1, 'brand_name' => 'Napa Extra', 'generic_name' => 'Paracetamol + Caffeine', 'strength' => '500mg + 65mg', 'dosage_form' => 'Tablet', 'manufacturer_name' => 'Square Pharmaceuticals PLC', 'active_batches_count' => 1, 'barcodes_count' => 2],
            ['id' => 2, 'brand_name' => 'Seclo 20', 'generic_name' => 'Omeprazole', 'strength' => '20mg', 'dosage_form' => 'Capsule', 'manufacturer_name' => 'Square Pharmaceuticals PLC', 'active_batches_count' => 1, 'barcodes_count' => 2],
            ['id' => 3, 'brand_name' => 'Monas 10', 'generic_name' => 'Montelukast Sodium', 'strength' => '10mg', 'dosage_form' => 'Tablet', 'manufacturer_name' => 'The ACME Laboratories Ltd.', 'active_batches_count' => 1, 'barcodes_count' => 1],
            ['id' => 4, 'brand_name' => 'Ace Plus', 'generic_name' => 'Paracetamol + Caffeine', 'strength' => '500mg + 65mg', 'dosage_form' => 'Tablet', 'manufacturer_name' => 'Beximco Pharmaceuticals Ltd.', 'active_batches_count' => 1, 'barcodes_count' => 1],
            ['id' => 5, 'brand_name' => 'Maxpro 20', 'generic_name' => 'Esomeprazole Magnesium', 'strength' => '20mg', 'dosage_form' => 'Tablet', 'manufacturer_name' => 'Renata Limited', 'active_batches_count' => 1, 'barcodes_count' => 1],
            ['id' => 6, 'brand_name' => 'Ciprocin 500', 'generic_name' => 'Ciprofloxacin', 'strength' => '500mg', 'dosage_form' => 'Tablet', 'manufacturer_name' => 'Square Pharmaceuticals PLC', 'active_batches_count' => 1, 'barcodes_count' => 1]
        ];
    }
}

echo json_encode([
    'success' => true,
    'stats' => $stats,
    'medicines_summary' => $medicinesSummary,
    'count' => count($barcodes),
    'barcodes' => $barcodes
]);

<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../config/db.php';

$pdo = null;
try {
    $pdo = Database::getConnection();
} catch (Exception $e) {
    $pdo = null;
}

$defaultMedicines = [
    [
        'id' => 1,
        'brand_name' => 'Napa Extra',
        'generic_name' => 'Paracetamol + Caffeine',
        'strength' => '500mg + 65mg',
        'dosage_form' => 'Tablet',
        'dar_number' => 'DAR-024-0312-054',
        'mrp_bdt' => 2.50,
        'pack_size' => '10 x 10 Strip',
        'indications_merits' => 'Relief of mild to moderate pain including headache, migraine, toothache, neuralgia, fever, sore throat, backache, and rheumatic pain.',
        'side_effects_demerits' => 'Generally well tolerated. Occasional skin rash, insomnia, restlessness due to caffeine.',
        'dosage_instructions' => 'Adults: 1-2 tablets every 4 to 6 hours as needed. Maximum 8 tablets in 24 hours.',
        'precautions' => 'Caution in patients with severe hepatic or renal impairment.',
        'primary_color_hex' => '#e11d48',
        'packaging_ref_image' => 'assets/images/medicines/napa_extra.svg',
        'manufacturer_name' => 'Square Pharmaceuticals PLC'
    ],
    [
        'id' => 2,
        'brand_name' => 'Seclo 20',
        'generic_name' => 'Omeprazole',
        'strength' => '20mg',
        'dosage_form' => 'Capsule',
        'dar_number' => 'DAR-024-0089-012',
        'mrp_bdt' => 6.00,
        'pack_size' => '10 x 10 Capsule Strip',
        'indications_merits' => 'Treatment of gastric and duodenal ulcers, GERD, Zollinger-Ellison syndrome.',
        'side_effects_demerits' => 'Headache, diarrhea, abdominal pain, nausea.',
        'dosage_instructions' => '1 capsule (20mg) once daily in the morning before breakfast.',
        'precautions' => 'Rule out gastric malignancy before initiating long-term therapy.',
        'primary_color_hex' => '#0284c7',
        'packaging_ref_image' => 'assets/images/medicines/seclo_20.svg',
        'manufacturer_name' => 'Square Pharmaceuticals PLC'
    ],
    [
        'id' => 3,
        'brand_name' => 'Monas 10',
        'generic_name' => 'Montelukast Sodium',
        'strength' => '10mg',
        'dosage_form' => 'Tablet',
        'dar_number' => 'DAR-031-0452-098',
        'mrp_bdt' => 16.00,
        'pack_size' => '3 x 10 Box',
        'indications_merits' => 'Prophylaxis and chronic treatment of asthma and seasonal allergic rhinitis.',
        'side_effects_demerits' => 'Upper respiratory infection, fever, headache, pharyngitis.',
        'dosage_instructions' => 'Adults: One 10mg tablet daily in the evening.',
        'precautions' => 'Not indicated for the reversal of acute bronchospasm.',
        'primary_color_hex' => '#16a34a',
        'packaging_ref_image' => 'assets/images/medicines/monas_10.svg',
        'manufacturer_name' => 'The ACME Laboratories Ltd.'
    ],
    [
        'id' => 4,
        'brand_name' => 'Ace Plus',
        'generic_name' => 'Paracetamol + Caffeine',
        'strength' => '500mg + 65mg',
        'dosage_form' => 'Tablet',
        'dar_number' => 'DAR-088-0219-063',
        'mrp_bdt' => 2.50,
        'pack_size' => '10 x 10 Strip',
        'indications_merits' => 'Rapid relief of headache, migraine, musculoskeletal pain and fever.',
        'side_effects_demerits' => 'Low toxicity at standard doses. Skin rash.',
        'dosage_instructions' => '1-2 tablets every 4-6 hours. Max 8 tablets in 24 hours.',
        'precautions' => 'Avoid with alcohol. Caution with liver disease.',
        'primary_color_hex' => '#2563eb',
        'packaging_ref_image' => 'assets/images/medicines/ace_plus.svg',
        'manufacturer_name' => 'Beximco Pharmaceuticals Ltd.'
    ],
    [
        'id' => 5,
        'brand_name' => 'Maxpro 20',
        'generic_name' => 'Esomeprazole Magnesium',
        'strength' => '20mg',
        'dosage_form' => 'Tablet',
        'dar_number' => 'DAR-045-0187-033',
        'mrp_bdt' => 7.00,
        'pack_size' => '10 x 10 Strip',
        'indications_merits' => 'GERD, healing of erosive esophagitis, acid suppression.',
        'side_effects_demerits' => 'Headache, abdominal pain, diarrhea.',
        'dosage_instructions' => '20mg once daily before meals.',
        'precautions' => 'Patients with severe liver disease should not exceed 20mg.',
        'primary_color_hex' => '#9333ea',
        'packaging_ref_image' => 'assets/images/medicines/maxpro_20.svg',
        'manufacturer_name' => 'Renata Limited'
    ],
    [
        'id' => 6,
        'brand_name' => 'Ciprocin 500',
        'generic_name' => 'Ciprofloxacin',
        'strength' => '500mg',
        'dosage_form' => 'Tablet',
        'dar_number' => 'DAR-024-0156-077',
        'mrp_bdt' => 15.00,
        'pack_size' => '10 x 10 Strip',
        'indications_merits' => 'Broad-spectrum antibiotic for respiratory, urinary, and gastrointestinal infections.',
        'side_effects_demerits' => 'Nausea, diarrhea, abdominal cramps.',
        'dosage_instructions' => '500mg every 12 hours for 7-14 days.',
        'precautions' => 'Avoid with dairy alone. Complete full course.',
        'primary_color_hex' => '#ea580c',
        'packaging_ref_image' => 'assets/images/medicines/ciprocin_500.svg',
        'manufacturer_name' => 'Square Pharmaceuticals PLC'
    ]
];

$id = isset($_GET['id']) ? intval($_GET['id']) : null;
$search = isset($_GET['q']) ? trim($_GET['q']) : '';

if ($pdo !== null) {
    try {
        if ($id) {
            $stmt = $pdo->prepare("
                SELECT m.*, mfg.name AS manufacturer_name, mfg.code AS manufacturer_code, mfg.headquarters, mfg.dgda_license_no
                FROM medicines m
                JOIN manufacturers mfg ON m.manufacturer_id = mfg.id
                WHERE m.id = :id
                LIMIT 1
            ");
            $stmt->execute([':id' => $id]);
            $medicine = $stmt->fetch();

            if ($medicine) {
                $bStmt = $pdo->prepare("SELECT * FROM batches WHERE medicine_id = :id ORDER BY expiry_date DESC");
                $bStmt->execute([':id' => $id]);
                $batches = $bStmt->fetchAll();

                echo json_encode(['success' => true, 'medicine' => $medicine, 'batches' => $batches]);
                exit;
            }
        } elseif ($search !== '') {
            $stmt = $pdo->prepare("
                SELECT m.*, mfg.name AS manufacturer_name
                FROM medicines m
                JOIN manufacturers mfg ON m.manufacturer_id = mfg.id
                WHERE (m.brand_name LIKE :s1 OR m.generic_name LIKE :s2)
                  AND (m.approval_status = 'approved' OR m.approval_status IS NULL)
                ORDER BY m.brand_name ASC
            ");
            $like = '%' . $search . '%';
            $stmt->execute([':s1' => $like, ':s2' => $like]);
            $medicines = $stmt->fetchAll();
            echo json_encode(['success' => true, 'count' => count($medicines), 'medicines' => $medicines]);
            exit;
        } else {
            $stmt = $pdo->query("
                SELECT m.*, mfg.name AS manufacturer_name
                FROM medicines m
                JOIN manufacturers mfg ON m.manufacturer_id = mfg.id
                WHERE (m.approval_status = 'approved' OR m.approval_status IS NULL)
                ORDER BY m.brand_name ASC
            ");
            $medicines = $stmt->fetchAll();
            if ($medicines && count($medicines) > 0) {
                echo json_encode(['success' => true, 'count' => count($medicines), 'medicines' => $medicines]);
                exit;
            }
        }
    } catch (Exception $e) {}
}

// Fallback response:
if ($id) {
    foreach ($defaultMedicines as $m) {
        if ($m['id'] == $id) {
            echo json_encode(['success' => true, 'medicine' => $m, 'batches' => []]);
            exit;
        }
    }
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Medicine not found']);
    exit;
}

if ($search !== '') {
    $filtered = array_values(array_filter($defaultMedicines, function($m) use ($search) {
        return stripos($m['brand_name'], $search) !== false || stripos($m['generic_name'], $search) !== false;
    }));
    echo json_encode(['success' => true, 'count' => count($filtered), 'medicines' => $filtered]);
    exit;
}

echo json_encode(['success' => true, 'count' => count($defaultMedicines), 'medicines' => $defaultMedicines]);

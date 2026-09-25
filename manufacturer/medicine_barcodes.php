<?php
require_once __DIR__ . "/../config/auth.php";

Auth::requireRole('manufacturer');

$currentUser = Auth::getCurrentUser();
$pdo = Database::getConnection();

$medicineId = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($medicineId <= 0) {
    header("Location: /manufacturer");
    exit;
}

$mfgId = $currentUser['manufacturer_id'] ?? null;

// Fetch medicine details
$medQuery = "
    SELECT m.*, mfg.name AS manufacturer_name, mfg.code AS manufacturer_code, mfg.dgda_license_no, mfg.headquarters
    FROM medicines m
    JOIN manufacturers mfg ON m.manufacturer_id = mfg.id
    WHERE m.id = :mid
";
if ($mfgId) {
    $medQuery .= " AND m.manufacturer_id = :mfgid";
}
$medQuery .= " LIMIT 1";

$mStmt = $pdo ? $pdo->prepare($medQuery) : null;
$medicine = null;
if ($mStmt) {
    $params = [':mid' => $medicineId];
    if ($mfgId) $params[':mfgid'] = $mfgId;
    $mStmt->execute($params);
    $medicine = $mStmt->fetch();
}

// Fallback if DB offline or testing
if (!$medicine) {
    $defaultMeds = [
        1 => [
            'id' => 1,
            'brand_name' => 'Napa Extra',
            'generic_name' => 'Paracetamol + Caffeine',
            'strength' => '500mg + 65mg',
            'dosage_form' => 'Tablet',
            'dar_number' => 'DAR-024-0312-054',
            'mrp_bdt' => 2.50,
            'pack_size' => '10 x 10 Strip',
            'indications_merits' => 'Fast relief from acute headaches, migraines, muscle aches, fever and neuralgia.',
            'side_effects_demerits' => 'Mild insomnia or palpitations due to caffeine if consumed in excess.',
            'dosage_instructions' => '1 to 2 tablets every 4 to 6 hours. Max 8 tablets daily.',
            'precautions' => 'Caution with liver disease. Avoid simultaneous paracetamol medications.',
            'approval_status' => 'approved',
            'manufacturer_name' => 'Square Pharmaceuticals PLC',
            'dgda_license_no' => 'DGDA-MFG-00124',
            'headquarters' => 'Square Centre, 48 Mohakhali C/A, Dhaka-1212'
        ],
        2 => [
            'id' => 2,
            'brand_name' => 'Seclo 20',
            'generic_name' => 'Omeprazole',
            'strength' => '20mg',
            'dosage_form' => 'Capsule',
            'dar_number' => 'DAR-024-0089-012',
            'mrp_bdt' => 6.00,
            'pack_size' => '10 x 10 Capsule Strip',
            'indications_merits' => 'Treatment of gastric and duodenal ulcers, GERD, acid suppression.',
            'side_effects_demerits' => 'Abdominal discomfort, mild headache, nausea.',
            'dosage_instructions' => '1 capsule daily before breakfast.',
            'precautions' => 'Rule out gastric malignancy before initiating therapy.',
            'approval_status' => 'approved',
            'manufacturer_name' => 'Square Pharmaceuticals PLC',
            'dgda_license_no' => 'DGDA-MFG-00124',
            'headquarters' => 'Square Centre, 48 Mohakhali C/A, Dhaka-1212'
        ]
    ];
    $medicine = $defaultMeds[$medicineId] ?? $defaultMeds[1];
}

// Fetch all barcodes generated for this medicine
$barcodes = [];
if ($pdo) {
    try {
        $bStmt = $pdo->prepare("
            SELECT b.*, bat.batch_number, bat.manufacturing_date, bat.expiry_date
            FROM barcodes_ledger b
            JOIN batches bat ON b.batch_id = bat.id
            WHERE b.medicine_id = :mid
            ORDER BY b.id DESC
        ");
        $bStmt->execute([':mid' => $medicineId]);
        $barcodes = $bStmt->fetchAll();
    } catch (Exception $e) {
        $barcodes = [];
    }
}

// Default fallback barcodes if none generated yet
if (empty($barcodes)) {
    if ($medicineId == 1) {
        $barcodes = [
            [
                'id' => 1,
                'barcode_serial' => 'MS-2026-NAPA-7821A',
                'crypto_hash' => 'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855',
                'batch_number' => 'SQ-NPA-2026B1',
                'expiry_date' => '2028-01-14',
                'scan_count' => 0,
                'lifecycle_status' => 'on_shelf',
                'scratch_pin' => '9482',
                'is_flagged_counterfeit' => 0,
                'created_at' => date('Y-m-d H:i:s', strtotime('-2 days'))
            ],
            [
                'id' => 3,
                'barcode_serial' => 'MS-2026-NAPA-SHELF-102',
                'crypto_hash' => '7a8b9c0d1e2f3a4b5c6d7e8f9a0b1c2d3e4f5a6b7c8d9e0f1a2b3c4d5e6f7a8b',
                'batch_number' => 'SQ-NPA-2026B1',
                'expiry_date' => '2028-01-14',
                'scan_count' => 3,
                'lifecycle_status' => 'on_shelf',
                'scratch_pin' => '7731',
                'is_flagged_counterfeit' => 0,
                'created_at' => date('Y-m-d H:i:s', strtotime('-1 day'))
            ]
        ];
    } elseif ($medicineId == 2) {
        $barcodes = [
            [
                'id' => 2,
                'barcode_serial' => 'MS-2026-SECLO-9914C',
                'crypto_hash' => 'b94d27b9934d3e08a52e52d7da7dabfac484efe37a5380ee9088f7ace2efcde9',
                'batch_number' => 'SQ-SCL-2026A4',
                'expiry_date' => '2028-02-09',
                'scan_count' => 0,
                'lifecycle_status' => 'on_shelf',
                'scratch_pin' => '5510',
                'is_flagged_counterfeit' => 0,
                'created_at' => date('Y-m-d H:i:s', strtotime('-3 days'))
            ],
            [
                'id' => 4,
                'barcode_serial' => 'MS-2026-SECL-SOLD-555',
                'crypto_hash' => '1a2b3c4d5e6f7a8b9c0d1e2f3a4b5c6d7e8f9a0b1c2d3e4f5a6b7c8d9e0f1a2b',
                'batch_number' => 'SQ-SCL-2026A4',
                'expiry_date' => '2028-02-09',
                'scan_count' => 2,
                'lifecycle_status' => 'sold_claimed',
                'scratch_pin' => '8834',
                'is_flagged_counterfeit' => 0,
                'created_at' => '2026-04-10 14:20:00'
            ]
        ];
    }
}

// Calculate metrics
$totalBarcodes = count($barcodes);
$onShelfCount = 0;
$soldClaimedCount = 0;
$scannedBrowsingCount = 0;
$flaggedCount = 0;

foreach ($barcodes as $b) {
    if (($b['is_flagged_counterfeit'] ?? 0) == 1) {
        $flaggedCount++;
    } elseif (($b['lifecycle_status'] ?? 'on_shelf') === 'sold_claimed') {
        $soldClaimedCount++;
    } elseif (($b['scan_count'] ?? 0) > 0) {
        $scannedBrowsingCount++;
    } else {
        $onShelfCount++;
    }
}

require_once __DIR__ . "/../partials/header.php";
?>

<main class="main-wrapper">
  <!-- Top Navigation & Actions -->
  <div class="no-print" style="margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
    <a href="/manufacturer" class="btn btn-secondary" style="font-weight: 700;">
      ← Back to Manufacturer Portal
    </a>
    <div style="display: flex; gap: 0.75rem;">
      <button class="btn btn-primary" onclick="window.print()">
        🖨️ Print Medicine Barcode Sheet (A4)
      </button>
    </div>
  </div>

  <!-- Medicine Group Profile Card -->
  <section class="panel-card" style="margin-bottom: 2rem; border-top: 4px solid var(--primary);">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem;">
      <div>
        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.35rem;">
          <span class="badge-tag">MEDICINE GROUP BARCODE ARCHIVE</span>
          <span class="status-pill <?php echo ($medicine['approval_status'] ?? 'approved') === 'approved' ? 'status-seized' : 'status-investigating'; ?>">
            <?php echo ($medicine['approval_status'] ?? 'approved') === 'approved' ? '✓ Approved by DGDA' : '⏳ Pending DGDA Review'; ?>
          </span>
        </div>
        <h1 style="font-size: 2.1rem; font-weight: 800; color: var(--gray-900); margin: 0.25rem 0;">
          💊 <?php echo htmlspecialchars($medicine['brand_name'] . ' ' . $medicine['strength']); ?>
        </h1>
        <div style="font-size: 1.05rem; color: var(--primary); font-weight: 700;">
          <?php echo htmlspecialchars($medicine['generic_name']); ?> • <?php echo htmlspecialchars($medicine['dosage_form'] ?? 'Tablet'); ?>
        </div>
      </div>

      <div style="text-align: right; background: var(--bg-surface-elevated); padding: 0.75rem 1.25rem; border-radius: var(--radius-sm); border: 1px solid var(--border-color);">
        <div style="font-size: 0.75rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase;">Licensed Manufacturer</div>
        <strong style="font-size: 0.95rem; color: var(--text-main); display: block;"><?php echo htmlspecialchars($medicine['manufacturer_name'] ?? 'Pharma PLC'); ?></strong>
        <span style="font-size: 0.8rem; color: var(--text-muted);">DGDA License: <code><?php echo htmlspecialchars($medicine['dgda_license_no'] ?? 'DGDA-MFG'); ?></code></span>
      </div>
    </div>

    <!-- Medicine Clinical Specs -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-top: 1.5rem; padding-top: 1.25rem; border-top: 1px solid var(--border-color);">
      <div>
        <div style="font-size: 0.75rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Govt DAR No</div>
        <strong style="font-size: 0.9rem; color: var(--text-main);"><?php echo htmlspecialchars($medicine['dar_number'] ?? 'N/A'); ?></strong>
      </div>
      <div>
        <div style="font-size: 0.75rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Official MRP</div>
        <strong style="font-size: 0.9rem; color: var(--primary);">৳ <?php echo number_format($medicine['mrp_bdt'] ?? 0, 2); ?> / Unit</strong>
      </div>
      <div>
        <div style="font-size: 0.75rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Pack Size</div>
        <strong style="font-size: 0.9rem; color: var(--text-main);"><?php echo htmlspecialchars($medicine['pack_size'] ?? '10 x 10 Strip'); ?></strong>
      </div>
      <div>
        <div style="font-size: 0.75rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Total Barcodes Created</div>
        <strong style="font-size: 1.1rem; color: var(--success); font-weight: 800;"><?php echo $totalBarcodes; ?> Barcodes</strong>
      </div>
    </div>

    <!-- Indications and Dosage -->
    <div style="margin-top: 1.25rem; display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; font-size: 0.85rem;">
      <div style="background: var(--bg-surface-elevated); padding: 0.85rem 1rem; border-radius: var(--radius-sm); border: 1px solid var(--border-subtle);">
        <strong style="color: #065f46; display: block; margin-bottom: 0.25rem;">✨ Indications & Benefits:</strong>
        <p style="margin: 0; color: var(--text-muted); line-height: 1.4;"><?php echo htmlspecialchars($medicine['indications_merits'] ?? 'Registered therapeutic indication.'); ?></p>
      </div>
      <div style="background: var(--bg-surface-elevated); padding: 0.85rem 1rem; border-radius: var(--radius-sm); border: 1px solid var(--border-subtle);">
        <strong style="color: #92400e; display: block; margin-bottom: 0.25rem;">⚠️ Precautions & Side Effects:</strong>
        <p style="margin: 0; color: var(--text-muted); line-height: 1.4;"><?php echo htmlspecialchars($medicine['side_effects_demerits'] ?? 'Standard clinical precautions.'); ?></p>
      </div>
    </div>
  </section>

  <!-- Barcode Stats Counters -->
  <div class="no-print" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
    <div class="panel-card" style="padding: 1.25rem; border-left: 4px solid var(--primary);">
      <div class="hero-stat-label">Total Barcodes Generated</div>
      <div style="font-size: 1.85rem; font-weight: 800; color: var(--gray-900);"><?php echo $totalBarcodes; ?></div>
      <div style="font-size: 0.8rem; color: var(--text-muted);">Registered in national ledger</div>
    </div>
    <div class="panel-card" style="padding: 1.25rem; border-left: 4px solid #10b981;">
      <div class="hero-stat-label">On-Shelf / Fresh Unused</div>
      <div style="font-size: 1.85rem; font-weight: 800; color: #059669;"><?php echo $onShelfCount; ?></div>
      <div style="font-size: 0.8rem; color: var(--text-muted);">0 scans • Ready for sale</div>
    </div>
    <div class="panel-card" style="padding: 1.25rem; border-left: 4px solid #0284c7;">
      <div class="hero-stat-label">In-Store Shelf Browsing</div>
      <div style="font-size: 1.85rem; font-weight: 800; color: #0284c7;"><?php echo $scannedBrowsingCount; ?></div>
      <div style="font-size: 0.8rem; color: var(--text-muted);">Pre-purchase scans detected</div>
    </div>
    <div class="panel-card" style="padding: 1.25rem; border-left: 4px solid #8b5cf6;">
      <div class="hero-stat-label">Purchased & Claimed</div>
      <div style="font-size: 1.85rem; font-weight: 800; color: #7c3aed;"><?php echo $soldClaimedCount; ?></div>
      <div style="font-size: 0.8rem; color: var(--text-muted);">Ownership locked to buyer</div>
    </div>
  </div>

  <!-- Barcode Records Grid / Printable Label Sheet -->
  <section class="panel-card printable-section">
    <div class="panel-header printable-header">
      <div>
        <h2 class="panel-title"><span>🏷️</span> Barcodes Generated for <?php echo htmlspecialchars($medicine['brand_name']); ?></h2>
        <p style="font-size: 0.85rem; color: var(--text-muted);">
          Cryptographic single-use serials generated for packaging stickering and tracking
        </p>
      </div>
      <div class="no-print">
        <button class="btn btn-secondary" onclick="window.print()">
          🖨️ Print Label Sheet (A4)
        </button>
      </div>
    </div>

    <?php if (empty($barcodes)): ?>
      <div style="text-align: center; color: var(--text-muted); padding: 3rem; background: var(--bg-surface-elevated); border-radius: var(--radius-sm); border: 1px dashed var(--border-subtle);">
        No barcodes generated yet for this medicine group.<br>
        <a href="/manufacturer" class="btn btn-primary" style="margin-top: 1rem; display: inline-block;">
          ⚡ Generate Barcodes on Manufacturer Portal
        </a>
      </div>
    <?php else: ?>
      <div id="barcode-sheet-container" class="barcode-sheet-grid">
        <?php foreach ($barcodes as $idx => $b): ?>
          <div class="barcode-label">
            <div class="label-header">
              <span>💊 <?php echo htmlspecialchars($medicine['brand_name'] . ' ' . $medicine['strength']); ?></span>
              <span class="label-batch"><?php echo htmlspecialchars($b['batch_number'] ?? 'BATCH-01'); ?></span>
            </div>
            
            <svg id="med-barcode-<?php echo $idx; ?>"></svg>
            
            <div class="label-footer">
              <div>Auth: <code><?php echo htmlspecialchars(substr($b['crypto_hash'] ?? 'SHA256', 0, 16)); ?>...</code></div>
              <?php if (!empty($b['scratch_pin'])): ?>
                <div>PIN: <strong><?php echo htmlspecialchars($b['scratch_pin']); ?></strong></div>
              <?php endif; ?>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.72rem; padding: 0.25rem 0.5rem; background: #f8fafc; border-top: 1px solid #e2e8f0;">
              <span>Status: <strong><?php echo ($b['lifecycle_status'] ?? 'on_shelf') === 'sold_claimed' ? '🔒 Sold & Claimed' : (($b['scan_count'] ?? 0) > 0 ? '🛒 In-Store Browsing (' . $b['scan_count'] . ')' : '🟢 On Shelf (Fresh)'); ?></strong></span>
            </div>

            <div class="label-actions no-print">
              <button class="btn-label-action" onclick="navigator.clipboard.writeText('<?php echo htmlspecialchars($b['barcode_serial']); ?>').then(()=>alert('Copied barcode: <?php echo htmlspecialchars($b['barcode_serial']); ?>'))">
                📋 Copy
              </button>
              <a href="/?barcode=<?php echo urlencode($b['barcode_serial']); ?>" target="_blank" class="btn-label-action">
                🔍 Verify in Scanner
              </a>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>
</main>

<script>
document.addEventListener('DOMContentLoaded', () => {
  if (window.barcodeEngine) {
    <?php foreach ($barcodes as $idx => $b): ?>
      window.barcodeEngine.renderBarcode('#med-barcode-<?php echo $idx; ?>', '<?php echo addslashes($b['barcode_serial']); ?>', {
        width: 1.8,
        height: 40,
        fontSize: 11,
        background: '#ffffff',
        lineColor: '#000000'
      });
    <?php endforeach; ?>
  }
});
</script>

<?php
require_once __DIR__ . "/../partials/footer.php";
?>

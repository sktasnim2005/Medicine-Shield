<?php
require_once __DIR__ . "/../config/auth.php";

// Strict Role Guard: Only pharmaceutical manufacturers permitted
Auth::requireRole('manufacturer');

$currentUser = Auth::getCurrentUser();
$pdo = Database::getConnection();

$mfgId = $currentUser['manufacturer_id'] ?? null;

$medicines = [];
$batches = [];
$totalBarcodes = 0;

if ($pdo) {
    try {
        if ($mfgId) {
            $mStmt = $pdo->prepare("
                SELECT m.*, mfg.name AS manufacturer_name,
                       (SELECT COUNT(*) FROM barcodes_ledger bl WHERE bl.medicine_id = m.id) AS barcode_count
                FROM medicines m 
                JOIN manufacturers mfg ON m.manufacturer_id = mfg.id
                WHERE m.manufacturer_id = :mid
                ORDER BY m.id DESC
            ");
            $mStmt->execute([':mid' => $mfgId]);
            $medicines = $mStmt->fetchAll();

            $bStmt = $pdo->prepare("
                SELECT b.*, m.brand_name 
                FROM batches b 
                JOIN medicines m ON b.medicine_id = m.id 
                WHERE m.manufacturer_id = :mid
                ORDER BY b.id DESC
            ");
            $bStmt->execute([':mid' => $mfgId]);
            $batches = $bStmt->fetchAll();

            $bcStmt = $pdo->prepare("
                SELECT COUNT(*) FROM barcodes_ledger bl
                JOIN medicines m ON bl.medicine_id = m.id
                WHERE m.manufacturer_id = :mid
            ");
            $bcStmt->execute([':mid' => $mfgId]);
            $totalBarcodes = intval($bcStmt->fetchColumn() ?: 0);
        } else {
            $medicines = $pdo->query("
                SELECT m.*, mfg.name AS manufacturer_name,
                       (SELECT COUNT(*) FROM barcodes_ledger bl WHERE bl.medicine_id = m.id) AS barcode_count
                FROM medicines m 
                JOIN manufacturers mfg ON m.manufacturer_id = mfg.id
                ORDER BY m.id DESC
            ")->fetchAll();

            $batches = $pdo->query("
                SELECT b.*, m.brand_name 
                FROM batches b 
                JOIN medicines m ON b.medicine_id = m.id 
                ORDER BY b.id DESC
            ")->fetchAll();

            $totalBarcodes = intval($pdo->query("SELECT COUNT(*) FROM barcodes_ledger")->fetchColumn() ?: 0);
        }
    } catch (Exception $e) {
        $medicines = [];
    }
}

// Fallback seed medicines if DB offline
if (empty($medicines)) {
    $medicines = [
        [
            'id' => 1,
            'brand_name' => 'Napa Extra',
            'generic_name' => 'Paracetamol + Caffeine',
            'strength' => '500mg + 65mg',
            'dosage_form' => 'Tablet',
            'dar_number' => 'DAR-024-0312-054',
            'mrp_bdt' => 2.50,
            'approval_status' => 'approved',
            'manufacturer_name' => 'Square Pharmaceuticals PLC',
            'barcode_count' => 12
        ],
        [
            'id' => 2,
            'brand_name' => 'Seclo 20',
            'generic_name' => 'Omeprazole',
            'strength' => '20mg',
            'dosage_form' => 'Capsule',
            'dar_number' => 'DAR-024-0089-012',
            'mrp_bdt' => 6.00,
            'approval_status' => 'approved',
            'manufacturer_name' => 'Square Pharmaceuticals PLC',
            'barcode_count' => 8
        ],
        [
            'id' => 6,
            'brand_name' => 'Ciprocin 500',
            'generic_name' => 'Ciprofloxacin',
            'strength' => '500mg',
            'dosage_form' => 'Tablet',
            'dar_number' => 'DAR-024-0156-077',
            'mrp_bdt' => 15.00,
            'approval_status' => 'approved',
            'manufacturer_name' => 'Square Pharmaceuticals PLC',
            'barcode_count' => 6
        ]
    ];
    $totalBarcodes = 26;
}

if (empty($batches)) {
    $batches = [
        ['id' => 1, 'batch_number' => 'SQ-NPA-2026B1', 'brand_name' => 'Napa Extra', 'expiry_date' => '2028-01-14', 'status' => 'Active'],
        ['id' => 2, 'batch_number' => 'SQ-SCL-2026A4', 'brand_name' => 'Seclo 20', 'expiry_date' => '2028-02-09', 'status' => 'Active'],
        ['id' => 6, 'batch_number' => 'SQ-CIP-2025Z1', 'brand_name' => 'Ciprocin 500', 'expiry_date' => '2027-10-04', 'status' => 'Active']
    ];
}

// Calculate summary metrics
$totalMedCount = count($medicines);
$approvedMedCount = 0;
$pendingMedCount = 0;
$approvedMedicines = [];

foreach ($medicines as $m) {
    if (($m['approval_status'] ?? 'approved') === 'approved') {
        $approvedMedCount++;
        $approvedMedicines[] = $m;
    } else {
        $pendingMedCount++;
    }
}

require_once __DIR__ . "/../partials/header.php";
?>

<main class="main-wrapper">
  <!-- Header Bar -->
  <div style="margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
    <div>
      <span class="badge-tag">PHARMACEUTICAL PRODUCTION PORTAL</span>
      <h1 style="font-size: 2rem; font-weight: 800; color: var(--gray-900); margin-top: 0.5rem;">
        🏭 <?php echo htmlspecialchars($currentUser['manufacturer_name'] ?? 'Pharmaceutical Manufacturer'); ?>
      </h1>
      <p style="color: var(--gray-600); font-size: 0.95rem;">
        Authorized Production Unit • License: <code><?php echo htmlspecialchars($currentUser['badge_no'] ?? 'DGDA-MFG-00124'); ?></code>
      </p>
    </div>

    <div style="display: flex; gap: 0.75rem; align-items: center; flex-wrap: wrap;">
      <button class="btn btn-primary" onclick="openRequestModal()">
        ➕ Request New Medicine Registration
      </button>
      <div style="background: var(--success-bg); border: 1px solid #a7f3d0; border-radius: var(--radius-sm); padding: 0.6rem 1rem; font-size: 0.85rem; color: #065f46; font-weight: 700;">
        🔒 DGDA Ledger Connected
      </div>
    </div>
  </div>

  <!-- Summary Cards Row -->
  <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 1.25rem; margin-bottom: 2.5rem;">
    <div class="panel-card" style="border-left: 4px solid var(--primary); padding: 1.25rem;">
      <div class="hero-stat-label">Total Medicines Created</div>
      <div style="font-size: 2rem; font-weight: 800; color: var(--gray-900);"><?php echo $totalMedCount; ?></div>
      <div style="font-size: 0.8rem; color: var(--text-muted);">In manufacturer portfolio</div>
    </div>

    <div class="panel-card" style="border-left: 4px solid #10b981; padding: 1.25rem;">
      <div class="hero-stat-label">Approved by DGDA</div>
      <div style="font-size: 2rem; font-weight: 800; color: #059669;"><?php echo $approvedMedCount; ?></div>
      <div style="font-size: 0.8rem; color: var(--text-muted);">Active for serialization & public</div>
    </div>

    <div class="panel-card" style="border-left: 4px solid #f59e0b; padding: 1.25rem;">
      <div class="hero-stat-label">Pending DGDA Review</div>
      <div style="font-size: 2rem; font-weight: 800; color: #d97706;"><?php echo $pendingMedCount; ?></div>
      <div style="font-size: 0.8rem; color: var(--text-muted);">Submitted registration requests</div>
    </div>

    <div class="panel-card" style="border-left: 4px solid #8b5cf6; padding: 1.25rem;">
      <div class="hero-stat-label">Total Barcodes Generated</div>
      <div style="font-size: 2rem; font-weight: 800; color: #7c3aed;"><?php echo $totalBarcodes; ?></div>
      <div style="font-size: 0.8rem; color: var(--text-muted);">Across all product batches</div>
    </div>
  </div>

  <!-- Medicine Groups & Barcode Archives Table -->
  <section class="panel-card" style="margin-bottom: 2.5rem;">
    <div class="panel-header">
      <div>
        <h2 class="panel-title"><span>💊</span> Medicine Groups & Barcode Archives</h2>
        <p style="font-size: 0.85rem; color: var(--text-muted); margin: 0.25rem 0 0 0;">
          Select any medicine to view all its previous barcodes, batch history, and dedicated printable labels on its own individual archive page.
        </p>
      </div>
      <button class="btn btn-secondary" style="font-size: 0.85rem;" onclick="openRequestModal()">
        ➕ New Registration Request
      </button>
    </div>

    <div style="overflow-x: auto;">
      <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
        <thead>
          <tr style="background: var(--bg-surface-elevated); border-bottom: 2px solid var(--border-color); color: var(--text-muted);">
            <th style="padding: 0.85rem 1rem; text-align: left;">Medicine Brand</th>
            <th style="padding: 0.85rem 1rem; text-align: left;">Generic & Form</th>
            <th style="padding: 0.85rem 1rem; text-align: left;">DAR Registration No</th>
            <th style="padding: 0.85rem 1rem; text-align: left;">MRP (BDT)</th>
            <th style="padding: 0.85rem 1rem; text-align: center;">DGDA Status</th>
            <th style="padding: 0.85rem 1rem; text-align: center;">Total Barcodes</th>
            <th style="padding: 0.85rem 1rem; text-align: right;">Dedicated Archive</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($medicines as $med): 
            $isApp = ($med['approval_status'] ?? 'approved') === 'approved';
          ?>
            <tr style="border-bottom: 1px solid var(--border-color);">
              <td style="padding: 0.85rem 1rem; font-weight: 800; color: var(--text-main);">
                💊 <?php echo htmlspecialchars($med['brand_name'] . ' (' . $med['strength'] . ')'); ?>
              </td>
              <td style="padding: 0.85rem 1rem; color: var(--text-muted);">
                <?php echo htmlspecialchars($med['generic_name']); ?> • <span style="font-size: 0.8rem;"><?php echo htmlspecialchars($med['dosage_form'] ?? 'Tablet'); ?></span>
              </td>
              <td style="padding: 0.85rem 1rem; font-family: monospace; font-size: 0.85rem;">
                <?php echo htmlspecialchars($med['dar_number']); ?>
              </td>
              <td style="padding: 0.85rem 1rem; font-weight: 700; color: var(--primary);">
                ৳ <?php echo number_format($med['mrp_bdt'] ?? 0, 2); ?>
              </td>
              <td style="padding: 0.85rem 1rem; text-align: center;">
                <span class="status-pill <?php echo $isApp ? 'status-seized' : 'status-investigating'; ?>" style="font-size: 0.75rem;">
                  <?php echo $isApp ? '✓ Approved' : '⏳ Pending DGDA Review'; ?>
                </span>
              </td>
              <td style="padding: 0.85rem 1rem; text-align: center; font-weight: 800; color: var(--success);">
                <?php echo intval($med['barcode_count'] ?? 0); ?>
              </td>
              <td style="padding: 0.85rem 1rem; text-align: right;">
                <a href="/manufacturer/barcodes?id=<?php echo $med['id']; ?>" class="btn btn-primary" style="padding: 0.4rem 0.85rem; font-size: 0.82rem; text-decoration: none; display: inline-flex; align-items: center; gap: 0.35rem;">
                  📂 View Barcodes Archive →
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>

  <!-- Barcode Batch Generation Section -->
  <div class="verification-grid">
    <!-- Barcode Batch Generator Form -->
    <div class="panel-card">
      <div class="panel-header">
        <h2 class="panel-title"><span>🏷️</span> Generate Serialized Barcodes</h2>
        <span class="badge-tag">SHA-256 Ledger</span>
      </div>

      <form id="barcode-gen-form" onsubmit="generateBatchBarcodes(event)">
        <div style="margin-bottom: 1rem;">
          <label style="font-size: 0.85rem; font-weight: 700; color: var(--gray-700); display: block; margin-bottom: 0.25rem;">Select Approved Medicine:</label>
          <select id="gen-medicine-id" class="input-field" style="width: 100%;">
            <?php foreach ($approvedMedicines as $med): ?>
              <option value="<?php echo $med['id']; ?>">
                <?php echo htmlspecialchars($med['brand_name'] . ' (' . $med['strength'] . ') - ' . $med['manufacturer_name']); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div style="margin-bottom: 1rem;">
          <label style="font-size: 0.85rem; font-weight: 700; color: var(--gray-700); display: block; margin-bottom: 0.25rem;">Select Active Production Batch:</label>
          <select id="gen-batch-id" class="input-field" style="width: 100%;">
            <?php foreach ($batches as $bat): ?>
              <option value="<?php echo $bat['id']; ?>">
                <?php echo htmlspecialchars($bat['batch_number'] . ' [' . $bat['brand_name'] . '] - Exp: ' . $bat['expiry_date']); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div style="margin-bottom: 1.5rem;">
          <label style="font-size: 0.85rem; font-weight: 700; color: var(--gray-700); display: block; margin-bottom: 0.25rem;">Number of Barcodes to Generate:</label>
          <select id="gen-quantity" class="input-field" style="width: 100%;">
            <option value="6">6 Barcodes (Sample Sheet)</option>
            <option value="12" selected>12 Barcodes (Standard Blister Strip Sheet)</option>
            <option value="24">24 Barcodes (Carton Box Pack)</option>
          </select>
        </div>

        <button type="submit" class="btn btn-primary btn-block">
          ⚡ Generate & Register in Immutable Ledger
        </button>
      </form>
    </div>

    <!-- Live Batch Production Status -->
    <div class="panel-card">
      <div class="panel-header">
        <h2 class="panel-title"><span>📋</span> Registered Production Batches</h2>
        <span class="badge-tag">DGDA Active</span>
      </div>

      <div style="overflow-y: auto; max-height: 320px;">
        <table style="width: 100%; border-collapse: collapse; font-size: 0.85rem;">
          <thead>
            <tr style="background: var(--gray-100); border-bottom: 2px solid var(--gray-200); color: var(--gray-600);">
              <th style="padding: 0.5rem; text-align: left;">Batch No</th>
              <th style="padding: 0.5rem; text-align: left;">Brand</th>
              <th style="padding: 0.5rem; text-align: left;">Expiry</th>
              <th style="padding: 0.5rem; text-align: left;">Status</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($batches as $bat): ?>
              <tr style="border-bottom: 1px solid var(--gray-200);">
                <td style="padding: 0.5rem; font-weight: 700;"><?php echo htmlspecialchars($bat['batch_number']); ?></td>
                <td style="padding: 0.5rem;"><?php echo htmlspecialchars($bat['brand_name']); ?></td>
                <td style="padding: 0.5rem;"><?php echo htmlspecialchars($bat['expiry_date']); ?></td>
                <td style="padding: 0.5rem;">
                  <span class="status-pill status-seized" style="font-size: 0.7rem;"><?php echo htmlspecialchars($bat['status']); ?></span>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Printable Barcode Sticker Sheets Section -->
  <section class="panel-card printable-section" style="margin-top: 2rem;">
    <div class="panel-header printable-header">
      <div>
        <h2 class="panel-title"><span>🖨️</span> Printable 1D Barcode Label Sheet (A4 Standard)</h2>
        <p style="font-size: 0.85rem; color: var(--text-muted);">Ready for industrial high-speed thermal label printer or packaging stickering</p>
      </div>
      <div class="no-print" style="display: flex; gap: 0.5rem;">
        <button class="btn btn-secondary" onclick="window.print()">🖨️ Print Barcode Labels (A4)</button>
      </div>
    </div>

    <div id="barcode-sheet-container" class="barcode-sheet-grid">
      <div style="grid-column: 1 / -1; text-align: center; color: var(--text-muted); padding: 2rem; background: var(--bg-surface-elevated); border-radius: var(--radius-sm); border: 1px dashed var(--border-subtle);">
        Click <strong>"⚡ Generate & Register in Immutable Ledger"</strong> above to produce cryptographic Code128 barcodes.
      </div>
    </div>
  </section>
</main>

<!-- Request New Medicine Registration Modal -->
<div id="med-request-modal" class="modal-backdrop">
  <div class="result-card" style="max-width: 650px;">
    <div class="result-banner banner-genuine" style="padding: 1.5rem 2rem;">
      <div class="banner-icon">📝</div>
      <div class="banner-content">
        <h2>Request New Medicine Registration</h2>
        <p>Submit pharmaceutical specifications to DGDA for regulatory review & approval.</p>
      </div>
    </div>

    <form class="result-body" onsubmit="submitMedicineRequest(event)">
      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
        <div>
          <label style="font-size: 0.85rem; font-weight: 700; color: var(--gray-700);">Brand Name: *</label>
          <input type="text" id="req-brand-name" class="input-field" style="width: 100%; margin-top: 0.25rem;" required placeholder="e.g. Napa Extra" />
        </div>
        <div>
          <label style="font-size: 0.85rem; font-weight: 700; color: var(--gray-700);">Generic Name: *</label>
          <input type="text" id="req-generic-name" class="input-field" style="width: 100%; margin-top: 0.25rem;" required placeholder="e.g. Paracetamol + Caffeine" />
        </div>
      </div>

      <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
        <div>
          <label style="font-size: 0.85rem; font-weight: 700; color: var(--gray-700);">Strength: *</label>
          <input type="text" id="req-strength" class="input-field" style="width: 100%; margin-top: 0.25rem;" required placeholder="e.g. 500mg + 65mg" />
        </div>
        <div>
          <label style="font-size: 0.85rem; font-weight: 700; color: var(--gray-700);">Dosage Form: *</label>
          <select id="req-dosage-form" class="input-field" style="width: 100%; margin-top: 0.25rem;">
            <option value="Tablet">Tablet</option>
            <option value="Capsule">Capsule</option>
            <option value="Syrup">Syrup</option>
            <option value="Injection">Injection</option>
            <option value="Suspension">Suspension</option>
            <option value="Eye Drops">Eye Drops</option>
            <option value="Inhaler">Inhaler</option>
          </select>
        </div>
        <div>
          <label style="font-size: 0.85rem; font-weight: 700; color: var(--gray-700);">Govt DAR Number: *</label>
          <input type="text" id="req-dar-number" class="input-field" style="width: 100%; margin-top: 0.25rem;" required placeholder="e.g. DAR-024-0312-054" />
        </div>
      </div>

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
        <div>
          <label style="font-size: 0.85rem; font-weight: 700; color: var(--gray-700);">Proposed MRP (BDT): *</label>
          <input type="number" step="0.10" id="req-mrp" class="input-field" style="width: 100%; margin-top: 0.25rem;" required placeholder="2.50" />
        </div>
        <div>
          <label style="font-size: 0.85rem; font-weight: 700; color: var(--gray-700);">Packaging Pack Size: *</label>
          <input type="text" id="req-pack-size" class="input-field" style="width: 100%; margin-top: 0.25rem;" required placeholder="e.g. 10 x 10 Strip" />
        </div>
      </div>

      <div style="margin-bottom: 1rem;">
        <label style="font-size: 0.85rem; font-weight: 700; color: var(--gray-700);">Indications & Clinical Benefits (Merits): *</label>
        <textarea id="req-indications" class="input-field" style="width: 100%; height: 65px; margin-top: 0.25rem;" required placeholder="Clinical indications registered with DGDA..."></textarea>
      </div>

      <div style="margin-bottom: 1rem;">
        <label style="font-size: 0.85rem; font-weight: 700; color: var(--gray-700);">Precautions & Side Effects (Demerits): *</label>
        <textarea id="req-side-effects" class="input-field" style="width: 100%; height: 65px; margin-top: 0.25rem;" required placeholder="Adverse reactions, warnings..."></textarea>
      </div>

      <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
        <button type="button" class="btn btn-secondary btn-block" onclick="closeRequestModal()">Cancel</button>
        <button type="submit" id="btn-submit-med" class="btn btn-primary btn-block">🚀 Submit to DGDA for Review</button>
      </div>
    </form>
  </div>
</div>

<script>
function openRequestModal() {
  document.getElementById('med-request-modal').classList.add('open');
}

function closeRequestModal() {
  document.getElementById('med-request-modal').classList.remove('open');
}

async function submitMedicineRequest(e) {
  e.preventDefault();
  const btn = document.getElementById('btn-submit-med');
  btn.disabled = true;
  btn.innerText = "Submitting to DGDA...";

  const payload = {
    brand_name: document.getElementById('req-brand-name').value.trim(),
    generic_name: document.getElementById('req-generic-name').value.trim(),
    strength: document.getElementById('req-strength').value.trim(),
    dosage_form: document.getElementById('req-dosage-form').value,
    dar_number: document.getElementById('req-dar-number').value.trim(),
    mrp_bdt: parseFloat(document.getElementById('req-mrp').value),
    pack_size: document.getElementById('req-pack-size').value.trim(),
    indications_merits: document.getElementById('req-indications').value.trim(),
    side_effects_demerits: document.getElementById('req-side-effects').value.trim(),
    dosage_instructions: "As directed by registered physician.",
    precautions: "Store in a cool, dry place below 30°C."
  };

  try {
    const res = await fetch('/api/request_medicine.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });
    const data = await res.json();
    if (data.success) {
      alert(data.message);
      location.reload();
    } else {
      alert("Error: " + (data.error || "Unable to submit request."));
      btn.disabled = false;
      btn.innerText = "🚀 Submit to DGDA for Review";
    }
  } catch (err) {
    console.error(err);
    alert("Network error submitting request.");
    btn.disabled = false;
    btn.innerText = "🚀 Submit to DGDA for Review";
  }
}

async function generateBatchBarcodes(e) {
  e.preventDefault();
  const medId = document.getElementById('gen-medicine-id').value;
  const batchId = document.getElementById('gen-batch-id').value;
  const qty = document.getElementById('gen-quantity').value;

  const container = document.getElementById('barcode-sheet-container');
  container.innerHTML = '<div style="grid-column: 1 / -1; text-align: center; padding: 2rem; color: var(--text-muted);">Generating cryptographic barcodes...</div>';

  try {
    const res = await fetch('/api/generate_barcode.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ medicine_id: medId, batch_id: batchId, quantity: qty })
    });
    const data = await res.json();

    if (data.success && data.barcodes) {
      let html = '';
      data.barcodes.forEach((b, idx) => {
        html += `
          <div class="barcode-label">
            <div class="label-header">
              <span>💊 ${b.brand_name}</span>
              <span class="label-batch">${b.batch_number}</span>
            </div>
            <svg id="gen-barcode-${idx}"></svg>
            <div class="label-footer">
              <div>Auth: <code>${b.crypto_hash}</code></div>
              ${b.scratch_pin ? `<div>PIN: <strong>${b.scratch_pin}</strong></div>` : ''}
            </div>
            <div class="label-actions no-print">
              <button class="btn-label-action" onclick="navigator.clipboard.writeText('${b.barcode_serial}').then(()=>alert('Copied barcode: ${b.barcode_serial}'))" title="Copy Barcode">
                📋 Copy
              </button>
              <a href="/?barcode=${encodeURIComponent(b.barcode_serial)}" target="_blank" class="btn-label-action" title="Scan and verify in consumer portal">
                🔍 Verify in Scanner
              </a>
            </div>
          </div>
        `;
      });
      container.innerHTML = html;

      // Render Barcodes via JsBarcode
      data.barcodes.forEach((b, idx) => {
        if (window.barcodeEngine) {
          window.barcodeEngine.renderBarcode('#gen-barcode-' + idx, b.barcode_serial, {
            width: 1.8,
            height: 40,
            fontSize: 11,
            background: '#ffffff',
            lineColor: '#000000'
          });
        }
      });
    }
  } catch (err) {
    console.error(err);
    alert('Error generating barcodes');
  }
}
</script>

<?php
require_once __DIR__ . "/../partials/footer.php";
?>

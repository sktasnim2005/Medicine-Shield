<?php
require_once __DIR__ . "/../config/auth.php";

// Strict Role Guard: Only DGDA / Government Law Enforcement permitted
Auth::requireRole('dgda');

$currentUser = Auth::getCurrentUser();
$pdo = Database::getConnection();

// Fetch metrics
$totalScans = 36;
$genuineScans = 28;
$duplicates = 5;
$fakes = 3;
$reports = [];
$pendingMedicines = [];

if ($pdo) {
    try {
        $totalScans = $pdo->query("SELECT COUNT(*) FROM scan_logs")->fetchColumn() ?: 36;
        $genuineScans = $pdo->query("SELECT COUNT(*) FROM scan_logs WHERE verification_status IN ('GENUINE', 'GENUINE_SHELF_BROWSING')")->fetchColumn() ?: 28;
        $duplicates = $pdo->query("SELECT COUNT(*) FROM scan_logs WHERE verification_status IN ('DUPLICATE_WARNING', 'ALREADY_SOLD_ALERT', 'CLONED_COUNTERFEIT')")->fetchColumn() ?: 5;
        $fakes = $pdo->query("SELECT COUNT(*) FROM scan_logs WHERE verification_status = 'INVALID_FAKE'")->fetchColumn() ?: 3;
        $reports = $pdo->query("SELECT * FROM counterfeit_reports ORDER BY id DESC LIMIT 15")->fetchAll();

        // Fetch pending medicine registration applications from manufacturers
        $pmStmt = $pdo->query("
            SELECT m.*, mfg.name AS manufacturer_name, mfg.dgda_license_no, mfg.headquarters
            FROM medicines m
            JOIN manufacturers mfg ON m.manufacturer_id = mfg.id
            WHERE m.approval_status = 'pending'
            ORDER BY m.id DESC
        ");
        $pendingMedicines = $pmStmt->fetchAll();
    } catch (Exception $e) {}
}

// Fallback demo pending medicines if DB offline
if (empty($pendingMedicines)) {
    $pendingMedicines = [
        [
            'id' => 7,
            'brand_name' => 'Bexitrol-F',
            'generic_name' => 'Salmeterol + Fluticasone',
            'strength' => '25mcg + 125mcg',
            'dosage_form' => 'Inhaler',
            'dar_number' => 'DAR-088-0341-019',
            'mrp_bdt' => 275.00,
            'pack_size' => '120 Puffs Canister',
            'indications_merits' => 'Regular treatment of asthma and symptomatic treatment of severe COPD.',
            'manufacturer_name' => 'Beximco Pharmaceuticals Ltd.',
            'dgda_license_no' => 'DGDA-MFG-00088',
            'created_at' => date('Y-m-d H:i:s', strtotime('-4 hours'))
        ]
    ];
}

require_once __DIR__ . "/../partials/header.php";
?>

<main class="main-wrapper">
  <div style="margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
    <div>
      <span class="badge-tag" style="background: #fee2e2; color: #991b1b;">GOVERNMENT DRUG REGULATION</span>
      <h1 style="font-size: 2rem; font-weight: 800; color: var(--gray-900); margin-top: 0.5rem;">
        ⚖️ DGDA National Anti-Counterfeit Command Center
      </h1>
      <p style="color: var(--gray-600); font-size: 0.95rem;">
        Logged in as: <strong><?php echo htmlspecialchars($currentUser['name']); ?></strong> (Badge ID: <code><?php echo htmlspecialchars($currentUser['badge_no'] ?? 'DGDA-INSPECT-77'); ?></code>)
      </p>
    </div>
    <div>
      <button class="btn btn-danger" onclick="alert('Enforcement Alert Broadcasted to All District Drug Inspectors!')">
        🚨 Broadcast High Alert
      </button>
    </div>
  </div>

  <!-- Key Metrics Row -->
  <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 1.25rem; margin-bottom: 2.5rem;">
    <div class="panel-card" style="border-left: 4px solid var(--primary); padding: 1.25rem;">
      <div class="hero-stat-label">Total Authentications</div>
      <div style="font-size: 2rem; font-weight: 800; color: var(--gray-900);"><?php echo $totalScans; ?></div>
      <div style="font-size: 0.8rem; color: #059669; margin-top: 0.25rem;">↑ 18% consumer growth</div>
    </div>

    <div class="panel-card" style="border-left: 4px solid var(--success); padding: 1.25rem;">
      <div class="hero-stat-label">Genuine Packs Cleared</div>
      <div style="font-size: 2rem; font-weight: 800; color: var(--success);"><?php echo $genuineScans; ?></div>
      <div style="font-size: 0.8rem; color: var(--gray-500); margin-top: 0.25rem;">Verified via Single-Scan</div>
    </div>

    <div class="panel-card" style="border-left: 4px solid var(--warning); padding: 1.25rem;">
      <div class="hero-stat-label">Duplicate Interceptions</div>
      <div style="font-size: 2rem; font-weight: 800; color: var(--warning);"><?php echo $duplicates; ?></div>
      <div style="font-size: 0.8rem; color: #b45309; margin-top: 0.25rem;">Reused Barcodes Flagged</div>
    </div>

    <div class="panel-card" style="border-left: 4px solid #f59e0b; padding: 1.25rem;">
      <div class="hero-stat-label">Pending Drug Approvals</div>
      <div style="font-size: 2rem; font-weight: 800; color: #d97706;"><?php echo count($pendingMedicines); ?></div>
      <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.25rem;">Manufacturer requests</div>
    </div>
  </div>

  <!-- Pending Medicine Registration Queue Section -->
  <section class="panel-card" style="margin-bottom: 2.5rem; border-top: 4px solid #d97706;">
    <div class="panel-header">
      <div>
        <h2 class="panel-title"><span>📝</span> Pharmaceutical Medicine Registration Requests</h2>
        <p style="font-size: 0.85rem; color: var(--text-muted); margin: 0.25rem 0 0 0;">
          Review and officially approve or reject new drug registration applications submitted by licensed pharmaceutical companies.
        </p>
      </div>
      <span class="status-pill status-investigating"><?php echo count($pendingMedicines); ?> Pending Application(s)</span>
    </div>

    <?php if (empty($pendingMedicines)): ?>
      <div style="text-align: center; color: var(--text-muted); padding: 2rem; background: var(--bg-surface-elevated); border-radius: var(--radius-sm); border: 1px dashed var(--border-subtle);">
        ✓ No pending medicine registration requests at this time. All submissions reviewed.
      </div>
    <?php else: ?>
      <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
          <thead>
            <tr style="background: var(--bg-surface-elevated); border-bottom: 2px solid var(--border-color); color: var(--text-muted);">
              <th style="padding: 0.85rem 1rem; text-align: left;">Manufacturer</th>
              <th style="padding: 0.85rem 1rem; text-align: left;">Brand & Strength</th>
              <th style="padding: 0.85rem 1rem; text-align: left;">Generic Formulation</th>
              <th style="padding: 0.85rem 1rem; text-align: left;">Proposed DAR No</th>
              <th style="padding: 0.85rem 1rem; text-align: left;">MRP (BDT)</th>
              <th style="padding: 0.85rem 1rem; text-align: center;">Clinical Indications</th>
              <th style="padding: 0.85rem 1rem; text-align: right;">DGDA Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($pendingMedicines as $pm): ?>
              <tr style="border-bottom: 1px solid var(--border-color);" id="row-pm-<?php echo $pm['id']; ?>">
                <td style="padding: 0.85rem 1rem;">
                  <strong style="color: var(--text-main); display: block;"><?php echo htmlspecialchars($pm['manufacturer_name']); ?></strong>
                  <span style="font-size: 0.78rem; color: var(--text-muted);">Lic: <code><?php echo htmlspecialchars($pm['dgda_license_no']); ?></code></span>
                </td>
                <td style="padding: 0.85rem 1rem; font-weight: 800; color: var(--text-main);">
                  💊 <?php echo htmlspecialchars($pm['brand_name'] . ' ' . $pm['strength']); ?>
                </td>
                <td style="padding: 0.85rem 1rem; color: var(--text-muted);">
                  <?php echo htmlspecialchars($pm['generic_name']); ?> • <span style="font-size: 0.8rem;"><?php echo htmlspecialchars($pm['dosage_form'] ?? 'Tablet'); ?></span>
                </td>
                <td style="padding: 0.85rem 1rem; font-family: monospace; font-size: 0.85rem;">
                  <?php echo htmlspecialchars($pm['dar_number']); ?>
                </td>
                <td style="padding: 0.85rem 1rem; font-weight: 700; color: var(--primary);">
                  ৳ <?php echo number_format($pm['mrp_bdt'] ?? 0, 2); ?>
                </td>
                <td style="padding: 0.85rem 1rem; text-align: center;">
                  <button class="btn btn-secondary" style="padding: 0.3rem 0.6rem; font-size: 0.78rem;" onclick="alert('Clinical Details for <?php echo htmlspecialchars(addslashes($pm['brand_name'])); ?>:\n\nIndications:\n<?php echo htmlspecialchars(addslashes($pm['indications_merits'] ?? '')); ?>')">
                    📋 View Specs
                  </button>
                </td>
                <td style="padding: 0.85rem 1rem; text-align: right;">
                  <div style="display: inline-flex; gap: 0.5rem;">
                    <button class="btn btn-success" style="padding: 0.4rem 0.8rem; font-size: 0.82rem;" onclick="reviewMedicine(<?php echo $pm['id']; ?>, 'approve')">
                      ✓ Approve
                    </button>
                    <button class="btn btn-danger" style="padding: 0.4rem 0.8rem; font-size: 0.82rem;" onclick="reviewMedicine(<?php echo $pm['id']; ?>, 'reject')">
                      ✕ Reject
                    </button>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </section>

  <!-- Bangladesh Counterfeit Hotspot Map & Incident Management -->
  <div class="verification-grid">
    <!-- Hotspots -->
    <div class="panel-card">
      <div class="panel-header">
        <h2 class="panel-title"><span>📍</span> Vulnerable Pharmaceutical Markets</h2>
        <span class="badge-tag">Bangladesh Hotspots</span>
      </div>

      <div style="display: flex; flex-direction: column; gap: 1rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.85rem; background: #fef2f2; border: 1px solid #fecaca; border-radius: var(--radius-sm);">
          <div>
            <strong style="color: #991b1b; font-size: 0.95rem;">Mitford Medicine Wholesale Market</strong>
            <div style="font-size: 0.8rem; color: #7f1d1d;">Old Dhaka • 14 suspicious duplicate barcodes intercepted</div>
          </div>
          <span class="status-pill status-review">CRITICAL RISK</span>
        </div>

        <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.85rem; background: #fffbeb; border: 1px solid #fde68a; border-radius: var(--radius-sm);">
          <div>
            <strong style="color: #92400e; font-size: 0.95rem;">Babubazar Wholesale Cluster</strong>
            <div style="font-size: 0.8rem; color: #78350f;">Kotwali, Dhaka • 8 duplicate scans detected</div>
          </div>
          <span class="status-pill status-investigating">HIGH RISK</span>
        </div>

        <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.85rem; background: #fffbeb; border: 1px solid #fde68a; border-radius: var(--radius-sm);">
          <div>
            <strong style="color: #92400e; font-size: 0.95rem;">Anderkilla Pharmacy Hub</strong>
            <div style="font-size: 0.8rem; color: #78350f;">Chittagong • 6 duplicate scans flagged</div>
          </div>
          <span class="status-pill status-investigating">HIGH RISK</span>
        </div>

        <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.85rem; background: var(--gray-50); border: 1px solid var(--gray-200); border-radius: var(--radius-sm);">
          <div>
            <strong style="color: var(--gray-800); font-size: 0.95rem;">Chawkbazar Medical Market</strong>
            <div style="font-size: 0.8rem; color: var(--gray-600);">Sylhet • 4 incidents under review</div>
          </div>
          <span class="status-pill status-seized">MONITORED</span>
        </div>
      </div>
    </div>

    <!-- Protocol Guide -->
    <div class="panel-card">
      <div class="panel-header">
        <h2 class="panel-title"><span>⚖️</span> DGDA Standard Operating Procedure</h2>
        <span class="badge-tag">Legal Action</span>
      </div>

      <div style="font-size: 0.9rem; color: var(--gray-700); line-height: 1.7;">
        <ol style="padding-left: 1.25rem;">
          <li style="margin-bottom: 0.75rem;">
            <strong>Step 1 (Automated Flag):</strong> When 2 or more duplicate scans of the same barcode serial are registered across disparate locations, the system triggers a counterfeit alert.
          </li>
          <li style="margin-bottom: 0.75rem;">
            <strong>Step 2 (Citizen Case Dossier):</strong> Photo evidence, pharmacy address, and GPS coordinates are compiled into an official case report.
          </li>
          <li style="margin-bottom: 0.75rem;">
            <strong>Step 3 (Field Raid):</strong> Executive Magistrate & DGDA inspector inspect pharmacy inventory and seize counterfeit stock under the Special Powers Act 1974.
          </li>
        </ol>
      </div>
    </div>
  </div>

  <!-- Incident List -->
  <section class="panel-card" style="margin-top: 2rem;">
    <div class="panel-header">
      <h2 class="panel-title"><span>📑</span> Counterfeit Incident Management Log</h2>
      <span class="badge-tag">Citizen Crowd-Reports</span>
    </div>

    <div style="overflow-x: auto;">
      <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
        <thead>
          <tr style="background: var(--gray-100); border-bottom: 2px solid var(--gray-200); color: var(--gray-600);">
            <th style="padding: 0.75rem 1rem; text-align: left;">Case ID</th>
            <th style="padding: 0.75rem 1rem; text-align: left;">Medicine</th>
            <th style="padding: 0.75rem 1rem; text-align: left;">Suspect Pharmacy</th>
            <th style="padding: 0.75rem 1rem; text-align: left;">District</th>
            <th style="padding: 0.75rem 1rem; text-align: left;">Status</th>
            <th style="padding: 0.75rem 1rem; text-align: left;">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($reports as $r): ?>
            <tr style="border-bottom: 1px solid var(--gray-200);">
              <td style="padding: 0.75rem 1rem; font-weight: 700; color: var(--primary);">
                DGDA-RPT-<?php echo str_pad($r['id'], 5, '0', STR_PAD_LEFT); ?>
              </td>
              <td style="padding: 0.75rem 1rem; font-weight: 700;"><?php echo htmlspecialchars($r['medicine_name']); ?></td>
              <td style="padding: 0.75rem 1rem;"><?php echo htmlspecialchars($r['pharmacy_name']); ?></td>
              <td style="padding: 0.75rem 1rem;"><?php echo htmlspecialchars($r['district']); ?></td>
              <td style="padding: 0.75rem 1rem;">
                <span class="status-pill <?php echo strpos($r['status'], 'Seized') !== false ? 'status-seized' : (strpos($r['status'], 'Enforcement') !== false ? 'status-investigating' : 'status-review'); ?>">
                  <?php echo htmlspecialchars($r['status']); ?>
                </span>
              </td>
              <td style="padding: 0.75rem 1rem;">
                <button class="btn btn-secondary" style="padding: 0.35rem 0.65rem; font-size: 0.8rem;" onclick="alert('Case Dossier #<?php echo $r['id']; ?>: <?php echo htmlspecialchars(addslashes($r['description'])); ?>')">
                  🔍 View Dossier
                </button>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>
</main>

<script>
async function reviewMedicine(medId, action) {
  const confirmMsg = action === 'approve' 
    ? "Are you sure you want to APPROVE this medicine registration? It will become active in the national pharmaceutical database and consumer verification portal."
    : "Are you sure you want to REJECT this medicine registration application?";
  
  if (!confirm(confirmMsg)) return;

  try {
    const res = await fetch('/api/approve_medicine.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ medicine_id: medId, action: action })
    });
    const data = await res.json();
    if (data.success) {
      alert(data.message);
      location.reload();
    } else {
      alert("Error: " + (data.error || "Failed to update status."));
    }
  } catch (err) {
    console.error(err);
    alert("Network error processing decision.");
  }
}
</script>

<?php
require_once __DIR__ . "/../partials/footer.php";
?>

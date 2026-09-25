<?php
require_once __DIR__ . '/partials/header.php';
?>

<main class="main-wrapper">
  <!-- Hero Section -->
  <section class="hero-card">
    <div class="hero-tag">🇧🇩 DGDA APPROVED VERIFICATION SYSTEM</div>
    <h1 class="hero-title">Scan Medicine Barcode to Detect Counterfeit Drugs Instantly</h1>
    <p class="hero-desc">
      Protect your family against lethal fake and substandard medicines. Every genuine pack carries a single-use cryptographic 1D barcode verified against the national ledger, with an AI visual packaging validator.
    </p>

    <div class="hero-stats-grid">
      <div class="hero-stat">
        <div class="hero-stat-val">100%</div>
        <div class="hero-stat-label">Single-Scan Anti-Duplication</div>
      </div>
      <div class="hero-stat">
        <div class="hero-stat-val">0.2s</div>
        <div class="hero-stat-label">AI Packaging Check Speed</div>
      </div>
      <div class="hero-stat">
        <div class="hero-stat-val">150,000+</div>
        <div class="hero-stat-label">Monitored Pharmacies</div>
      </div>
      <div class="hero-stat">
        <div class="hero-stat-val">+150 pts</div>
        <div class="hero-stat-label">Rewards for Fake Reports</div>
      </div>
    </div>
  </section>

  <!-- Navigation Tabs -->
  <nav class="tab-navigation">
    <button class="tab-btn active" data-target="tab-scan-verify">
      🔍 Live Barcode Verification
    </button>
    <button class="tab-btn" data-target="tab-ai-inspect">
      🤖 AI Packaging Inspector
    </button>
    <button class="tab-btn" data-target="tab-drug-catalog">
      💊 Drug Guidelines & MRP
    </button>
    <button class="tab-btn" data-target="tab-hotspot-map">
      🗺️ Counterfeit Outbreaks & Reports
    </button>
  </nav>

  <!-- TAB 1: SCAN & VERIFY -->
  <div id="tab-scan-verify" class="tab-pane">
    <div class="verification-grid">
      <!-- Camera Barcode Scanner -->
      <div class="panel-card">
        <div class="panel-header">
          <h2 class="panel-title">
            <span>📷</span> Camera Barcode Scanner
          </h2>
          <span class="badge-tag">1D Code128 / EAN</span>
        </div>

        <div class="scanner-viewport">
          <video id="camera-stream" class="scanner-video" playsinline></video>
          <div class="scanner-overlay">
            <div class="barcode-target-box">
              <div class="laser-line"></div>
            </div>
            <div id="scanner-hint" class="scanner-hint">Camera ready. Click open camera below.</div>
          </div>
        </div>

        <div class="scanner-actions">
          <button id="btn-toggle-camera" class="btn btn-primary btn-block" onclick="barcodeEngine.startCamera()">
            📷 Open Camera Scanner
          </button>
          <label class="btn btn-secondary" style="cursor: pointer;">
            📁 Upload Image
            <input type="file" accept="image/*" style="display: none;" onchange="barcodeEngine.handleImageUpload(event)">
          </label>
        </div>

        <!-- Manual Input -->
        <div class="manual-input-box">
          <input type="text" id="manual-barcode-input" class="input-field" placeholder="Enter barcode serial (e.g. MS-2026-NAPA-7821A)" />
          <button class="btn btn-primary" onclick="app.verifyBarcode()">Verify Now</button>
        </div>

      </div>

      <!-- How Verification Works Card -->
      <div class="panel-card" style="display: flex; flex-direction: column;">
        <div class="panel-header">
          <h2 class="panel-title">
            <span>🛡️</span> 4-Layer Anti-Counterfeit Architecture
          </h2>
          <span class="badge-tag" style="background: #dcfce7; color: #15803d;">DGDA Smart Ledger</span>
        </div>

        <div style="flex: 1; display: flex; flex-direction: column; gap: 1rem;">
          <div style="background: var(--gray-50); border: 1px solid var(--gray-200); border-radius: var(--radius-sm); padding: 1rem;">
            <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.35rem;">
              <span style="font-size: 1.3rem;">🛒</span>
              <strong style="font-size: 1rem; color: var(--gray-900);">Smart In-Store Shelf Browsing</strong>
            </div>
            <p style="font-size: 0.85rem; color: var(--gray-600); line-height: 1.5;">
              Multiple customer scans in the same pharmacy do <strong>NOT</strong> falsely trigger counterfeit alarms. The system identifies pre-purchase browsing and keeps the product verified as authentic.
            </p>
          </div>

          <div style="background: var(--gray-50); border: 1px solid var(--gray-200); border-radius: var(--radius-sm); padding: 1rem;">
            <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.35rem;">
              <span style="font-size: 1.3rem;">🔒</span>
              <strong style="font-size: 1rem; color: var(--gray-900);">Post-Purchase Ownership Lock (Claiming)</strong>
            </div>
            <p style="font-size: 0.85rem; color: var(--gray-600); line-height: 1.5;">
              Upon purchase, the buyer taps <strong>"Mark as Purchased"</strong> to lock the serial code. Any subsequent scan on a shelf immediately triggers a warning of unauthorized resale or empty box refilling.
            </p>
          </div>

          <div style="background: var(--gray-50); border: 1px solid var(--gray-200); border-radius: var(--radius-sm); padding: 1rem;">
            <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.35rem;">
              <span style="font-size: 1.3rem;">🚨</span>
              <strong style="font-size: 1rem; color: var(--gray-900);">AI Spatial Velocity Anomaly Detection</strong>
            </div>
            <p style="font-size: 0.85rem; color: var(--gray-600); line-height: 1.5;">
              If a single barcode serial surfaces in distant cities simultaneously (e.g. Dhaka and Chittagong), our AI flags an impossible travel anomaly and triggers an automated counterfeit syndicate alert.
            </p>
          </div>

          <div style="background: #fef3c7; border: 1px solid #fde68a; border-radius: var(--radius-sm); padding: 1rem;">
            <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.35rem;">
              <span style="font-size: 1.3rem;">🎁</span>
              <strong style="font-size: 1rem; color: #92400e;">Citizen "Health Guardian" Rewards</strong>
            </div>
            <p style="font-size: 0.85rem; color: #78350f; line-height: 1.5;">
              Earn +50 pts for scanning, +100 pts for securing purchase ownership, and +150 pts for reporting fake medicine hotspots. Redeemable for mobile balance and pharmacy vouchers!
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- TAB 2: AI PACKAGING INSPECTOR -->
  <div id="tab-ai-inspect" class="tab-pane" style="display: none;">
    <div class="verification-grid">
      <div class="panel-card">
        <div class="panel-header">
          <h2 class="panel-title">
            <span>🤖</span> AI Visual Packaging Analysis
          </h2>
          <span class="badge-tag">MobileNet Neural Vision</span>
        </div>

        <p id="ai-model-status" style="font-size: 0.85rem; color: var(--gray-500); margin-bottom: 1rem;">
          ⏳ Initializing in-browser MobileNet AI model...
        </p>

        <div style="margin-bottom: 1rem;">
          <label style="font-size: 0.85rem; font-weight: 700; color: var(--gray-700); display: block; margin-bottom: 0.35rem;">
            Select Target Medicine Packaging Benchmark:
          </label>
          <select id="ai-select-medicine" class="input-field" style="width: 100%;">
            <option value="napa">Square Napa Extra (Crimson Red / Silver Foil)</option>
            <option value="seclo">Square Seclo 20 (Cyan Blue / Pink Capsule)</option>
            <option value="monas">ACME Monas 10 (Green / White Strip)</option>
            <option value="ace">Beximco Ace Plus (Blue / Red Fast Relief)</option>
            <option value="maxpro">Renata Maxpro 20 (Purple Box)</option>
          </select>
        </div>

        <div class="ai-preview-box" id="ai-preview-container">
          <img id="ai-preview-image" src="" alt="Packaging Snapshot Preview" />
        </div>

        <label class="ai-drop-zone" style="display: block;">
          <span style="font-size: 2rem;">📸</span>
          <div style="font-weight: 700; color: var(--gray-800); margin-top: 0.5rem;">
            Click to Capture / Upload Medicine Packaging Photo
          </div>
          <div style="font-size: 0.8rem; color: var(--gray-500);">
            Take a clear photo of the blister strip or medicine box
          </div>
          <input type="file" id="ai-photo-upload" accept="image/*" style="display: none;" />
        </label>

        <button id="btn-run-ai-inspect" class="btn btn-primary btn-block" disabled>
          ⚡ Run AI Packaging Inspection
        </button>

        <div style="margin-top: 1rem; font-size: 0.85rem;" id="ai-analysis-feedback"></div>
      </div>

      <!-- AI Inspection Scorecard -->
      <div class="panel-card">
        <div class="panel-header">
          <h2 class="panel-title">
            <span>📊</span> Visual Authenticity Scorecard
          </h2>
          <span class="badge-tag">Feature Extraction</span>
        </div>

        <div class="ai-metrics-card">
          <div class="metric-row">
            <span>Overall Packaging Trust Score</span>
            <span id="score-overall-val" style="font-size: 1.1rem; font-weight: 800; color: var(--primary);">--%</span>
          </div>
          <div class="progress-bar-bg">
            <div id="bar-overall" class="progress-fill bg-emerald" style="width: 0%;"></div>
          </div>

          <div class="metric-row">
            <span>Color Tone & Histogram Match</span>
            <span id="score-color-val">--%</span>
          </div>
          <div class="progress-bar-bg">
            <div id="bar-color" class="progress-fill bg-amber" style="width: 0%;"></div>
          </div>

          <div class="metric-row">
            <span>Logo Sharpness & Typography Edge Clarity</span>
            <span id="score-typo-val">--%</span>
          </div>
          <div class="progress-bar-bg">
            <div id="bar-typo" class="progress-fill bg-emerald" style="width: 0%;"></div>
          </div>
        </div>

        <div style="margin-top: 1.5rem; background: var(--gray-50); border: 1px solid var(--gray-200); border-radius: var(--radius-sm); padding: 1.25rem;">
          <h4 style="font-size: 0.95rem; color: var(--gray-900); margin-bottom: 0.5rem;">How AI Detects Counterfeit Packaging:</h4>
          <ul style="font-size: 0.85rem; color: var(--gray-600); padding-left: 1.2rem; line-height: 1.6;">
            <li><strong>Ink & Tone Consistency:</strong> Substandard printing uses cheap dye inks that deviate from official Pantone / CMYK values.</li>
            <li><strong>Foil Texture & Embossing:</strong> Analyzes micro-grooves and blister foil curvature under ambient light.</li>
            <li><strong>Typography Bleed:</strong> Detects blurry font boundaries common in unauthorized offset photocopies.</li>
          </ul>
        </div>
      </div>
    </div>
  </div>

  <!-- TAB 3: DRUG GUIDELINES & MRP -->
  <div id="tab-drug-catalog" class="tab-pane" style="display: none;">
    <div style="margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
      <div>
        <h2 style="font-size: 1.5rem; font-weight: 800; color: var(--gray-900);">Official Medicine Catalog & Guidelines</h2>
        <p style="font-size: 0.9rem; color: var(--gray-600);">Verified clinical indications, side effects, precautions, and government MRP</p>
      </div>
    </div>

    <div id="catalog-grid-container" class="catalog-grid">
      <!-- Populated dynamically via JS -->
    </div>
  </div>

  <!-- TAB 4: COUNTERFEIT OUTBREAKS & REPORTS -->
  <div id="tab-hotspot-map" class="tab-pane" style="display: none;">
    <div class="panel-card" style="margin-bottom: 2rem;">
      <div class="panel-header">
        <h2 class="panel-title">
          <span>🚨</span> Real-Time Counterfeit Outbreaks in Bangladesh
        </h2>
        <button class="btn btn-danger" onclick="app.openReportModal()">
          🚩 Report Fake Medicine
        </button>
      </div>

      <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse; text-align: left;">
          <thead>
            <tr style="background: var(--gray-100); border-bottom: 2px solid var(--gray-300); font-size: 0.85rem; color: var(--gray-600);">
              <th style="padding: 0.75rem 1rem;">Suspected Medicine</th>
              <th style="padding: 0.75rem 1rem;">Pharmacy / Market Location</th>
              <th style="padding: 0.75rem 1rem;">City / District</th>
              <th style="padding: 0.75rem 1rem;">Enforcement Status</th>
              <th style="padding: 0.75rem 1rem;">Reported Date</th>
            </tr>
          </thead>
          <tbody id="incidents-table-body">
            <!-- Populated dynamically -->
          </tbody>
        </table>
      </div>
    </div>
  </div>
</main>

<!-- Verification Result Modal -->
<div id="result-modal" class="modal-backdrop">
  <div class="result-card">
    <div id="result-content">
      <!-- Populated dynamically by app.js -->
    </div>
  </div>
</div>

<!-- Citizen Counterfeit Reporting Modal -->
<div id="report-modal" class="modal-backdrop">
  <div class="result-card">
    <div class="result-banner banner-fake" style="padding: 1.5rem 2rem;">
      <div class="banner-icon">🚩</div>
      <div class="banner-content">
        <h2>Report Counterfeit Medicine to DGDA</h2>
        <p>Your vigilance helps law enforcement seize lethal fake drug batches.</p>
      </div>
    </div>

    <form class="result-body" onsubmit="app.submitReport(event)">
      <div style="margin-bottom: 1rem;">
        <label style="font-size: 0.85rem; font-weight: 700; color: var(--gray-700);">Barcode Serial (If available):</label>
        <input type="text" id="report-barcode-input" class="input-field" style="width: 100%; margin-top: 0.25rem;" placeholder="e.g. MS-2026-NAPA-DUPL-998" />
      </div>

      <div style="margin-bottom: 1rem;">
        <label style="font-size: 0.85rem; font-weight: 700; color: var(--gray-700);">Medicine Name & Strength: *</label>
        <input type="text" id="report-med-name" class="input-field" style="width: 100%; margin-top: 0.25rem;" required placeholder="e.g. Napa Extra 500mg" />
      </div>

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
        <div>
          <label style="font-size: 0.85rem; font-weight: 700; color: var(--gray-700);">Pharmacy / Shop Name: *</label>
          <input type="text" id="report-pharmacy-name" class="input-field" style="width: 100%; margin-top: 0.25rem;" required placeholder="e.g. Janata Drug Corner" />
        </div>
        <div>
          <label style="font-size: 0.85rem; font-weight: 700; color: var(--gray-700);">City / Market: *</label>
          <input type="text" id="report-city" class="input-field" style="width: 100%; margin-top: 0.25rem;" required placeholder="e.g. Mitford Market, Dhaka" />
        </div>
      </div>

      <div style="margin-bottom: 1rem;">
        <label style="font-size: 0.85rem; font-weight: 700; color: var(--gray-700);">Pharmacy Full Address: *</label>
        <input type="text" id="report-pharmacy-address" class="input-field" style="width: 100%; margin-top: 0.25rem;" required placeholder="Shop No, Street, Landmark" />
      </div>

      <div style="margin-bottom: 1rem;">
        <label style="font-size: 0.85rem; font-weight: 700; color: var(--gray-700);">Suspicious Details / Symptoms: *</label>
        <textarea id="report-desc" class="input-field" style="width: 100%; height: 80px; margin-top: 0.25rem;" required placeholder="Describe what looked wrong (e.g. washed out color, missing hologram, duplicate barcode alert, patient illness)"></textarea>
      </div>

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
        <div>
          <label style="font-size: 0.85rem; font-weight: 700; color: var(--gray-700);">Your Name (Optional):</label>
          <input type="text" id="report-user-name" class="input-field" style="width: 100%; margin-top: 0.25rem;" placeholder="Anonymous Guardian" />
        </div>
        <div>
          <label style="font-size: 0.85rem; font-weight: 700; color: var(--gray-700);">Contact Phone (For Reward Voucher):</label>
          <input type="tel" id="report-user-phone" class="input-field" style="width: 100%; margin-top: 0.25rem;" placeholder="017xxxxxxxx" />
        </div>
      </div>

      <div style="display: flex; gap: 1rem;">
        <button type="button" class="btn btn-secondary btn-block" onclick="app.closeReportModal()">Cancel</button>
        <button type="submit" class="btn btn-danger btn-block">🚀 Submit Report (+150 Points)</button>
      </div>
    </form>
  </div>
</div>

<?php
require_once __DIR__ . '/partials/footer.php';
?>

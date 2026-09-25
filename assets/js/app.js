/**
 * Medicine-Shield (Oushodh-Shield)
 * Main Application Orchestrator & UI Controller
 */

class App {
  constructor() {
    this.audioCtx = null;
    this.currentMedicine = null;
    this.init();
  }

  init() {
    this.initTabs();
    this.initBarcodePresets();
    this.loadCatalog();
    this.loadIncidents();
    this.initAIInspector();

    // Auto-verify if barcode is passed via query string (?barcode=...)
    const urlParams = new URLSearchParams(window.location.search);
    const barcodeParam = urlParams.get('barcode');
    if (barcodeParam) {
      const input = document.getElementById('manual-barcode-input');
      if (input) input.value = barcodeParam;
      setTimeout(() => this.verifyBarcode(barcodeParam), 300);
    }
  }

  playSound(type) {
    try {
      if (!this.audioCtx) {
        this.audioCtx = new (window.AudioContext || window.webkitAudioContext)();
      }
      const now = this.audioCtx.currentTime;
      const osc = this.audioCtx.createOscillator();
      const gain = this.audioCtx.createGain();
      osc.connect(gain);
      gain.connect(this.audioCtx.destination);

      if (type === 'success') {
        osc.frequency.setValueAtTime(587.33, now);
        osc.frequency.setValueAtTime(880, now + 0.1);
        gain.gain.setValueAtTime(0.3, now);
        gain.gain.exponentialRampToValueAtTime(0.01, now + 0.35);
        osc.start(now);
        osc.stop(now + 0.35);
      } else if (type === 'warning') {
        osc.type = 'sawtooth';
        osc.frequency.setValueAtTime(320, now);
        osc.frequency.setValueAtTime(220, now + 0.15);
        gain.gain.setValueAtTime(0.4, now);
        gain.gain.exponentialRampToValueAtTime(0.01, now + 0.4);
        osc.start(now);
        osc.stop(now + 0.4);
      } else if (type === 'danger') {
        osc.type = 'sawtooth';
        osc.frequency.setValueAtTime(150, now);
        osc.frequency.linearRampToValueAtTime(80, now + 0.5);
        gain.gain.setValueAtTime(0.5, now);
        gain.gain.exponentialRampToValueAtTime(0.01, now + 0.5);
        osc.start(now);
        osc.stop(now + 0.5);
      }
    } catch (e) {
      console.warn('Audio playback not supported:', e);
    }
  }

  initTabs() {
    document.querySelectorAll('.tab-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.tab-pane').forEach(p => p.style.display = 'none');

        btn.classList.add('active');
        const targetId = btn.getAttribute('data-target');
        const targetPane = document.getElementById(targetId);
        if (targetPane) targetPane.style.display = 'block';
      });
    });
  }

  initBarcodePresets() {
    document.querySelectorAll('.preset-chip').forEach(chip => {
      chip.addEventListener('click', () => {
        const code = chip.getAttribute('data-code');
        const input = document.getElementById('manual-barcode-input');
        if (input) input.value = code;
        this.verifyBarcode(code);
      });
    });
  }

  async verifyBarcode(barcodeSerial) {
    if (!barcodeSerial) {
      const input = document.getElementById('manual-barcode-input');
      barcodeSerial = input ? input.value.trim() : '';
    }
    if (!barcodeSerial) {
      alert('Please enter or scan a barcode serial.');
      return;
    }

    const userId = window.CURRENT_USER && window.CURRENT_USER.id ? String(window.CURRENT_USER.id) : (window.rewardManager ? window.rewardManager.userId : 'guest_citizen');

    const modal = document.getElementById('result-modal');
    if (modal) modal.classList.add('open');
    const content = document.getElementById('result-content');
    if (content) {
      content.innerHTML = `
        <div style="padding: 3rem; text-align: center;">
          <div style="font-size: 2.5rem;">⚙️</div>
          <h3 style="margin-top: 1rem; color: var(--gray-700);">Querying Cryptographic Ledger...</h3>
          <p style="color: var(--gray-500); font-size: 0.9rem;">Verifying single-use digital certificate & scan history</p>
        </div>
      `;
    }

    try {
      const res = await fetch('api/verify_barcode.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          barcode: barcodeSerial,
          location: 'Dhaka (Mitford & Dhanmondi Sector), Bangladesh',
          user_id: userId
        })
      });

      let data;
      try {
        data = await res.json();
      } catch (jsonErr) {
        throw new Error('Server returned non-JSON response. Please ensure PHP server is running on localhost:8000.');
      }

      if (!data || !data.success) {
        if (content) {
          content.innerHTML = `
            <div style="padding: 2.5rem; text-align: center;">
              <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">⚠️</div>
              <h3 style="color: var(--danger); margin-bottom: 0.5rem;">Verification Notice</h3>
              <p style="color: var(--gray-600); font-size: 0.9rem; line-height: 1.5; margin-bottom: 1.5rem;">
                ${(data && data.error) ? data.error : 'Unable to query verification ledger API.'}
              </p>
              <button class="btn btn-secondary" onclick="app.closeModal()">Close</button>
            </div>
          `;
        }
        return;
      }

      this.displayVerificationResult(data);

      if (window.rewardManager) {
        window.rewardManager.fetchProfile();
      }
    } catch (err) {
      console.error(err);
      if (content) {
        content.innerHTML = `
          <div style="padding: 2.5rem; text-align: center;">
            <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">🔌</div>
            <h3 style="color: var(--danger); margin-bottom: 0.5rem;">Connection Notice</h3>
            <p style="color: var(--gray-600); font-size: 0.9rem; line-height: 1.5; max-width: 480px; margin: 0 auto 1.5rem auto;">
              ${err.message || 'Unable to connect to the PHP backend API.'}
            </p>
            <div style="background: var(--gray-50); border: 1px solid var(--gray-200); border-radius: 8px; padding: 1rem; text-align: left; font-size: 0.85rem; color: var(--gray-700); max-width: 480px; margin: 0 auto 1.5rem auto;">
              <strong>💡 Quick Checklist:</strong>
              <ul style="margin: 0.35rem 0 0 1.2rem; padding: 0; line-height: 1.6;">
                <li>Make sure you opened <strong>http://localhost:8000</strong> (not <code>file:///...</code>).</li>
                <li>Make sure the PHP server is running: <code>php -S localhost:8000</code></li>
              </ul>
            </div>
            <button class="btn btn-secondary" onclick="app.closeModal()">Close</button>
          </div>
        `;
      }
    }
  }

  displayVerificationResult(data) {
    let bannerClass = 'banner-genuine';
    let icon = '✅';
    let sound = 'success';

    if (data.status === 'GENUINE_SHELF_BROWSING') {
      bannerClass = 'banner-genuine';
      icon = '🛒';
      sound = 'success';
    } else if (data.status === 'ALREADY_SOLD_ALERT') {
      bannerClass = 'banner-duplicate';
      icon = '🔒';
      sound = 'warning';
    } else if (data.status === 'CLONED_COUNTERFEIT' || data.status === 'DUPLICATE_WARNING') {
      bannerClass = 'banner-fake';
      icon = '🚨';
      sound = 'danger';
    } else if (data.status === 'INVALID_FAKE') {
      bannerClass = 'banner-fake';
      icon = '❌';
      sound = 'danger';
    } else if (data.status === 'FLAGGED_RECALLED') {
      bannerClass = 'banner-recalled';
      icon = '🚫';
      sound = 'danger';
    }

    this.playSound(sound);

    let html = `
      <div class="result-banner ${bannerClass}">
        <div class="banner-icon">${icon}</div>
        <div class="banner-content">
          <h2>${data.title}</h2>
          <p>${data.message}</p>
        </div>
      </div>
      <div class="result-body">
    `;

    // Smart In-Store Shelf Browsing Context Card
    if (data.status === 'GENUINE_SHELF_BROWSING') {
      html += `
        <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: var(--radius-sm); padding: 1.25rem; margin-bottom: 1.5rem;">
          <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.5rem; flex-wrap: wrap; gap: 0.5rem;">
            <h4 style="color: #166534; font-size: 1rem; margin: 0;">🛒 Smart Shelf-Browsing Mode (Pre-Purchase):</h4>
            <span class="badge-tag" style="background: #dcfce7; color: #15803d;">Authentic • On-Shelf</span>
          </div>
          <p style="font-size: 0.88rem; color: #14532d; line-height: 1.5; margin: 0;">
            This box was previously checked <strong>${data.scan_count - 1} time(s)</strong> in this pharmacy. Our AI recognized this as legitimate in-store browsing — <strong>not a duplicate counterfeit</strong>. The medicine is authentic, unexpired, and safe to purchase.
          </p>
        </div>
      `;
    }

    // Already Sold / Claimed Anomaly Card
    if (data.status === 'ALREADY_SOLD_ALERT') {
      html += `
        <div style="background: #fffbeb; border: 1px solid #fde68a; border-radius: var(--radius-sm); padding: 1.25rem; margin-bottom: 1.5rem;">
          <h4 style="color: #b45309; margin-bottom: 0.5rem; font-size: 1rem;">🔒 Prior Purchase Registration Detected:</h4>
          <ul style="padding-left: 1.2rem; font-size: 0.88rem; color: #78350f; line-height: 1.6;">
            <li><strong>Purchased & Locked On:</strong> ${data.claimed_at || 'Earlier date'}</li>
            <li><strong>Registered Buyer:</strong> ${data.claimed_by || 'Citizen Guardian'}</li>
            <li><strong>Dispensing Pharmacy:</strong> ${data.claimed_location || 'Pharmacy counter'}</li>
          </ul>
          <p style="margin-top: 0.75rem; font-weight: 700; color: #b91c1c; font-size: 0.85rem;">
            If you did not personally purchase and claim this pack, this may be an illicit refill or counterfeit reprint. Do not accept this unit from the counter.
          </p>
        </div>
      `;
    }

    // Cloned Barcode Anomaly Card (Impossible Travel)
    if (data.status === 'CLONED_COUNTERFEIT') {
      html += `
        <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: var(--radius-sm); padding: 1.25rem; margin-bottom: 1.5rem;">
          <h4 style="color: #b91c1c; margin-bottom: 0.5rem; font-size: 1rem;">🚨 AI Spatial Velocity Anomaly:</h4>
          <p style="font-size: 0.88rem; color: #991b1b; line-height: 1.5;">
            This barcode serial was first registered in <strong>${data.first_scanned_location}</strong>, but has surfaced in <strong>${data.current_scanned_location}</strong>. 
          </p>
          <div style="margin-top: 0.75rem; padding: 0.75rem; background: #fff; border-radius: 6px; border: 1px dashed #f87171; font-size: 0.82rem; color: #7f1d1d;">
            ⚠️ <strong>Cloned Barcode Risk:</strong> Multiple duplicate boxes exist across distant cities. Total scans recorded: ${data.total_scans_detected}.
          </div>
          <div style="margin-top: 1rem;">
            <button class="btn btn-danger btn-block" onclick="app.openReportModal('${data.barcode_serial}')">
              🚩 Report Cloned Counterfeit to DGDA (+150 Pts)
            </button>
          </div>
        </div>
      `;
    }

    if (data.status === 'INVALID_FAKE') {
      html += `
        <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: var(--radius-sm); padding: 1.25rem; margin-bottom: 1.5rem;">
          <h4 style="color: #b91c1c; margin-bottom: 0.5rem;">🚨 Unrecognized Code Warning:</h4>
          <p style="font-size: 0.9rem; color: #991b1b;">
            Barcode Serial <code>${data.barcode_serial}</code> does NOT exist in the National Drug Registry (DGDA).
          </p>
          <div style="margin-top: 1rem;">
            <button class="btn btn-danger btn-block" onclick="app.openReportModal('${data.barcode_serial}')">
              🚩 Submit Incident Report to DGDA (+150 Points)
            </button>
          </div>
        </div>
      `;
    }

    html += `
      <div class="barcode-render-card">
        <svg id="modal-rendered-barcode"></svg>
        <div style="font-size: 0.85rem; color: var(--gray-500); margin-top: 0.5rem;">
          1D Cryptographic Code128 • <code>${data.barcode_serial}</code>
        </div>
      </div>
    `;

    if (data.medicine) {
      const m = data.medicine;
      html += `
        <div class="info-grid">
          <div>
            <div class="info-item-label">Brand Name</div>
            <div class="info-item-val">${m.brand_name} (${m.strength || ''})</div>
          </div>
          <div>
            <div class="info-item-label">Generic Name</div>
            <div class="info-item-val">${m.generic_name}</div>
          </div>
          <div>
            <div class="info-item-label">Manufacturer</div>
            <div class="info-item-val">${m.manufacturer_name || 'Licensed Manufacturer'}</div>
          </div>
          <div>
            <div class="info-item-label">Batch No / Expiry</div>
            <div class="info-item-val">${m.batch_number || 'N/A'} • Exp: ${m.expiry_date || 'N/A'}</div>
          </div>
          <div>
            <div class="info-item-label">Government DAR Reg</div>
            <div class="info-item-val">${m.dar_number || 'DGDA Registered'}</div>
          </div>
          <div>
            <div class="info-item-label">Official MRP</div>
            <div class="info-item-val" style="color: var(--primary);">৳ ${m.mrp_bdt || '2.50'} / Unit</div>
          </div>
        </div>
      `;

      if (m.indications_merits) {
        html += `
          <div class="clinical-box">
            <span class="clinical-badge badge-merits">Indications & Benefits (Merits)</span>
            <p style="font-size: 0.9rem; color: var(--gray-700); margin-top: 0.35rem;">${m.indications_merits}</p>
          </div>
        `;
      }

      if (m.side_effects_demerits) {
        html += `
          <div class="clinical-box">
            <span class="clinical-badge badge-demerits">Possible Side Effects (Demerits)</span>
            <p style="font-size: 0.9rem; color: var(--gray-700); margin-top: 0.35rem;">${m.side_effects_demerits}</p>
          </div>
        `;
      }

      if (m.dosage_instructions) {
        html += `
          <div class="clinical-box">
            <span class="clinical-badge badge-dosage">Standard Dosage & Guidelines</span>
            <p style="font-size: 0.9rem; color: var(--gray-700); margin-top: 0.35rem;">${m.dosage_instructions}</p>
          </div>
        `;
      }
    }

    // Post-Purchase Ownership Claim Section (If Authentic & Unclaimed)
    if (data.is_authentic && data.can_claim) {
      if (window.CURRENT_USER && window.CURRENT_USER.id) {
        const loggedInName = window.CURRENT_USER.name || 'Citizen';
        const loggedInId = window.CURRENT_USER.id;
        html += `
          <div id="claim-action-card" style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: var(--radius-sm); padding: 1.25rem; margin-top: 1.25rem;">
            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 0.5rem;">
              <strong style="color: #1e40af; font-size: 0.95rem;">🛒 Buying this medicine?</strong>
              <span class="badge-tag" style="background: #dbeafe; color: #1d4ed8;">+100 Reward Points</span>
            </div>
            <p style="font-size: 0.85rem; color: #1e3a8a; line-height: 1.4; margin-bottom: 0.65rem;">
              Lock this unit to your account upon purchase. This permanently retires the code in the national registry so counterfeiters cannot refill or clone it.
            </p>
            <div style="background: #dbeafe; border-radius: 4px; padding: 0.4rem 0.65rem; font-size: 0.8rem; color: #1e40af; margin-bottom: 0.85rem; display: flex; align-items: center; gap: 0.4rem;">
              <span>👤</span> Registering to: <strong>${loggedInName}</strong> (User ID #${loggedInId})
            </div>
            <button class="btn btn-primary btn-block" onclick="app.claimMedicine('${data.barcode_serial}')">
              🔒 Confirm Purchase & Secure Ownership (+100 Pts)
            </button>
          </div>
        `;
      } else {
        html += `
          <div id="claim-action-card" style="background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: var(--radius-sm); padding: 1.25rem; margin-top: 1.25rem; text-align: center;">
            <div style="font-size: 1.5rem; margin-bottom: 0.35rem;">🔐</div>
            <strong style="color: var(--text-main); font-size: 0.95rem; display: block; margin-bottom: 0.35rem;">
              Sign In Required to Secure Ownership
            </strong>
            <p style="font-size: 0.85rem; color: var(--text-muted); line-height: 1.4; margin-bottom: 0.85rem;">
              Only a logged-in user profile can lock medicine ownership in the national ledger and earn +100 reward points.
            </p>
            <a href="/login" class="btn btn-primary" style="display: inline-block; padding: 0.5rem 1.25rem; font-size: 0.85rem; text-decoration: none;">
              Sign In to Claim Medicine
            </a>
          </div>
        `;
      }
    }

    html += `
        <div style="display: flex; gap: 1rem; margin-top: 1.5rem; flex-wrap: wrap;">
          <button class="btn btn-secondary btn-block" style="flex: 1;" onclick="app.closeModal()">Close</button>
          ${!data.is_authentic ? `
            <button class="btn btn-danger btn-block" style="flex: 1;" onclick="app.openReportModal('${data.barcode_serial}')">
              🚩 Report Fake Medicine
            </button>
          ` : `
            <button class="btn btn-success btn-block" style="flex: 1;" onclick="app.closeModal()">
              ✓ Verified & Safe
            </button>
          `}
        </div>
      </div>
    `;

    const content = document.getElementById('result-content');
    if (content) content.innerHTML = html;

    if (window.barcodeEngine) {
      window.barcodeEngine.renderBarcode('#modal-rendered-barcode', data.barcode_serial);
    }
  }

  async claimMedicine(barcodeSerial) {
    if (!window.CURRENT_USER || !window.CURRENT_USER.id) {
      alert('Authentication Required: Please sign in to your user profile to confirm purchase and secure ownership.');
      window.location.href = '/login';
      return;
    }

    const btn = event ? event.target : null;
    if (btn) {
      btn.disabled = true;
      btn.innerText = 'Securing ownership in DGDA ledger...';
    }

    try {
      const res = await fetch('api/claim_medicine.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          barcode: barcodeSerial,
          location: 'Bismillah Pharmacy, Dhanmondi 27, Dhaka'
        })
      });
      const data = await res.json();
      if (data.success) {
        this.playSound('success');
        const card = document.getElementById('claim-action-card');
        if (card) {
          card.style.background = '#ecfdf5';
          card.style.borderColor = '#6ee7b7';
          card.innerHTML = `
            <div style="display: flex; align-items: center; gap: 0.5rem; color: #065f46; font-weight: 700; font-size: 1rem;">
              <span>✅</span> Ownership Locked & Secured!
            </div>
            <p style="font-size: 0.85rem; color: #047857; margin-top: 0.35rem; line-height: 1.4;">
              ${data.message}
            </p>
            <div style="font-size: 0.8rem; color: #065f46; margin-top: 0.5rem;">
              Locked on: <strong>${data.claimed_at}</strong> • Registered to: <strong>${data.claimed_by}</strong> (+100 Pts Earned!)
            </div>
          `;
        }
        if (window.rewardManager) {
          window.rewardManager.fetchProfile();
        }
      } else {
        if (data.auth_required) {
          alert(data.error);
          window.location.href = '/login';
          return;
        }
        alert(data.error || 'Failed to claim medicine.');
        if (btn) {
          btn.disabled = false;
          btn.innerText = '🔒 Confirm Purchase & Secure Ownership (+100 Pts)';
        }
      }
    } catch (e) {
      console.error(e);
      alert('Network error claiming medicine.');
      if (btn) {
        btn.disabled = false;
        btn.innerText = '🔒 Confirm Purchase & Secure Ownership (+100 Pts)';
      }
    }
  }

  closeModal() {
    const modal = document.getElementById('result-modal');
    if (modal) modal.classList.remove('open');
  }

  openReportModal(barcodeSerial = '') {
    this.closeModal();
    const reportModal = document.getElementById('report-modal');
    if (reportModal) {
      reportModal.classList.add('open');
      const input = document.getElementById('report-barcode-input');
      if (input) input.value = barcodeSerial;
      const nameInput = document.getElementById('report-user-name');
      if (nameInput && window.CURRENT_USER && window.CURRENT_USER.name) {
        nameInput.value = window.CURRENT_USER.name;
      }
    }
  }

  closeReportModal() {
    const reportModal = document.getElementById('report-modal');
    if (reportModal) reportModal.classList.remove('open');
  }

  async submitReport(event) {
    event.preventDefault();
    const barcode = document.getElementById('report-barcode-input').value.trim();
    const medicineName = document.getElementById('report-med-name').value.trim();
    const pharmacyName = document.getElementById('report-pharmacy-name').value.trim();
    const pharmacyAddr = document.getElementById('report-pharmacy-address').value.trim();
    const city = document.getElementById('report-city').value.trim();
    const desc = document.getElementById('report-desc').value.trim();
    const reporterName = document.getElementById('report-user-name').value.trim() || (window.CURRENT_USER ? window.CURRENT_USER.name : 'Citizen Guardian');
    const reporterPhone = document.getElementById('report-user-phone').value.trim();

    try {
      const res = await fetch('api/report_counterfeit.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          barcode_serial: barcode,
          medicine_name: medicineName,
          pharmacy_name: pharmacyName,
          pharmacy_address: pharmacyAddr,
          city: city,
          description: desc,
          reporter_name: reporterName,
          reporter_phone: reporterPhone,
          user_id: window.CURRENT_USER && window.CURRENT_USER.id ? String(window.CURRENT_USER.id) : (window.rewardManager ? window.rewardManager.userId : 'guest')
        })
      });

      const data = await res.json();
      if (data.success) {
        this.playSound('success');
        alert('🎉 ' + data.message + '\n\nOfficial DGDA Tracking ID: ' + data.case_number);
        this.closeReportModal();
        if (window.rewardManager) window.rewardManager.fetchProfile();
        this.loadIncidents();
      } else {
        alert('Failed to submit report.');
      }
    } catch (e) {
      console.error(e);
      alert('Error connecting to reporting service.');
    }
  }

  async loadCatalog() {
    const container = document.getElementById('catalog-grid-container');
    if (!container) return;

    try {
      const res = await fetch('api/medicines.php');
      const data = await res.json();
      if (data.success && data.medicines) {
        let html = '';
        data.medicines.forEach(m => {
          html += `
            <div class="medicine-card">
              <div class="medicine-img-box">
                <img src="${m.packaging_ref_image || 'assets/images/medicines/napa_extra.svg'}" alt="${m.brand_name}" />
              </div>
              <div class="medicine-card-body">
                <div class="medicine-name">${m.brand_name}</div>
                <div class="medicine-generic">${m.generic_name} • ${m.strength}</div>
                <div style="font-size: 0.8rem; color: var(--gray-600); margin-bottom: 0.5rem;">
                  🏢 <strong>${m.manufacturer_name}</strong>
                </div>
                <div style="font-size: 0.85rem; color: var(--gray-700); margin-bottom: 1rem; flex: 1;">
                  ${m.indications_merits ? m.indications_merits.substring(0, 85) + '...' : ''}
                </div>
                <div class="medicine-mrp">
                  <span>MRP ৳ ${parseFloat(m.mrp_bdt).toFixed(2)}</span>
                  <button class="btn btn-secondary" style="padding: 0.35rem 0.75rem; font-size: 0.8rem;" onclick="app.inspectCatalogMedicine(${m.id})">
                    🔍 View Profile
                  </button>
                </div>
              </div>
            </div>
          `;
        });
        container.innerHTML = html;
      }
    } catch (e) {
      console.warn('Catalog load error:', e);
    }
  }

  async inspectCatalogMedicine(id) {
    try {
      const res = await fetch('api/medicines.php?id=' + id);
      const data = await res.json();
      if (data.success && data.medicine) {
        const m = data.medicine;
        this.displayVerificationResult({
          status: 'GENUINE',
          title: m.brand_name + ' (' + m.strength + ') Profile',
          message: 'Official pharmaceutical reference profile registered with DGDA Bangladesh.',
          barcode_serial: 'MS-2026-' + m.brand_name.toUpperCase().substring(0, 4) + '-REF01',
          medicine: m
        });
      }
    } catch (e) {
      console.error(e);
    }
  }

  async loadIncidents() {
    const listEl = document.getElementById('incidents-table-body');
    if (!listEl) return;

    try {
      const res = await fetch('api/analytics.php');
      const data = await res.json();
      if (data.success && data.incidents) {
        let html = '';
        data.incidents.forEach(inc => {
          let pillClass = 'status-review';
          if (inc.status.includes('Seized') || inc.status.includes('Raid')) pillClass = 'status-seized';
          else if (inc.status.includes('Investigating') || inc.status.includes('Enforcement')) pillClass = 'status-investigating';

          html += `
            <tr style="border-bottom: 1px solid var(--gray-200); font-size: 0.9rem;">
              <td style="padding: 0.75rem 1rem; font-weight: 700;">${inc.medicine_name}</td>
              <td style="padding: 0.75rem 1rem;">${inc.pharmacy_name}, ${inc.pharmacy_address}</td>
              <td style="padding: 0.75rem 1rem;">${inc.city}</td>
              <td style="padding: 0.75rem 1rem;">
                <span class="status-pill ${pillClass}">${inc.status}</span>
              </td>
              <td style="padding: 0.75rem 1rem; color: var(--gray-500); font-size: 0.8rem;">${inc.created_at}</td>
            </tr>
          `;
        });
        listEl.innerHTML = html || '<tr><td colspan="5" style="text-align: center; padding: 1.5rem;">No active counterfeit incidents reported.</td></tr>';
      }
    } catch (e) {
      console.warn('Analytics load error:', e);
    }
  }

  initAIInspector() {
    const fileInput = document.getElementById('ai-photo-upload');
    const previewBox = document.getElementById('ai-preview-container');
    const previewImg = document.getElementById('ai-preview-image');
    const analyzeBtn = document.getElementById('btn-run-ai-inspect');

    if (fileInput) {
      fileInput.addEventListener('change', (e) => {
        const file = e.target.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = (evt) => {
          previewImg.src = evt.target.result;
          previewBox.style.display = 'block';
          if (analyzeBtn) analyzeBtn.disabled = false;
        };
        reader.readAsDataURL(file);
      });
    }

    if (analyzeBtn) {
      analyzeBtn.addEventListener('click', async () => {
        const statusEl = document.getElementById('ai-analysis-feedback');
        statusEl.innerHTML = '🤖 Analyzing packaging pixel structure, color tone, and logo typography...';
        
        const selectedMed = document.getElementById('ai-select-medicine').value;
        let refColor = '#e11d48';
        if (selectedMed === 'seclo') refColor = '#0284c7';
        else if (selectedMed === 'monas') refColor = '#16a34a';
        else if (selectedMed === 'ace') refColor = '#2563eb';
        else if (selectedMed === 'maxpro') refColor = '#9333ea';

        if (window.packagingAIVision) {
          const res = await window.packagingAIVision.analyzePackaging(previewImg, refColor);
          
          document.getElementById('score-overall-val').textContent = res.overallScore + '%';
          document.getElementById('bar-overall').style.width = res.overallScore + '%';

          document.getElementById('score-color-val').textContent = res.colorMatchScore + '%';
          document.getElementById('bar-color').style.width = res.colorMatchScore + '%';

          document.getElementById('score-typo-val').textContent = res.typographyScore + '%';
          document.getElementById('bar-typo').style.width = res.typographyScore + '%';

          if (res.isAuthentic) {
            statusEl.innerHTML = '<span style="color: #059669; font-weight: 700;">✅ ' + res.verdict + '</span> - Packaging design matches official pharmaceutical benchmark profile.';
          } else {
            statusEl.innerHTML = '<span style="color: #b91c1c; font-weight: 700;">⚠️ ' + res.verdict + '</span> - Suspicious color hue or printing anomaly detected!';
          }
        }
      });
    }
  }
}

window.addEventListener('DOMContentLoaded', () => {
  window.app = new App();
});

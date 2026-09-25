# 🛡️ Medicine-Shield (Oushodh-Shield)
### Counterfeit Drug Verification & Public Health Protection System (Bangladesh)

A full-stack, AI-powered anti-counterfeit drug verification platform built for Bangladesh and global pharmaceutical security. Combines single-use cryptographic 1D Barcode Ledgers with on-device Computer Vision packaging validation (MobileNet AI).

---

## 🚀 Key Features

1. **Smart 4-Layer Anti-Counterfeit Verification**:
   - **Layer 1 (Smart In-Store Shelf Browsing)**: Solves the retail browsing challenge. If multiple customers inspect a box on the same pharmacy shelf before buying, the system recognizes pre-purchase browsing and keeps the product **100% Genuine** without false counterfeit alarms.
   - **Layer 2 (Post-Purchase Ownership Lock & Claim)**: Upon purchase, the consumer or pharmacy locks the cryptographic serial token into their account. This permanently retires the code in the national ledger so counterfeiters cannot reuse or refill genuine packaging.
   - **Layer 3 (AI Spatial Velocity & Cloned Anomaly Detection)**: If a single serial code is scanned in distant cities simultaneously (e.g., Dhaka and Chittagong within minutes), the AI flags an impossible travel anomaly and triggers an automated counterfeit syndicate alert to DGDA.
   - **Layer 4 (On-Device MobileNet AI Visual Inspector)**:
     - Runs 100% on-device in the browser using TensorFlow.js MobileNet v2.
     - Performs multi-factor analysis: color tone spectrum match against reference Pantone CMYK standards, typography boundary sharpness, and logo placement.
2. **Complete Drug Information & Clinical Guidelines**:
   - Indications / Clinical Merits
   - Demerits / Adverse Side Effects
   - Dosage Guidelines & Warnings
   - Official DGDA Maximum Retail Price (MRP in BDT)
3. **Gamified Citizen "Health Guardian" Rewards**:
   - +50 Points for every genuine scan.
   - +150 Points for reporting counterfeit drug shops to DGDA.
   - Points redeemable for Mobile Recharges (Grameenphone, Banglalink, Robi) or 10% Pharmacy Vouchers.
4. **Manufacturer Production Portal (`/manufacturer`)**:
   - Register batches and generate bulk cryptographic Code128 barcodes.
   - Ready-to-print industrial sticker sheets for thermal blister pack printers.
5. **Regulatory / DGDA Command Center (`/regulatory`)**:
   - Real-time intelligence feed and hotspot tracking for Mitford, Babubazar, Chittagong, Sylhet, etc.
   - Incident dossier management for Police & Magistrate raids.

---

## 🛠️ Technology Stack

- **Backend**: Raw PHP (PHP 8.2+ / PHP 8.5+)
- **Database**: Standard SQL (MySQL / MariaDB 8.0+ / MySQL Workbench)
- **Frontend**: Vanilla HTML5, Modern CSS3 (Glassmorphism & Responsive Healthcare UI), JavaScript ES6+
- **Barcode Engine**: JsBarcode 1D Code128 / EAN-13 Rendering & HTML5 BarcodeDetector / Camera Scanner
- **AI Model**: TensorFlow.js Pre-trained MobileNet v2 + HTML5 Canvas Computer Vision

---

## 💻 Setup & Running with MySQL / MySQL Workbench

### Step 1: Database Setup in MySQL Workbench

1. Open **MySQL Workbench** on your machine.
2. Connect to your local MySQL Server (`127.0.0.1:3306`).
3. Click **File -> Open SQL Script...** and select:
   ```
   /Users/tasnim/.gemini/antigravity/scratch/medicine-shield/database/schema.sql
   ```
4. Click the **Execute (Lightning Bolt ⚡)** button to run the script. This creates the `medicine_shield` database schema, all tables, foreign keys, and Bangladeshi pharma seed data (Square, Beximco, Incepta, Renata, ACME).

### Step 2: Start PHP Built-in Server

In your Terminal (or from this directory):

```bash
cd /Users/tasnim/.gemini/antigravity/scratch/medicine-shield
php -S localhost:8000
```

Now open your web browser and navigate to:
👉 **[http://localhost:8000](http://localhost:8000)**

---

## 🧪 Test Scenarios & Presets

You can test every scenario directly using the built-in preset chips on the homepage:

| Test Scenario | Barcode Serial | Expected System Response |
|---|---|---|
| **Fresh Genuine Drug** | `MS-2026-NAPA-7821A` | ✅ **Verified 100% Genuine** (Awards +50 pts, records location & time) |
| **Fresh Genuine Drug 2** | `MS-2026-SECLO-9914C` | ✅ **Verified 100% Genuine** (Seclo 20 Omeprazole) |
| **Duplicate / Reused Code** | `MS-2026-NAPA-DUPL-998` | ⚠️ **Duplicate Warning** (Alerts that it was scanned previously in Mitford Market, Old Dhaka) |
| **Duplicate Reused 2** | `MS-2026-SECL-DUPL-441` | ⚠️ **Duplicate Warning** (Previously scanned in Chittagong) |
| **Police Raid Blacklist** | `MS-2026-FAKE-RAID-001` | 🚫 **Blacklisted / Recalled Batch** (Seized in Police Raid) |
| **Counterfeit / Invalid** | `MS-INVALID-FAKE-CODE-999`| ❌ **Unrecognized Barcode** (Prompts immediate DGDA Report) |

---

## 📁 Project Directory Structure

```
medicine-shield/
├── config/
│   └── db.php                  # Database connection (MySQL with auto-fallback)
├── database/
│   └── schema.sql              # MySQL Workbench ready SQL DDL & Seed script
├── api/
│   ├── verify_barcode.php      # Anti-duplication cryptographic ledger verification
│   ├── generate_barcode.php    # Batch barcode generator API
│   ├── report_counterfeit.php  # Public counterfeit incident reporting API
│   ├── medicines.php           # Medicine clinical catalog & MRP API
│   ├── rewards.php             # Gamification & voucher redemption API
│   └── analytics.php           # Regulatory hotspot analytics API
├── assets/
│   ├── css/
│   │   └── style.css           # Modern medical UI design system
│   ├── js/
│   │   ├── barcode_scanner.js  # Code128 rendering & camera scanner
│   │   ├── ai_vision.js        # MobileNet v2 neural packaging inspection
│   │   ├── rewards.js          # Points wallet & reward redemption
│   │   └── app.js              # Application orchestrator & audio alerts
│   └── images/
│       └── medicines/          # Authentic packaging reference vectors
├── manufacturer/
│   └── index.php               # Manufacturer production & barcode generator portal
├── regulatory/
│   └── index.php               # DGDA enforcement dashboard & outbreak map
├── partials/
│   ├── header.php              # Navigation & branding header
│   └── footer.php              # Footer & script loaders
├── index.php                   # Consumer verification portal
└── README.md                   # Documentation & quickstart guide
```

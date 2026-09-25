# 🛡️ Medicine-Shield (Oushodh-Shield)

### AI-Powered Counterfeit Drug Verification & Public Health Protection System

[![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?logo=php\&logoColor=white)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-8.0+-4479A1?logo=mysql\&logoColor=white)](https://www.mysql.com/)
[![JavaScript](https://img.shields.io/badge/JavaScript-ES6+-F7DF1E?logo=javascript\&logoColor=black)](https://developer.mozilla.org/en-US/docs/Web/JavaScript)
[![TensorFlow.js](https://img.shields.io/badge/TensorFlow.js-MobileNet%20v2-FF6F00?logo=tensorflow\&logoColor=white)](https://www.tensorflow.org/js)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](#license)

**Medicine-Shield** is a full-stack anti-counterfeit drug verification platform designed for **Bangladesh's pharmaceutical ecosystem**. It combines **single-use cryptographic barcode verification**, **AI-powered packaging inspection**, and **regulatory intelligence** to help consumers, pharmacies, manufacturers, and the Directorate General of Drug Administration (DGDA) combat counterfeit medicines.

## 🌐 Live Demo

**Consumer Portal:** https://medicineshield.free.je/login.php

> **Recommended:** Open on Brave or Chrome or Edge for the best barcode scanning experience.

---

## ✨ Key Features

### 🛡️ Four-Layer Anti-Counterfeit Protection

#### Layer 1 — Smart In-Store Shelf Browsing

* Prevents false counterfeit alerts when multiple customers inspect the same medicine before purchase.
* Keeps genuine products verified during normal pharmacy shelf browsing.

#### Layer 2 — Post-Purchase Ownership Lock

* Locks the barcode to the buyer's account after purchase.
* Permanently retires the cryptographic serial from the national ledger.
* Prevents genuine packaging from being reused by counterfeiters.

#### Layer 3 — AI Spatial Velocity Detection

* Detects impossible travel scenarios.
* Example: the same barcode appears in **Dhaka** and **Chattogram** within minutes.
* Automatically flags suspicious activity for DGDA investigation.

#### Layer 4 — On-Device AI Packaging Inspector

Powered by **TensorFlow.js MobileNet v2**.

The browser performs local visual verification without uploading images.

Checks include:

* Color tone consistency
* Typography sharpness
* Logo alignment
* Packaging layout validation

---

## 💊 Drug Information Portal

Every verified medicine displays:

* Indications
* Clinical benefits
* Side effects
* Dosage guidance
* Safety warnings
* Official DGDA Maximum Retail Price (BDT)

---

## 🏆 Health Guardian Rewards

Gamification encourages public participation.

| Action               | Reward      |
| -------------------- | ----------- |
| Genuine verification | +50 points  |
| Counterfeit report   | +150 points |

Rewards can be redeemed for:

* Mobile recharge
* Pharmacy discount vouchers

---

## 🏭 Manufacturer Portal (`/manufacturer`)

Manufacturers can:

* Register production batches
* Generate bulk Code128 barcodes
* Produce thermal-printer-ready barcode sheets
* Manage production records

---

## 🏛️ DGDA Regulatory Dashboard (`/regulatory`)

Provides enforcement intelligence including:

* Counterfeit hotspot tracking
* Real-time incident feed
* Investigation dossiers
* Police raid support
* Regional monitoring for Dhaka, Mitford, Babubazar, Chattogram, Sylhet, and more.

---

## 📸 Screenshots

> Replace these with your project screenshots.

* Homepage
* Barcode Verification
* AI Packaging Inspection
* Manufacturer Dashboard
* DGDA Command Center

---

## 🛠 Technology Stack

| Component       | Technology                        |
| --------------- | --------------------------------- |
| Backend         | PHP 8.2+                          |
| Database        | MySQL / MariaDB                   |
| Database Tool   | MySQL Workbench                   |
| Frontend        | HTML5, CSS3, JavaScript (ES6+)    |
| Barcode         | JsBarcode + HTML5 BarcodeDetector |
| AI              | TensorFlow.js MobileNet v2        |
| Computer Vision | HTML5 Canvas                      |
| UI              | Glassmorphism Responsive Design   |

---

## 🚀 Getting Started

### 1. Clone the Repository

```bash
git clone https://github.com/your-username/medicine-shield.git
cd medicine-shield
```

### 2. Configure the Database

Open **MySQL Workbench**.

1. Connect to `127.0.0.1:3306`.
2. Open:

```text
database/schema.sql
```

3. Click the **⚡ Execute** button.

This creates:

* `medicine_shield` database
* Required tables
* Foreign keys
* Sample Bangladeshi pharmaceutical data

### 3. Start the PHP Server

```bash
php -S localhost:8000
```

Open:

```text
http://localhost:8000
```

---

## 🧪 Test Scenarios

| Scenario       | Barcode                    | Expected Result        |
| -------------- | -------------------------- | ---------------------- |
| Genuine Drug   | `MS-2026-NAPA-7821A`       | ✅ Verified Genuine     |
| Genuine Drug 2 | `MS-2026-SECLO-9914C`      | ✅ Verified Genuine     |
| Duplicate      | `MS-2026-NAPA-DUPL-998`    | ⚠️ Duplicate Warning   |
| Duplicate 2    | `MS-2026-SECL-DUPL-441`    | ⚠️ Previously Scanned  |
| Police Raid    | `MS-2026-FAKE-RAID-001`    | 🚫 Blacklisted         |
| Invalid Code   | `MS-INVALID-FAKE-CODE-999` | ❌ Unrecognized Barcode |

---

## 📂 Project Structure

```text
medicine-shield/
├── api/
│   ├── analytics.php
│   ├── generate_barcode.php
│   ├── medicines.php
│   ├── report_counterfeit.php
│   ├── rewards.php
│   └── verify_barcode.php
│
├── assets/
│   ├── css/
│   ├── images/
│   └── js/
│
├── config/
│   └── db.php
│
├── database/
│   └── schema.sql
│
├── manufacturer/
│   └── index.php
│
├── regulatory/
│   └── index.php
│
├── partials/
│   ├── footer.php
│   └── header.php
│
├── index.php
└── README.md
```

---

## 🔄 Verification Workflow

```text
Consumer scans barcode
        │
        ▼
Cryptographic Verification
        │
        ▼
Ownership Validation
        │
        ▼
AI Packaging Inspection
        │
        ▼
Risk Analysis
        │
        ▼
Final Result
(Genuine / Duplicate / Blacklisted / Counterfeit)
```

---

## 🎯 Intended Impact

Medicine-Shield aims to:

* Reduce counterfeit medicine circulation.
* Protect consumers.
* Assist pharmacies.
* Empower DGDA enforcement.
* Improve pharmaceutical supply-chain transparency.
* Encourage citizen participation through rewards.

---

## 🔮 Future Enhancements

* QR + NFC hybrid verification
* Offline Progressive Web App (PWA)
* Blockchain-backed audit trail
* OCR-based expiry and batch detection
* Multi-language support (Bangla & English)
* Hospital and pharmacy API integration

---

## 👨‍💻 Author

**Sk Tasnim Ur Rahman**

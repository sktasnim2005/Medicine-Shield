<?php
// partials/header.php
require_once __DIR__ . '/../config/auth.php';
$currentUser = Auth::getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Medicine-Shield (Oushodh-Shield) • Counterfeit Drug Verification System</title>
  
  <!-- Zero-Flicker Instant Theme Applicator -->
  <script>
    (function() {
      try {
        var theme = localStorage.getItem('medicine_shield_theme') || 'auto';
        document.documentElement.setAttribute('data-theme', theme);
      } catch(e){}
    })();
  </script>

  <!-- Current Authenticated User Session for JavaScript -->
  <script>
    window.CURRENT_USER = <?php echo json_encode($currentUser ? [
      'id' => (int)$currentUser['id'],
      'name' => $currentUser['name'],
      'email' => $currentUser['email'],
      'role' => $currentUser['role']
    ] : null, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG); ?>;
  </script>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  
  <link rel="stylesheet" href="/assets/css/style.css">
  
  <!-- Theme Management Script -->
  <script src="/assets/js/theme.js"></script>

  <!-- JsBarcode Library for 1D Barcode Rendering -->
  <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
  
  <!-- TensorFlow.js + MobileNet Pretrained AI Model -->
  <script src="https://cdn.jsdelivr.net/npm/@tensorflow/tfjs@4.10.0/dist/tf.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/@tensorflow-models/mobilenet@2.1.0/dist/mobilenet.min.js"></script>
</head>
<body>

<header class="navbar">
  <div class="nav-container">
    <a href="/" class="brand">
      <div class="brand-icon">🛡️</div>
      <div>
        <div>Medicine-Shield</div>
        <div style="font-size: 0.65rem; color: #0284c7; font-weight: 700; letter-spacing: 0.5px; text-transform: uppercase;">Oushodh-Shield Bangladesh</div>
      </div>
    </a>

    <nav class="nav-links">
      <a href="/">🔍 Consumer Scanner</a>
      <a href="/manufacturer">🏭 Manufacturer Portal</a>
      <a href="/regulatory">⚖️ DGDA Enforcement</a>
    </nav>

    <div style="display: flex; align-items: center; gap: 0.75rem;">
      <!-- Light / Dark / Auto Theme Selector -->
      <div class="theme-toggle-group" title="Select Theme: Light, Dark, or System Auto">
        <button class="theme-btn" data-theme-set="light" onclick="themeManager.setTheme('light')" title="Light Mode">
          ☀️ <span style="font-size:0.75rem;">Light</span>
        </button>
        <button class="theme-btn" data-theme-set="dark" onclick="themeManager.setTheme('dark')" title="Dark Mode">
          🌙 <span style="font-size:0.75rem;">Dark</span>
        </button>
        <button class="theme-btn" data-theme-set="auto" onclick="themeManager.setTheme('auto')" title="System Auto Mode">
          💻 <span style="font-size:0.75rem;">Auto</span>
        </button>
      </div>

      <?php if ($currentUser): ?>
        <!-- Logged In User Profile -->
        <div style="display: flex; align-items: center; gap: 0.5rem;">
          <?php if ($currentUser['role'] === 'citizen'): ?>
            <button id="user-points-badge" class="nav-badge-btn" onclick="app.openReportModal()">
              ⭐ <strong>150 pts</strong> • Health Guardian
            </button>
            <button class="btn btn-secondary" style="padding: 0.4rem 0.75rem; font-size: 0.85rem;" onclick="rewardManager.redeemReward('Mobile Recharge BDT 50')">
              🎁 Rewards
            </button>
          <?php elseif ($currentUser['role'] === 'manufacturer'): ?>
            <span class="status-pill status-investigating" style="font-size: 0.8rem; padding: 0.4rem 0.75rem;">
              🏭 <?php echo htmlspecialchars($currentUser['manufacturer_name'] ?? 'Pharma Manufacturer'); ?>
            </span>
          <?php elseif ($currentUser['role'] === 'dgda'): ?>
            <span class="status-pill status-review" style="font-size: 0.8rem; padding: 0.4rem 0.75rem;">
              ⚖️ DGDA Inspector #<?php echo htmlspecialchars($currentUser['badge_no'] ?? '77'); ?>
            </span>
          <?php endif; ?>

          <span style="font-size: 0.85rem; font-weight: 700; color: var(--gray-700); margin-left: 0.25rem;">
            <?php echo htmlspecialchars($currentUser['name']); ?>
          </span>

          <a href="/logout" class="btn btn-secondary" style="padding: 0.4rem 0.75rem; font-size: 0.85rem; color: #b91c1c; border-color: #fecaca;" title="Sign Out">
            🚪 Logout
          </a>
        </div>
      <?php else: ?>
        <!-- Guest View -->
        <a href="/login" class="btn btn-primary" style="padding: 0.45rem 1rem; font-size: 0.85rem;">
          🔐 Sign In
        </a>
      <?php endif; ?>
    </div>
  </div>
</header>

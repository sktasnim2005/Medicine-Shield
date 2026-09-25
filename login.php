<?php
/**
 * Medicine-Shield (Oushodh-Shield)
 * Multi-Role Login Gateway & Portal Selector
 */

require_once __DIR__ . '/config/auth.php';

$error = '';
$success = '';

if (isset($_GET['logged_out'])) {
    $success = 'You have been logged out successfully.';
}
if (isset($_GET['registered'])) {
    $success = 'Account created successfully! Please sign in with your credentials.';
}
if (isset($_GET['auth_required'])) {
    $error = 'Please sign in to access this portal.';
}
if (isset($_GET['unauthorized'])) {
    $req = htmlspecialchars($_GET['required'] ?? 'authorized');
    $error = "Access Restricted: You must be signed in as a " . strtoupper($req) . " to access that portal.";
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $role = trim($_POST['role'] ?? '');

    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password.';
    } else {
        $res = Auth::login($email, $password, !empty($role) ? $role : null);
        if ($res['success']) {
            header("Location: " . $res['redirect']);
            exit;
        } else {
            $error = $res['error'];
        }
    }
}

$currentUser = Auth::getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login Portal • Medicine-Shield (Oushodh-Shield)</title>
  
  <!-- Zero-Flicker Instant Theme Applicator -->
  <script>
    (function() {
      try {
        var theme = localStorage.getItem('medicine_shield_theme') || 'auto';
        document.documentElement.setAttribute('data-theme', theme);
      } catch(e){}
    })();
  </script>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  
  <link rel="stylesheet" href="/assets/css/style.css">
  <script src="/assets/js/theme.js"></script>
  
  <style>
    .auth-page-wrapper {
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      background: var(--bg-body);
      padding: 2rem 1.5rem;
      position: relative;
    }

    .auth-container {
      width: 100%;
      max-width: 520px;
      background: var(--bg-card);
      border-radius: var(--radius-lg);
      border: 1px solid var(--border-color);
      box-shadow: var(--shadow-lg);
      overflow: hidden;
    }

    .auth-header {
      background: linear-gradient(135deg, #0f172a 0%, #1e293b 70%, #0369a1 100%);
      color: white;
      padding: 2.25rem 2rem 1.75rem;
      text-align: center;
      position: relative;
    }

    .auth-role-tabs {
      display: grid;
      grid-template-columns: 1fr 1fr 1fr;
      background: var(--bg-surface-elevated);
      padding: 0.35rem;
      border-radius: var(--radius-sm);
      margin-bottom: 1.5rem;
      border: 1px solid var(--border-color);
    }

    .role-tab-btn {
      background: none;
      border: none;
      font-family: inherit;
      font-size: 0.85rem;
      font-weight: 700;
      color: var(--text-muted);
      padding: 0.6rem 0.5rem;
      border-radius: 6px;
      cursor: pointer;
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 0.25rem;
      transition: all 0.2s ease;
    }

    .role-tab-btn.active {
      background: var(--bg-card);
      color: var(--primary);
      box-shadow: var(--shadow-sm);
    }

    .demo-login-box {
      background: var(--bg-surface-elevated);
      border: 1px dashed var(--border-subtle);
      border-radius: var(--radius-sm);
      padding: 1rem;
      margin-top: 1.5rem;
      text-align: center;
    }

    .demo-btn-group {
      display: flex;
      flex-direction: column;
      gap: 0.5rem;
      margin-top: 0.75rem;
    }

    .demo-btn {
      font-size: 0.8rem;
      font-weight: 700;
      padding: 0.5rem 0.85rem;
      border-radius: 6px;
      border: 1px solid var(--border-color);
      background: var(--bg-surface);
      color: var(--text-main);
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: space-between;
      transition: all 0.15s ease;
    }

    .demo-btn:hover {
      background: var(--primary-light);
      border-color: var(--primary);
      color: var(--primary);
    }

    .alert-banner {
      padding: 0.85rem 1rem;
      border-radius: var(--radius-sm);
      font-size: 0.85rem;
      font-weight: 600;
      margin-bottom: 1.25rem;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }
    .alert-danger { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }
    .alert-success { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
  </style>
</head>
<body>

<div class="auth-page-wrapper">
  <!-- Brand Logo -->
  <div style="margin-bottom: 1.5rem; text-align: center;">
    <a href="/" style="display: inline-flex; align-items: center; gap: 0.75rem; text-decoration: none; color: var(--gray-900);">
      <div class="brand-icon" style="width: 46px; height: 46px; font-size: 1.6rem;">🛡️</div>
      <div style="text-align: left;">
        <div style="font-size: 1.45rem; font-weight: 800;">Medicine-Shield</div>
        <div style="font-size: 0.75rem; color: #0284c7; font-weight: 700; text-transform: uppercase;">Anti-Counterfeit Verification Portal</div>
      </div>
    </a>
  </div>

  <div class="auth-container">
    <div class="auth-header">
      <h2 id="login-title" style="font-size: 1.35rem; font-weight: 800;">Portal Access Login</h2>
      <p id="login-subtitle" style="font-size: 0.85rem; color: #cbd5e1; margin-top: 0.25rem;">
        Select your role below to access your dedicated verification dashboard
      </p>
    </div>

    <div style="padding: 1.75rem 2rem;">
      <?php if ($error): ?>
        <div class="alert-banner alert-danger">
          <span>⚠️</span>
          <div><?php echo htmlspecialchars($error); ?></div>
        </div>
      <?php endif; ?>

      <?php if ($success): ?>
        <div class="alert-banner alert-success">
          <span>✅</span>
          <div><?php echo htmlspecialchars($success); ?></div>
        </div>
      <?php endif; ?>

      <!-- Role Selector Tabs -->
      <div class="auth-role-tabs">
        <button type="button" class="role-tab-btn active" onclick="switchRole('citizen', '👤 Citizen Guardian', 'Scan medicine barcodes, check visual packaging AI, and redeem points.')">
          <span style="font-size: 1.2rem;">👤</span>
          <span>Citizen</span>
        </button>
        <button type="button" class="role-tab-btn" onclick="switchRole('manufacturer', '🏭 Pharma Manufacturer', 'Generate cryptographic batch barcodes & print packaging labels.')">
          <span style="font-size: 1.2rem;">🏭</span>
          <span>Manufacturer</span>
        </button>
        <button type="button" class="role-tab-btn" onclick="switchRole('dgda', '⚖️ DGDA Inspector', 'Access national counterfeit dossiers & police raid telemetry.')">
          <span style="font-size: 1.2rem;">⚖️</span>
          <span>DGDA</span>
        </button>
      </div>

      <!-- Login Form -->
      <form method="POST" action="/login" id="login-form">
        <input type="hidden" name="role" id="input-role" value="citizen">

        <div style="margin-bottom: 1.25rem;">
          <label style="font-size: 0.85rem; font-weight: 700; color: var(--gray-700); display: block; margin-bottom: 0.35rem;">
            Official Email Address:
          </label>
          <input type="email" name="email" id="email-field" class="input-field" style="width: 100%;" required placeholder="name@domain.com" />
        </div>

        <div style="margin-bottom: 1.5rem;">
          <label style="font-size: 0.85rem; font-weight: 700; color: var(--gray-700); display: block; margin-bottom: 0.35rem;">Password:</label>
          <input type="password" name="password" id="password-field" class="input-field" style="width: 100%;" required placeholder="••••••••" />
        </div>

        <button type="submit" id="btn-submit-login" class="btn btn-primary btn-block" style="padding: 0.85rem;">
          🔐 Sign In to Portal
        </button>
      </form>

      <!-- Registration link for citizens -->
      <div style="margin-top: 1.5rem; text-align: center; font-size: 0.85rem; color: var(--gray-600);">
        New citizen? <a href="/register" style="color: var(--primary); font-weight: 700; text-decoration: none;">Create a Health Guardian Account</a>
      </div>

    </div>
  </div>
</div>

<script>
function switchRole(role, title, subtitle) {
  document.querySelectorAll('.role-tab-btn').forEach(btn => btn.classList.remove('active'));
  event.currentTarget.classList.add('active');
  
  document.getElementById('input-role').value = role;
  document.getElementById('login-title').textContent = title;
  document.getElementById('login-subtitle').textContent = subtitle;
}
</script>

</body>
</html>

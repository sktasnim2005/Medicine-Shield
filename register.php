<?php
/**
 * Medicine-Shield (Oushodh-Shield)
 * Citizen Registration Portal
 */

require_once __DIR__ . '/config/auth.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($name) || empty($email) || empty($password)) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $pdo = Database::getConnection();
        
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $email]);
        if ($stmt->fetch()) {
            $error = 'An account with this email address already exists. Please login instead.';
        } else {
            $passHash = password_hash($password, PASSWORD_BCRYPT);
            $ins = $pdo->prepare("
                INSERT INTO users (name, email, password_hash, role, badge_or_license_no)
                VALUES (:name, :email, :hash, 'citizen', :badge)
            ");
            $ins->execute([
                ':name' => $name,
                ':email' => $email,
                ':hash' => $passHash,
                ':badge' => 'CITIZEN-' . strtoupper(substr(md5($email), 0, 8))
            ]);

            $newUserId = $pdo->lastInsertId();

            // Give bonus starting reward points
            $pdo->prepare("
                INSERT INTO user_rewards (user_id, points, badge_title)
                VALUES (:u, 150, 'Health Guardian Lv. 1')
            ")->execute([':u' => (string)$newUserId]);

            header("Location: /login?registered=1");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Citizen Registration • Medicine-Shield</title>
  
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
    }
    .auth-container {
      width: 100%;
      max-width: 500px;
      background: var(--bg-card);
      border-radius: var(--radius-lg);
      border: 1px solid var(--border-color);
      box-shadow: var(--shadow-lg);
      overflow: hidden;
    }
  </style>
</head>
<body>

<div class="auth-page-wrapper">
  <div style="margin-bottom: 1.5rem; text-align: center;">
    <a href="/" style="display: inline-flex; align-items: center; gap: 0.75rem; text-decoration: none; color: var(--gray-900);">
      <div class="brand-icon" style="width: 44px; height: 44px; font-size: 1.5rem;">🛡️</div>
      <div style="text-align: left;">
        <div style="font-size: 1.35rem; font-weight: 800;">Medicine-Shield</div>
        <div style="font-size: 0.7rem; color: #0284c7; font-weight: 700; text-transform: uppercase;">Citizen Health Guardian Registration</div>
      </div>
    </a>
  </div>

  <div class="auth-container">
    <div style="background: linear-gradient(135deg, #059669, #10b981); color: white; padding: 2rem; text-align: center;">
      <h2 style="font-size: 1.35rem; font-weight: 800;">Join as a Health Guardian</h2>
      <p style="font-size: 0.85rem; opacity: 0.95; margin-top: 0.25rem;">
        Earn reward points for verifying genuine medicines & reporting counterfeit shops
      </p>
    </div>

    <div style="padding: 1.75rem 2rem;">
      <?php if ($error): ?>
        <div style="background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; padding: 0.75rem; border-radius: var(--radius-sm); font-size: 0.85rem; margin-bottom: 1.25rem;">
          ⚠️ <?php echo htmlspecialchars($error); ?>
        </div>
      <?php endif; ?>

      <form method="POST" action="/register">
        <div style="margin-bottom: 1rem;">
          <label style="font-size: 0.85rem; font-weight: 700; color: var(--gray-700); display: block; margin-bottom: 0.25rem;">Full Name: *</label>
          <input type="text" name="name" class="input-field" style="width: 100%;" required placeholder="e.g. Tasnimur Rahman" />
        </div>

        <div style="margin-bottom: 1rem;">
          <label style="font-size: 0.85rem; font-weight: 700; color: var(--gray-700); display: block; margin-bottom: 0.25rem;">Email Address: *</label>
          <input type="email" name="email" class="input-field" style="width: 100%;" required placeholder="tasnimur@example.com" />
        </div>

        <div style="margin-bottom: 1rem;">
          <label style="font-size: 0.85rem; font-weight: 700; color: var(--gray-700); display: block; margin-bottom: 0.25rem;">Mobile Number (Optional for Top-Up Rewards):</label>
          <input type="tel" name="phone" class="input-field" style="width: 100%;" placeholder="017xxxxxxxx" />
        </div>

        <div style="margin-bottom: 1.5rem;">
          <label style="font-size: 0.85rem; font-weight: 700; color: var(--gray-700); display: block; margin-bottom: 0.25rem;">Create Password: *</label>
          <input type="password" name="password" class="input-field" style="width: 100%;" required placeholder="At least 6 characters" />
        </div>

        <button type="submit" class="btn btn-success btn-block" style="padding: 0.85rem;">
          ⭐ Register & Claim 150 Welcome Points
        </button>
      </form>

      <div style="margin-top: 1.5rem; text-align: center; font-size: 0.85rem; color: var(--gray-600);">
        Already have an account? <a href="/login" style="color: var(--primary); font-weight: 700; text-decoration: none;">Sign In here</a>
      </div>

    </div>
  </div>
</div>

</body>
</html>

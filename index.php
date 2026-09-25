<?php
// ============================================================
//  index.php — Front controller / URL router
//  Run with: php -S localhost:8000 index.php
// ============================================================

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$uri = rtrim($uri, '/');
if ($uri === '') $uri = '/';

// ── Route map ─────────────────────────────────────────────────
$routes = [
    // ── Public Pages ──────────────────────────────────────────
    '/'                       => 'scanner.php',
    '/scanner'                => 'scanner.php',
    '/verify'                 => 'scanner.php',

    // ── Authentication ────────────────────────────────────────
    '/login'                  => 'login.php',
    '/register'               => 'register.php',
    '/logout'                 => 'logout.php',

    // ── Portals ───────────────────────────────────────────────
    '/manufacturer'           => 'manufacturer/index.php',
    '/manufacturer/barcodes'  => 'manufacturer/medicine_barcodes.php',
    '/barcodes'               => 'manufacturer/medicine_barcodes.php',
    '/regulatory'             => 'regulatory/index.php',
    '/dgda'                   => 'regulatory/index.php',

    // ── Direct .php routes (backward compatibility) ───────────
    '/scanner.php'                           => 'scanner.php',
    '/login.php'                             => 'login.php',
    '/register.php'                          => 'register.php',
    '/logout.php'                            => 'logout.php',
    '/manufacturer/index.php'                => 'manufacturer/index.php',
    '/manufacturer/medicine_barcodes.php'    => 'manufacturer/medicine_barcodes.php',
    '/regulatory/index.php'                  => 'regulatory/index.php',

    // ── API Endpoints (clean routes + .php) ───────────────────
    '/api/verify_barcode'          => 'api/verify_barcode.php',
    '/api/verify_barcode.php'      => 'api/verify_barcode.php',
    '/api/claim_medicine'          => 'api/claim_medicine.php',
    '/api/claim_medicine.php'      => 'api/claim_medicine.php',
    '/api/request_medicine'        => 'api/request_medicine.php',
    '/api/request_medicine.php'    => 'api/request_medicine.php',
    '/api/approve_medicine'        => 'api/approve_medicine.php',
    '/api/approve_medicine.php'    => 'api/approve_medicine.php',
    '/api/generate_barcode'        => 'api/generate_barcode.php',
    '/api/generate_barcode.php'    => 'api/generate_barcode.php',
    '/api/report_counterfeit'      => 'api/report_counterfeit.php',
    '/api/report_counterfeit.php'  => 'api/report_counterfeit.php',
    '/api/medicines'               => 'api/medicines.php',
    '/api/medicines.php'           => 'api/medicines.php',
    '/api/rewards'                 => 'api/rewards.php',
    '/api/rewards.php'             => 'api/rewards.php',
    '/api/analytics'               => 'api/analytics.php',
    '/api/analytics.php'           => 'api/analytics.php',
    '/api/get_manufacturer_barcodes'     => 'api/get_manufacturer_barcodes.php',
    '/api/get_manufacturer_barcodes.php' => 'api/get_manufacturer_barcodes.php',
];

// ── Serve static files (css, js, images, fonts) ──────────────
$static = __DIR__ . $uri;
if ($uri !== '/' && file_exists($static) && is_file($static)) {
    return false;
}

// ── Match route ───────────────────────────────────────────────
if (isset($routes[$uri])) {
    $file = __DIR__ . '/' . $routes[$uri];
    if (file_exists($file)) {
        require $file;
    } else {
        http_response_code(500);
        echo "<div style='font-family:sans-serif;padding:40px;text-align:center'>
                <h2>⚠️ Route file not found</h2>
                <p><code>" . htmlspecialchars($routes[$uri]) . "</code></p>
              </div>";
    }
} else {
    http_response_code(404);
    echo "<!DOCTYPE html>
    <html lang='en'>
    <head>
      <meta charset='UTF-8'>
      <meta name='viewport' content='width=device-width, initial-scale=1.0'>
      <title>404 — Page Not Found • Medicine-Shield</title>
      <link rel='stylesheet' href='/assets/css/style.css'>
    </head>
    <body style='display:flex;align-items:center;justify-content:center;min-height:100vh;background:var(--bg-app);font-family:\"Plus Jakarta Sans\",sans-serif;margin:0;'>
      <div style='text-align:center;padding:2.5rem;background:var(--bg-surface);border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,0.08);max-width:480px;width:90%;border:1px solid var(--border-color);'>
        <div style='font-size:3rem;margin-bottom:0.75rem;'>🛡️</div>
        <h2 style='font-size:1.75rem;margin:0 0 0.5rem;color:var(--text-main);'>404 — Page Not Found</h2>
        <p style='color:var(--text-muted);font-size:0.95rem;line-height:1.5;margin-bottom:1.5rem;'>
          The requested URL <code>" . htmlspecialchars($uri) . "</code> was not found in the Medicine-Shield registry.
        </p>
        <a href='/' class='btn btn-primary' style='display:inline-block;padding:0.6rem 1.5rem;text-decoration:none;border-radius:8px;font-weight:700;'>
          ← Back to Live Scanner
        </a>
      </div>
    </body>
    </html>";
}

<?php
/**
 * FitForFaith — Installation Script
 *
 * Run once to set up the database, then DELETE this file.
 * Access: https://yourdomain.com/install.php
 *
 * IMPORTANT: Delete or rename this file after successful installation!
 */

// Simple security: require a token to run in production
$setupToken = 'change-this-token-before-use';
$provided   = $_GET['token'] ?? '';

// Self-delete action
if (($_GET['action'] ?? '') === 'delete' && $provided === $setupToken) {
    @unlink(__FILE__);
    die('<div style="font-family:sans-serif;background:#0b0b18;color:#6ee7b7;padding:40px;text-align:center;">
         <h2>✓ install.php deleted successfully.</h2>
         <p style="color:#94a3b8;margin-top:12px;"><a href="/" style="color:#8b5cf6;">Go to site →</a></p>
         </div>');
}

if (PHP_SAPI === 'cli') {
    // Allow running from CLI without token
} elseif ($provided !== $setupToken) {
    die('<h1>Access Denied</h1><p>Set ?token=your-token in the URL. Edit install.php to set your token.</p>');
}

define('BASE_PATH', __DIR__);

// Load config without bootstrapping full app
if (!file_exists(__DIR__ . '/config/database.php')) {
    die('<h1>Setup Error</h1><p>Copy config/database.example.php to config/database.php and fill in your credentials.</p>');
}

require_once __DIR__ . '/config/database.php';

$dsn = 'mysql:host=' . DB_HOST . ';port=' . (DB_PORT ?? 3306) . ';charset=utf8mb4';
try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) {
    die('<h1>Database Connection Failed</h1><pre>' . htmlspecialchars($e->getMessage()) . '</pre>');
}

// Create database if it doesn't exist
$pdo->exec('CREATE DATABASE IF NOT EXISTS `' . DB_NAME . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
$pdo->exec('USE `' . DB_NAME . '`');

// Read and execute schema
$schema = file_get_contents(__DIR__ . '/database/schema.sql');
if (!$schema) die('Could not read database/schema.sql');

// Split by semicolons, execute each statement
$statements = array_filter(
    array_map('trim', explode(';', $schema)),
    fn($s) => $s !== '' && !str_starts_with($s, '--')
);

$errors   = [];
$executed = 0;
foreach ($statements as $stmt) {
    try {
        $pdo->exec($stmt);
        $executed++;
    } catch (PDOException $e) {
        // Ignore "already exists" errors
        if (!strpos($e->getMessage(), 'already exists') !== false &&
            !strpos($e->getMessage(), 'Duplicate entry') !== false) {
            $errors[] = $e->getMessage() . "\nSQL: " . substr($stmt, 0, 100);
        }
    }
}

// Create default admin user
$adminEmail    = 'admin@example.com';
$adminPassword = 'Admin@12345'; // Change immediately after install

$existing = $pdo->prepare('SELECT id FROM users WHERE email = ?');
$existing->execute([$adminEmail]);
$adminCreated = false;

if (!$existing->fetch()) {
    $hash = password_hash($adminPassword, PASSWORD_BCRYPT, ['cost' => 12]);
    $pdo->prepare(
        'INSERT INTO users (first_name, last_name, email, password_hash, role, is_active, is_paid)
         VALUES (?,?,?,?,?,?,?)'
    )->execute(['Admin', 'User', $adminEmail, $hash, 'admin', 1, 1]);
    $adminCreated = true;
}

// Add some sample churches
$sampleChurches = [
    ['name' => 'Grace Church',    'slug' => 'grace-church',    'city' => 'Cape Town'],
    ['name' => 'Hope Fellowship', 'slug' => 'hope-fellowship', 'city' => 'Johannesburg'],
    ['name' => 'Faith Community', 'slug' => 'faith-community', 'city' => 'Pretoria'],
];
$churchesAdded = 0;
foreach ($sampleChurches as $church) {
    $check = $pdo->prepare('SELECT id FROM churches WHERE slug = ?');
    $check->execute([$church['slug']]);
    if (!$check->fetch()) {
        $pdo->prepare('INSERT INTO churches (name, slug, city) VALUES (?,?,?)')
            ->execute([$church['name'], $church['slug'], $church['city']]);
        $pdo->exec('INSERT IGNORE INTO church_points_cache (church_id) VALUES (' . $pdo->lastInsertId() . ')');
        $churchesAdded++;
    }
}

// Create storage directories
$dirs = [
    BASE_PATH . '/storage/cache',
    BASE_PATH . '/storage/logs',
    BASE_PATH . '/storage/qrcodes',
];
foreach ($dirs as $dir) {
    if (!is_dir($dir)) mkdir($dir, 0755, true);
}

// Output result
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>FitForFaith — Installation</title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { background: #0b0b18; color: #f1f5f9; font-family: system-ui, sans-serif; padding: 40px 20px; }
    .card { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; padding: 32px; max-width: 680px; margin: 0 auto; }
    h1 { font-size: 2rem; font-weight: 900; margin-bottom: 8px; }
    .success { color: #6ee7b7; } .error { color: #fca5a5; } .warn { color: #fcd34d; }
    .info { background: rgba(59,130,246,0.1); border: 1px solid rgba(59,130,246,0.3); border-radius: 8px; padding: 16px; margin: 16px 0; }
    .item { display: flex; gap: 10px; padding: 8px 0; border-bottom: 1px solid rgba(255,255,255,0.06); }
    .btn { display: inline-block; padding: 12px 28px; background: linear-gradient(135deg,#7c3aed,#ec4899); color: #fff; border-radius: 100px; text-decoration: none; font-weight: 700; margin-top: 20px; }
    pre { background: rgba(0,0,0,0.3); padding: 12px; border-radius: 8px; font-size: 0.8rem; overflow-x: auto; margin: 8px 0; }
  </style>
</head>
<body>
<div class="card">
  <h1>🎉 Installation Complete</h1>
  <p style="color:#94a3b8; margin-top:8px;">FitForFaith platform has been set up.</p>

  <div style="margin-top:24px;">
    <div class="item">
      <span class="success">✓</span>
      <span>Database <strong><?= DB_NAME ?></strong> created/verified</span>
    </div>
    <div class="item">
      <span class="success">✓</span>
      <span><?= $executed ?> SQL statements executed</span>
    </div>
    <?php if ($adminCreated): ?>
    <div class="item">
      <span class="success">✓</span>
      <span>Admin account created</span>
    </div>
    <?php endif; ?>
    <div class="item">
      <span class="success">✓</span>
      <span><?= $churchesAdded ?> sample churches added</span>
    </div>
    <div class="item">
      <span class="success">✓</span>
      <span>Storage directories created</span>
    </div>
    <?php if ($errors): ?>
    <div class="item">
      <span class="warn">⚠</span>
      <span><?= count($errors) ?> non-critical warnings (usually "already exists")</span>
    </div>
    <?php endif; ?>
  </div>

  <?php if ($adminCreated): ?>
  <div class="info" style="margin-top:20px;">
    <strong>Default Admin Account</strong><br>
    Email: <code><?= $adminEmail ?></code><br>
    Password: <code><?= $adminPassword ?></code><br>
    <span class="warn">⚠ Change this password immediately after logging in!</span>
  </div>
  <?php endif; ?>

  <div class="info warn" style="border-color:rgba(245,158,11,0.3); background:rgba(245,158,11,0.1); margin-top:20px;">
    <strong>⚠ IMPORTANT — Next Steps:</strong>
    <ol style="margin-top:8px; padding-left:20px; line-height:2;">
      <li>Update <code>config/app.php</code> — set <code>APP_URL</code> to your real domain</li>
      <li>Update <code>config/strava.php</code> — add Strava Client ID & Secret</li>
      <li>Update <code>config/payfast.php</code> — add PayFast credentials</li>
      <li>Set <code>APP_ENV</code> to <code>production</code> in config/app.php</li>
      <li>Set up cron jobs (see CRON_SETUP.md)</li>
      <li>Register Strava webhook (see STRAVA_WEBHOOK_SETUP.md)</li>
      <li class="error"><strong>DELETE or rename this install.php file!</strong></li>
    </ol>
  </div>

  <a href="/" class="btn">Open FitForFaith →</a>
  <a href="/admin" class="btn" style="margin-left:10px; background:rgba(255,255,255,0.1);">Admin Panel →</a>
  <a href="install.php?token=<?= htmlspecialchars($setupToken) ?>&action=delete"
     class="btn" style="margin-left:10px; background:rgba(220,38,38,0.7);"
     onclick="return confirm('Delete install.php now? This cannot be undone.')">
     🗑 Delete install.php
  </a>

  <?php if ($errors): ?>
  <details style="margin-top:20px;">
    <summary style="cursor:pointer; color:#94a3b8;">Show warnings (<?= count($errors) ?>)</summary>
    <?php foreach ($errors as $err): ?>
      <pre><?= htmlspecialchars($err) ?></pre>
    <?php endforeach; ?>
  </details>
  <?php endif; ?>
</div>
</body>
</html>

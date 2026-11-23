<?php
// One-time setup: creates the database, tables, sample menu and demo accounts.
// Open http://localhost:8000/setup.php   (add ?reset=1 to wipe and rebuild everything)
$config = require __DIR__ . '/../config/config.php';
$log = [];
$ok = false;

if (!in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)) {
    http_response_code(403);
    exit('Setup can only be run from localhost.');
}

function run_sql_file(PDO $pdo, string $file): void
{
    $sql = preg_replace('/^--.*$/m', '', file_get_contents($file));
    foreach (array_filter(array_map('trim', explode(';', $sql))) as $statement) {
        $pdo->exec($statement);
    }
}

try {
    $db = $config['db'];
    $pdo = new PDO("mysql:host={$db['host']};port={$db['port']};charset=utf8mb4", $db['user'], $db['pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $name = preg_replace('/[^A-Za-z0-9_]/', '', $db['name']);
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$name`");
    $log[] = "Database '$name' is ready.";

    if (isset($_GET['reset'])) {
        $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
        foreach (['payments', 'order_items', 'orders', 'menu_items', 'categories', 'users'] as $t) {
            $pdo->exec("DROP TABLE IF EXISTS `$t`");
        }
        $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
        $log[] = 'Old tables dropped (reset).';
    }

    run_sql_file($pdo, __DIR__ . '/../database/schema.sql');
    $log[] = 'Tables created.';

    if ((int)$pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn() === 0) {
        run_sql_file($pdo, __DIR__ . '/../database/seed.sql');
        $log[] = 'Sample menu inserted.';
    }

    $insert = $pdo->prepare('INSERT IGNORE INTO users (name, email, password_hash, phone, role) VALUES (?, ?, ?, ?, ?)');
    $insert->execute(['Admin User', 'admin@foodhub.test', password_hash('Admin@123', PASSWORD_DEFAULT), '01700000000', 'admin']);
    $insert->execute(['Demo Customer', 'customer@foodhub.test', password_hash('Customer@123', PASSWORD_DEFAULT), '01800000000', 'customer']);
    $log[] = 'Demo accounts ready.';
    $ok = true;
} catch (Throwable $e) {
    $log[] = 'Error: ' . $e->getMessage();
    $log[] = 'Check that MySQL is running and that config/config.php has the right user and password.';
}
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>FoodHub Setup</title><link rel="stylesheet" href="assets/css/style.css"></head>
<body><main class="container narrow">
<h1>FoodHub setup</h1>
<ul>
<?php foreach ($log as $line): ?><li><?= htmlspecialchars($line, ENT_QUOTES, 'UTF-8') ?></li><?php endforeach; ?>
</ul>
<?php if ($ok): ?>
<div class="alert alert-success">
<p><strong>All done.</strong></p>
<p>Admin: <code>admin@foodhub.test</code> / <code>Admin@123</code><br>
Customer: <code>customer@foodhub.test</code> / <code>Customer@123</code></p>
</div>
<p><a class="btn" href="index.php">Open FoodHub</a></p>
<?php endif; ?>
</main></body></html>

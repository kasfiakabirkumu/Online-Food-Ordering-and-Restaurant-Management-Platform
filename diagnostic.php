<?php
declare(strict_types=1);

$config = require __DIR__ . '/../config/config.php';
if (!in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)) {
    http_response_code(403);
    exit('This diagnostic page is available only from localhost.');
}

$drivers = PDO::getAvailableDrivers();
$pdoMysql = in_array('mysql', $drivers, true);
$dbStatus = 'Not tested: PDO MySQL driver is unavailable.';
$dbError = '';

if ($pdoMysql) {
    try {
        $db = $config['db'];
        new PDO(
            "mysql:host={$db['host']};port={$db['port']};dbname={$db['name']};charset=utf8mb4",
            $db['user'],
            $db['pass'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        $dbStatus = 'Connected successfully to the configured database.';
    } catch (Throwable $e) {
        $dbStatus = 'Connection failed.';
        $dbError = $e->getMessage();
    }
}
function h(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>FoodHub diagnostics</title>
<style>
body{font:16px/1.5 Arial,sans-serif;background:#f7f7f7;color:#222;margin:0;padding:28px}
main{max-width:760px;margin:auto;background:white;padding:24px;border-radius:12px}
code{background:#eee;padding:2px 5px;border-radius:4px;overflow-wrap:anywhere}
.ok{color:#087a36}.bad{color:#b42318}li{margin:10px 0}
</style>
</head>
<body><main>
<h1>FoodHub – Environment check</h1>
<ul>
<li>PHP version: <code><?= h(PHP_VERSION) ?></code></li>
<li>PHP executable: <code><?= h(PHP_BINARY) ?></code></li>
<li>Loaded php.ini: <code><?= h(php_ini_loaded_file() ?: '(none)') ?></code></li>
<li>PDO drivers: <code><?= h($drivers ? implode(', ', $drivers) : '(none)') ?></code></li>
<li>PDO MySQL: <strong class="<?= $pdoMysql ? 'ok' : 'bad' ?>"><?= $pdoMysql ? 'Available' : 'Missing' ?></strong></li>
<li>Database connection: <strong class="<?= $dbError === '' && $pdoMysql ? 'ok' : 'bad' ?>"><?= h($dbStatus) ?></strong></li>
</ul>
<?php if ($dbError !== ''): ?>
<h2>Connection detail</h2><pre><?= h($dbError) ?></pre>
<?php endif; ?>
<p>When the database connection succeeds, open <a href="setup.php">setup.php</a> to create the FoodHub tables and demo data.</p>
</main></body></html>

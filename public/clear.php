<?php
define('DEV_KEY', 'TELCOVANTAGEDEVELOPERS@2026!');

$key = $_GET['key'] ?? '';
if (hash('sha256', $key) !== hash('sha256', DEV_KEY)) {
    http_response_code(401);
    die(json_encode(['error' => 'Unauthorized']));
}

header('Content-Type: application/json');

$base    = dirname(__DIR__);
$results = [];

// View cache
$viewDir = $base . '/storage/framework/views';
$count   = 0;
if (is_dir($viewDir)) {
    foreach (glob($viewDir . '/*.php') as $f) {
        @unlink($f) && $count++;
    }
}
$results['view_cache'] = "cleared ({$count} files)";

// App cache
$cacheDir = $base . '/storage/framework/cache/data';
$count    = 0;
if (is_dir($cacheDir)) {
    $iter = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($cacheDir, FilesystemIterator::SKIP_DOTS));
    foreach ($iter as $f) {
        if ($f->isFile()) { @unlink($f->getPathname()) && $count++; }
    }
}
$results['app_cache'] = "cleared ({$count} files)";

// Config cache
$configCache = $base . '/bootstrap/cache/config.php';
$results['config_cache'] = file_exists($configCache) && @unlink($configCache) ? 'cleared' : 'not found';

// Route cache
foreach (glob($base . '/bootstrap/cache/routes*.php') as $f) {
    @unlink($f);
}
$results['route_cache'] = 'cleared';

echo json_encode([
    'status'  => 'ok',
    'message' => 'All caches cleared.',
    'results' => $results,
    'time'    => date('Y-m-d H:i:s'),
], JSON_PRETTY_PRINT);

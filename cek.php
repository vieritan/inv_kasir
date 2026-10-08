<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h2>Cek Error PHP:</h2>";
try {
    require_once __DIR__ . '/index.php';
} catch (Throwable $e) {
    echo "<b>Fatal Error:</b> " . $e->getMessage() . " on line " . $e->getLine() . " in " . $e->getFile();
}
?>

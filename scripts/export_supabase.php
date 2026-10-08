<?php
// scripts/export_supabase.php
// Script untuk meng-export seluruh data dari Supabase ke JSON & SQL Backup

require_once __DIR__ . '/../config/db.php';

$tables = ['app_users', 'customers', 'invoices', 'items', 'sales', 'settings', 'suppliers'];
$backupDir = __DIR__ . '/../data/backup_' . date('Ymd_His');

if (!is_dir($backupDir)) {
    mkdir($backupDir, 0777, true);
}

echo "=== EXPORT DATA FROM SUPABASE ===\n\n";

$allData = [];

foreach ($tables as $table) {
    echo "Mengambil data tabel '$table'... ";
    
    if ($table === 'settings') {
        $res = supabaseRequest('settings?select=*');
    } else {
        $res = supabaseRequest($table . '?select=*&order=id.asc');
    }
    
    if (is_array($res)) {
        $count = count($res);
        echo "SUKSES ($count rows)\n";
        $allData[$table] = $res;
        
        // Save table JSON
        file_put_contents($backupDir . '/' . $table . '.json', json_encode($res, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    } else {
        echo "GAGAL (Gagal mengambil data)\n";
        $allData[$table] = [];
    }
}

// Generate SQL Export File
$sqlFile = $backupDir . '/full_backup.sql';
$sql = "-- SUPABASE FULL DATABASE BACKUP\n";
$sql .= "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";

// Include Schema SQL
if (file_exists(__DIR__ . '/../config/schema.sql')) {
    $sql .= "-- --- SCHEMA STRUCT ---\n";
    $sql .= file_get_contents(__DIR__ . '/../config/schema.sql') . "\n\n";
}

$sql .= "-- --- DATA INSERTS ---\n\n";

foreach ($allData as $table => $rows) {
    if (empty($rows)) continue;
    
    $sql .= "-- Data for table: $table\n";
    
    foreach ($rows as $row) {
        $cols = [];
        $vals = [];
        
        foreach ($row as $k => $v) {
            $cols[] = "\"$k\"";
            if ($v === null) {
                $vals[] = "NULL";
            } elseif (is_bool($v)) {
                $vals[] = $v ? 'TRUE' : 'FALSE';
            } elseif (is_array($v) || is_object($v)) {
                $jsonStr = str_replace("'", "''", json_encode($v, JSON_UNESCAPED_UNICODE));
                $vals[] = "'$jsonStr'::jsonb";
            } elseif (is_numeric($v)) {
                $vals[] = $v;
            } else {
                $escaped = str_replace("'", "''", (string)$v);
                $vals[] = "'$escaped'";
            }
        }
        
        $sql .= "INSERT INTO public.\"$table\" (" . implode(", ", $cols) . ") VALUES (" . implode(", ", $vals) . ") ON CONFLICT DO NOTHING;\n";
    }
    $sql .= "\n";
}

file_put_contents($sqlFile, $sql);

echo "\n=========================================\n";
echo "EXPORT SELESAI!\n";
echo "Folder Backup: " . realpath($backupDir) . "\n";
echo "File SQL Backup: " . realpath($sqlFile) . "\n";
echo "=========================================\n";

<?php
// scripts/generate_mysql_import.php

$tablesMap = [
    'app_users' => 'users',
    'customers' => 'customers',
    'invoices' => 'invoices',
    'items' => 'items',
    'sales' => 'sales',
    'settings' => 'settings',
    'suppliers' => 'suppliers'
];

$allowedColumns = [
    'invoices' => ['id', 'invoice_no', 'invoice_date', 'sales_id', 'sales_name', 'customer_id', 'customer_name', 'total_dus', 'total_bonus', 'total_amount', 'is_return', 'is_cash', 'notes', 'items', 'created_at'],
    'sales' => ['id', 'code', 'name', 'buyer_count', 'debt', 'limit_amount', 'due_days', 'phone'],
    'customers' => ['id', 'code', 'name', 'address', 'phone', 'ktp'],
    'suppliers' => ['id', 'code', 'name', 'address', 'phone'],
    'items' => ['id', 'code', 'name', 'unit', 'cost_price', 'sell_price', 'stock'],
    'app_users' => ['id', 'username', 'password', 'name', 'role']
];

$backupDir = __DIR__ . '/../data';
$sqlFile = $backupDir . '/mysql_data_import.sql';

$sql = "-- MYSQL DATA IMPORT\n";
$sql .= "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";
$sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

foreach ($tablesMap as $table => $jsonName) {
    $jsonFile = $backupDir . '/' . $jsonName . '.json';
    if (!file_exists($jsonFile)) continue;

    $content = file_get_contents($jsonFile);
    $rows = json_decode($content, true);

    if (empty($rows)) continue;

    $sql .= "-- Data untuk tabel: $table\n";

    if ($table === 'settings') {
        $jsonStr = str_replace("'", "''", json_encode($rows, JSON_UNESCAPED_UNICODE));
        $sql .= "INSERT IGNORE INTO `$table` (`key`, `value`) VALUES ('main_settings', '$jsonStr');\n";
    } else {
        $colsAllowed = $allowedColumns[$table] ?? [];
        
        foreach ($rows as $row) {
            $cols = [];
            $vals = [];

            foreach ($row as $k => $v) {
                if (!in_array($k, $colsAllowed)) continue; // Abaikan kolom yang tidak ada di schema MySQL
                
                $cols[] = "`$k`";
                
                if ($v === null) {
                    $vals[] = "NULL";
                } elseif (is_bool($v)) {
                    $vals[] = $v ? '1' : '0';
                } elseif (is_array($v) || is_object($v)) {
                    $jsonStr = str_replace("'", "''", json_encode($v, JSON_UNESCAPED_UNICODE));
                    $vals[] = "'$jsonStr'";
                } elseif (is_numeric($v) && !in_array($k, ['code', 'phone', 'ktp', 'invoice_no'])) {
                    $vals[] = $v;
                } else {
                    $escaped = str_replace("'", "''", (string)$v);
                    $vals[] = "'$escaped'";
                }
            }

            $sql .= "INSERT IGNORE INTO `$table` (" . implode(", ", $cols) . ") VALUES (" . implode(", ", $vals) . ");\n";
        }
    }
    $sql .= "\n";
}

$sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
file_put_contents($sqlFile, $sql);
echo "Berhasil update SQL Import!";

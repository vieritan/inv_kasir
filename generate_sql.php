<?php
require_once __DIR__ . '/config/db.php'; // Use local db.php which points to Supabase/JSON

$tables = [
    'users.json' => 'app_users',
    'sales.json' => 'sales',
    'customers.json' => 'customers',
    'suppliers.json' => 'suppliers',
    'items.json' => 'items',
    'invoices.json' => 'invoices'
];

$sql = "";

foreach ($tables as $jsonFile => $tableName) {
    $data = loadData($jsonFile);
    if (empty($data)) continue;
    
    // TRUNCATE to avoid duplicates
    $sql .= "TRUNCATE TABLE `$tableName`;\n";
    
    foreach ($data as $row) {
        $cols = [];
        $vals = [];
        foreach ($row as $k => $v) {
            // Skip redundant 'limit' field since we already use limit_amount
            if ($tableName === 'sales' && $k === 'limit') {
                continue;
            }
            if ($tableName === 'invoices' && $k === 'items') {
                if (is_array($v)) $v = json_encode($v, JSON_UNESCAPED_UNICODE);
            }
            $cols[] = "`$k`";
            
            if ($v === null) {
                $vals[] = "NULL";
            } else {
                $escaped = addslashes((string)$v);
                $vals[] = "'$escaped'";
            }
        }
        $colStr = implode(", ", $cols);
        $valStr = implode(", ", $vals);
        $sql .= "INSERT INTO `$tableName` ($colStr) VALUES ($valStr);\n";
    }
    $sql .= "\n";
}

// Settings
$settings = getInvoiceSettings();
$settingsJson = addslashes(json_encode($settings, JSON_UNESCAPED_UNICODE));
$sql .= "TRUNCATE TABLE `settings`;\n";
$sql .= "INSERT INTO `settings` (`key`, `value`) VALUES ('main_settings', '$settingsJson');\n";

file_put_contents('import_data.sql', $sql);
echo "SQL File generated: import_data.sql";
?>

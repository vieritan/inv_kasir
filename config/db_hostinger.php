<?php
// config/db.php - MySQL Data Store Manager untuk Inventory 3 (HOSTINGER)

define('DB_HOST', 'localhost');
define('DB_NAME', 'u659347505_inventory');
define('DB_USER', 'u659347505_admin');
define('DB_PASS', '@Kayolaoffice2026');

define('DATA_DIR', __DIR__ . '/../data');

if (!is_dir(DATA_DIR)) {
    mkdir(DATA_DIR, 0755, true);
}

function getDbConnection() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            die("Koneksi Database Gagal: " . $e->getMessage());
        }
    }
    return $pdo;
}

function getDataFile($filename) {
    return DATA_DIR . '/' . $filename;
}

/**
 * Load data directly from MySQL
 */
function loadData($filename, $forceSync = false) {
    $pdo = getDbConnection();

    $tableMap = [
        'users.json' => 'app_users',
        'sales.json' => 'sales',
        'customers.json' => 'customers',
        'suppliers.json' => 'suppliers',
        'items.json' => 'items',
        'invoices.json' => 'invoices'
    ];

    if ($filename === 'settings.json') {
        $stmt = $pdo->prepare("SELECT `value` FROM settings WHERE `key` = 'main_settings'");
        $stmt->execute();
        $res = $stmt->fetch();
        if ($res && !empty($res['value'])) {
            return json_decode($res['value'], true);
        }
        return [];
    } elseif (isset($tableMap[$filename])) {
        $table = $tableMap[$filename];
        $order = ($table === 'invoices') ? 'id DESC' : 'id ASC';
        
        $stmt = $pdo->prepare("SELECT * FROM `$table` ORDER BY $order");
        $stmt->execute();
        $res = $stmt->fetchAll();
        
        // Sesuaikan tipe data dan decode JSON untuk MySQL
        foreach ($res as &$row) {
            if ($table === 'sales' && isset($row['limit_amount'])) {
                $row['limit'] = $row['limit_amount'];
            }
            if ($table === 'invoices' && isset($row['items'])) {
                $decodedItems = json_decode($row['items'], true);
                $row['items'] = is_array($decodedItems) ? $decodedItems : [];
            }
        }
        
        // Simpan cache lokal supaya file json tetap ada
        $filePath = getDataFile($filename);
        @file_put_contents($filePath, json_encode($res, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        return $res;
    }

    return [];
}

/**
 * Save data to MySQL
 */
function saveData($filename, $data) {
    $pdo = getDbConnection();
    
    // Sync local file
    $filePath = getDataFile($filename);
    file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    $tableMap = [
        'users.json' => 'app_users',
        'sales.json' => 'sales',
        'customers.json' => 'customers',
        'suppliers.json' => 'suppliers',
        'items.json' => 'items',
        'invoices.json' => 'invoices'
    ];

    $allowedColumns = [
        'invoices' => ['id', 'invoice_no', 'invoice_date', 'sales_id', 'sales_name', 'customer_id', 'customer_name', 'total_dus', 'total_bonus', 'total_amount', 'is_return', 'is_cash', 'notes', 'items'],
        'sales' => ['id', 'code', 'name', 'buyer_count', 'debt', 'limit_amount', 'due_days', 'phone'],
        'customers' => ['id', 'code', 'name', 'address', 'phone', 'ktp'],
        'suppliers' => ['id', 'code', 'name', 'address', 'phone'],
        'items' => ['id', 'code', 'name', 'unit', 'cost_price', 'sell_price', 'stock'],
        'app_users' => ['id', 'username', 'password', 'name', 'role']
    ];

    if ($filename === 'settings.json') {
        $jsonVal = json_encode($data, JSON_UNESCAPED_UNICODE);
        $stmt = $pdo->prepare("INSERT INTO settings (`key`, `value`) VALUES ('main_settings', ?) ON DUPLICATE KEY UPDATE `value`=VALUES(`value`)");
        $stmt->execute([$jsonVal]);
    } elseif (isset($tableMap[$filename])) {
        $table = $tableMap[$filename];
        
        if (is_array($data) && !empty($data)) {
            $cols = $allowedColumns[$table] ?? null;
            if (!$cols) return;

            $pdo->beginTransaction();
            try {
                foreach ($data as $row) {
                    if (is_array($row)) {
                        $insertCols = [];
                        $insertVals = [];
                        $updateStrArr = [];
                        $params = [];

                        foreach ($cols as $col) {
                            $val = null;
                            if ($col === 'limit_amount' && isset($row['limit'])) {
                                $val = $row['limit'];
                            } elseif (array_key_exists($col, $row)) {
                                $val = $row[$col];
                            }
                            
                            if ($val !== null) {
                                $insertCols[] = "`$col`";
                                $insertVals[] = "?";
                                if ($col !== 'id') {
                                    $updateStrArr[] = "`$col`=VALUES(`$col`)";
                                }
                                
                                // Jika array/object, jadikan json string (khusus kolom items di invoices)
                                if (is_array($val) || is_object($val)) {
                                    $val = json_encode($val, JSON_UNESCAPED_UNICODE);
                                }
                                $params[] = $val;
                            }
                        }

                        if (!empty($insertCols)) {
                            $sql = "INSERT INTO `$table` (" . implode(', ', $insertCols) . ") VALUES (" . implode(', ', $insertVals) . ") ON DUPLICATE KEY UPDATE " . implode(', ', $updateStrArr);
                            $stmt = $pdo->prepare($sql);
                            $stmt->execute($params);
                        }
                    }
                }
                $pdo->commit();
            } catch (Exception $e) {
                $pdo->rollBack();
                error_log("Error saving to $table: " . $e->getMessage());
            }
        }
    }
}

// Get Custom Invoice Settings
function getInvoiceSettings() {
    $defaults = [
        'company_name' => 'KAYOLA',
        'company_address' => 'Jl. Raya Pasar Kemis',
        'company_phone' => 'Telp. 081806366565',
        'last_invoice_number' => 23926,
        'auto_today_date' => 1,
        'default_date' => date('Y-m-d'),
        'print_font_weight' => 'bold',
        'print_font_size' => '13px'
    ];
    $saved = loadData('settings.json');
    return array_merge($defaults, is_array($saved) ? $saved : []);
}

// Save Custom Invoice Settings
function saveInvoiceSettings($settings) {
    saveData('settings.json', $settings);
}

// Generate Next Sequential Invoice Number
function getNextInvoiceNumber() {
    $settings = getInvoiceSettings();
    $nextNo = (int)$settings['last_invoice_number'] + 1;
    $settings['last_invoice_number'] = $nextNo;
    saveInvoiceSettings($settings);
    return str_pad($nextNo, 11, '0', STR_PAD_LEFT);
}

// Peek Next Invoice Number Without Incrementing
function peekNextInvoiceNumber() {
    $settings = getInvoiceSettings();
    $nextNo = (int)$settings['last_invoice_number'] + 1;
    return str_pad($nextNo, 11, '0', STR_PAD_LEFT);
}

/**
 * Delete invoices by array of IDs from both local JSON and MySQL DB
 */
function deleteInvoicesFromStore($idsToDelete) {
    if (empty($idsToDelete) || !is_array($idsToDelete)) {
        return 0;
    }

    $idsToDelete = array_values(array_unique(array_map('intval', $idsToDelete)));
    $pdo = getDbConnection();
    
    $placeholders = implode(',', array_fill(0, count($idsToDelete), '?'));
    $stmt = $pdo->prepare("DELETE FROM invoices WHERE id IN ($placeholders)");
    $stmt->execute($idsToDelete);
    $deletedCount = $stmt->rowCount();

    // Remove dari local cache
    $currentInvoices = loadData('invoices.json', false);
    $remaining = [];
    foreach ($currentInvoices as $inv) {
        $invId = (int)($inv['id'] ?? 0);
        if (!in_array($invId, $idsToDelete, true)) {
            $remaining[] = $inv;
        }
    }
    $filePath = getDataFile('invoices.json');
    file_put_contents($filePath, json_encode($remaining, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    return $deletedCount;
}


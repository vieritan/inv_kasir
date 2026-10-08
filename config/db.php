<?php
// config/db.php - Supabase Data Store Manager for Inventory 3

define('SUPABASE_URL', 'https://tpismvafhzxbzldtemfo.supabase.co');
define('SUPABASE_KEY', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InRwaXNtdmFmaHp4YnpsZHRlbWZvIiwicm9sZSI6ImFub24iLCJpYXQiOjE3ODU2NjcwNTIsImV4cCI6MjEwMTI0MzA1Mn0.CXeqjQss3nBoZuTWEPp-PtbDKlIIGLMwsZ6VFMRinnI');
define('DATA_DIR', __DIR__ . '/../data');

if (!is_dir(DATA_DIR)) {
    mkdir(DATA_DIR, 0755, true);
}

/**
 * Execute HTTP Request to Supabase PostgREST API
 */
function supabaseRequest($endpoint, $method = 'GET', $data = null, $extraHeaders = []) {
    $url = SUPABASE_URL . '/rest/v1/' . ltrim($endpoint, '/');
    $ch = curl_init($url);
    
    $headers = [
        'apikey: ' . SUPABASE_KEY,
        'Authorization: Bearer ' . SUPABASE_KEY,
        'Content-Type: application/json'
    ];

    $hasPrefer = false;
    foreach ($extraHeaders as $h) {
        if (stripos($h, 'Prefer:') === 0) {
            $hasPrefer = true;
            break;
        }
    }
    if (!$hasPrefer) {
        $headers[] = 'Prefer: return=representation';
    }
    $headers = array_merge($headers, $extraHeaders);
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
    
    if ($data !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_UNICODE));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    $decoded = json_decode($response, true);
    return ($httpCode >= 200 && $httpCode < 300) ? $decoded : false;
}

function getDataFile($filename) {
    return DATA_DIR . '/' . $filename;
}

/**
 * Load data from local JSON cache (ultra fast) with periodic Supabase sync
 */
function loadData($filename, $forceSync = false) {
    $filePath = getDataFile($filename);

    // Fast path: Return local cached JSON instantly if available & fresh (< 60s)
    if (!$forceSync && file_exists($filePath) && (time() - filemtime($filePath)) < 60) {
        $content = file_get_contents($filePath);
        $cached = json_decode($content, true);
        if (is_array($cached)) {
            return $cached;
        }
    }

    $tableMap = [
        'users.json' => 'app_users',
        'sales.json' => 'sales',
        'customers.json' => 'customers',
        'suppliers.json' => 'suppliers',
        'items.json' => 'items',
        'invoices.json' => 'invoices'
    ];

    if ($filename === 'settings.json') {
        $res = supabaseRequest('settings?key=eq.main_settings&select=value');
        if (is_array($res) && isset($res[0]['value'])) {
            $data = $res[0]['value'];
            @file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            return $data;
        }
    } elseif (isset($tableMap[$filename])) {
        $table = $tableMap[$filename];
        $order = ($table === 'invoices') ? 'id.desc' : 'id.asc';
        $res = supabaseRequest($table . '?select=*&order=' . $order);
        
        if (is_array($res)) {
            if ($table === 'sales') {
                foreach ($res as &$row) {
                    if (isset($row['limit_amount'])) {
                        $row['limit'] = $row['limit_amount'];
                    }
                }
            }
            @file_put_contents($filePath, json_encode($res, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            return $res;
        }
    }

    // Fallback to local file if network request failed or unavailable
    if (file_exists($filePath)) {
        $content = file_get_contents($filePath);
        return json_decode($content, true) ?: [];
    }

    return [];
}

/**
 * Save data to Supabase (and sync local JSON cache)
 */
function saveData($filename, $data) {
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
        supabaseRequest('settings?on_conflict=key', 'POST', [
            'key' => 'main_settings',
            'value' => $data
        ], ['Prefer: resolution=merge-duplicates,return=representation']);
    } elseif (isset($tableMap[$filename])) {
        $table = $tableMap[$filename];
        $formattedData = [];
        
        if (is_array($data) && !empty($data)) {
            $cols = $allowedColumns[$table] ?? null;
            foreach ($data as $row) {
                if (is_array($row)) {
                    $item = [];
                    if ($cols) {
                        foreach ($cols as $col) {
                            if ($col === 'limit_amount' && isset($row['limit'])) {
                                $item['limit_amount'] = $row['limit'];
                            } elseif (array_key_exists($col, $row)) {
                                $item[$col] = $row[$col];
                            }
                        }
                    } else {
                        $item = $row;
                        unset($item['created_at']);
                        if ($table === 'sales' && isset($item['limit'])) {
                            $item['limit_amount'] = $item['limit'];
                            unset($item['limit']);
                        }
                    }
                    $formattedData[] = $item;
                }
            }

            if (!empty($formattedData)) {
                supabaseRequest($table . '?on_conflict=id', 'POST', array_values($formattedData), [
                    'Prefer: resolution=merge-duplicates,return=representation'
                ]);
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
 * Delete invoices by array of IDs from both local JSON and Supabase DB
 */
function deleteInvoicesFromStore($idsToDelete) {
    if (empty($idsToDelete) || !is_array($idsToDelete)) {
        return 0;
    }

    $idsToDelete = array_values(array_unique(array_map('intval', $idsToDelete)));
    $filePath = getDataFile('invoices.json');

    // Remove from local JSON cache (instant local file load)
    $currentInvoices = loadData('invoices.json', false);
    $deletedCount = 0;
    $remaining = [];

    foreach ($currentInvoices as $inv) {
        $invId = (int)($inv['id'] ?? 0);
        if (in_array($invId, $idsToDelete, true)) {
            $deletedCount++;
        } else {
            $remaining[] = $inv;
        }
    }

    file_put_contents($filePath, json_encode($remaining, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    // Sync deletion to Supabase (single HTTP DELETE API call)
    if ($deletedCount > 0) {
        $idList = implode(',', $idsToDelete);
        supabaseRequest("invoices?id=in.({$idList})", 'DELETE');
    }

    return $deletedCount;
}


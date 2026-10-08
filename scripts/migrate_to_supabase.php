<?php
// scripts/migrate_to_supabase.php
// Script migrasi data dari file JSON lokal ke Supabase PostgreSQL

$supabaseUrl = 'https://pjuyihvoohmrddwkzikd.supabase.co';
$supabaseKey = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InBqdXlpaHZvb2htcmRkd2t6aWtkIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NjQ2ODkyMzIsImV4cCI6MjA4MDI2NTIzMn0.YJYGb2HJKDU-UAheEQcaw4UWEETchMhdjxpRqxtFtlc';

function supabasePostgrestRequest($endpoint, $method = 'GET', $data = null) {
    global $supabaseUrl, $supabaseKey;
    
    $url = $supabaseUrl . '/rest/v1/' . $endpoint;
    $ch = curl_init($url);
    
    $headers = [
        'apikey: ' . $supabaseKey,
        'Authorization: Bearer ' . $supabaseKey,
        'Content-Type: application/json',
        'Prefer: resolution=merge-duplicates,return=representation'
    ];
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    if ($data !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_UNICODE));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    return [
        'code' => $httpCode,
        'response' => json_decode($response, true),
        'raw' => $response,
        'error' => $error
    ];
}

$dataDir = __DIR__ . '/../data';

$fileTableMap = [
    'users.json' => 'users',
    'sales.json' => 'sales',
    'customers.json' => 'customers',
    'suppliers.json' => 'suppliers',
    'items.json' => 'items',
    'invoices.json' => 'invoices'
];

echo "=== MEMULAI MIGRASI DATA KE SUPABASE ===\n\n";

// 1. Migrasi Settings
$settingsFile = $dataDir . '/settings.json';
if (file_exists($settingsFile)) {
    $settingsData = json_decode(file_get_contents($settingsFile), true);
    if ($settingsData) {
        echo "Migrasi settings.json -> tabel settings... ";
        $res = supabasePostgrestRequest('settings', 'POST', [
            'key' => 'main_settings',
            'value' => $settingsData
        ]);
        echo ($res['code'] >= 200 && $res['code'] < 300) ? "SUKSES!\n" : "GAGAL (" . $res['code'] . "): " . $res['raw'] . "\n";
    }
}

// 2. Migrasi Tabel Lainnya
foreach ($fileTableMap as $file => $table) {
    $filePath = $dataDir . '/' . $file;
    if (!file_exists($filePath)) {
        continue;
    }
    
    $rows = json_decode(file_get_contents($filePath), true);
    if (empty($rows)) {
        continue;
    }
    
    echo "Migrasi $file -> tabel $table (" . count($rows) . " record)... ";
    
    // Normalisasi kolom jika ada penyesuaian
    if ($table === 'sales') {
        foreach ($rows as &$r) {
            if (isset($r['limit'])) {
                $r['limit_amount'] = $r['limit'];
                unset($r['limit']);
            }
        }
    }
    
    if ($table === 'invoices') {
        foreach ($rows as &$r) {
            if (isset($r['items']) && is_array($r['items'])) {
                // Keep as array, PostgREST will serialize jsonb
            }
        }
    }
    
    $res = supabasePostgrestRequest($table, 'POST', $rows);
    if ($res['code'] >= 200 && $res['code'] < 300) {
        echo "SUKSES!\n";
    } else {
        echo "GAGAL (" . $res['code'] . "): " . $res['raw'] . "\n";
    }
}

echo "\n=== MIGRASI SELESAI ===\n";

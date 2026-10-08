<?php
// scripts/seed_to_supabase.php
// Generates SQL inserts from items.json and invoices.json

$dataDir = __DIR__ . '/../data';

$items = json_decode(file_get_contents($dataDir . '/items.json'), true) ?: [];
$invoices = json_decode(file_get_contents($dataDir . '/invoices.json'), true) ?: [];

$sql = "";

// 1. Items
if (!empty($items)) {
    $sql .= "INSERT INTO public.items (id, code, name, unit, cost_price, sell_price, stock) VALUES \n";
    $itemRows = [];
    foreach ($items as $it) {
        $id = (int)$it['id'];
        $code = pg_escape_string_val($it['code']);
        $name = pg_escape_string_val($it['name']);
        $unit = pg_escape_string_val($it['unit'] ?? 'Dus');
        $cost = (float)($it['cost_price'] ?? 0);
        $sell = (float)($it['sell_price'] ?? 0);
        $stock = (int)($it['stock'] ?? 0);
        $itemRows[] = "($id, '$code', '$name', '$unit', $cost, $sell, $stock)";
    }
    $sql .= implode(",\n", $itemRows);
    $sql .= "\nON CONFLICT (id) DO UPDATE SET code = EXCLUDED.code, name = EXCLUDED.name, unit = EXCLUDED.unit, cost_price = EXCLUDED.cost_price, sell_price = EXCLUDED.sell_price, stock = EXCLUDED.stock;\n\n";
}

// 2. Invoices
if (!empty($invoices)) {
    $sql .= "INSERT INTO public.invoices (id, invoice_no, invoice_date, sales_id, sales_name, customer_id, customer_name, total_dus, total_bonus, total_amount, is_return, is_cash, notes, items) VALUES \n";
    $invRows = [];
    foreach ($invoices as $inv) {
        $id = (int)$inv['id'];
        $invNo = pg_escape_string_val($inv['invoice_no']);
        $invDate = pg_escape_string_val($inv['invoice_date']);
        $salesId = isset($inv['sales_id']) ? (int)$inv['sales_id'] : 'NULL';
        $salesName = pg_escape_string_val($inv['sales_name'] ?? '');
        $custId = isset($inv['customer_id']) ? (int)$inv['customer_id'] : 'NULL';
        $custName = pg_escape_string_val($inv['customer_name'] ?? '');
        $dus = (int)($inv['total_dus'] ?? 0);
        $bonus = (int)($inv['total_bonus'] ?? 0);
        $amount = (float)($inv['total_amount'] ?? 0);
        $isReturn = (int)($inv['is_return'] ?? 0);
        $isCash = (int)($inv['is_cash'] ?? 0);
        $notes = pg_escape_string_val($inv['notes'] ?? '');
        $itemsJson = pg_escape_string_val(json_encode($inv['items'] ?? [], JSON_UNESCAPED_UNICODE));
        
        $invRows[] = "($id, '$invNo', '$invDate', $salesId, '$salesName', $custId, '$custName', $dus, $bonus, $amount, $isReturn, $isCash, '$notes', '$itemsJson'::jsonb)";
    }
    $sql .= implode(",\n", $invRows);
    $sql .= "\nON CONFLICT (id) DO UPDATE SET invoice_no = EXCLUDED.invoice_no, invoice_date = EXCLUDED.invoice_date, sales_id = EXCLUDED.sales_id, sales_name = EXCLUDED.sales_name, customer_id = EXCLUDED.customer_id, customer_name = EXCLUDED.customer_name, total_dus = EXCLUDED.total_dus, total_bonus = EXCLUDED.total_bonus, total_amount = EXCLUDED.total_amount, is_return = EXCLUDED.is_return, is_cash = EXCLUDED.is_cash, notes = EXCLUDED.notes, items = EXCLUDED.items;\n";
}

function pg_escape_string_val($str) {
    return str_replace("'", "''", (string)$str);
}

file_put_contents(__DIR__ . '/seed.sql', $sql);
echo "Generated seed.sql successfully. Size: " . strlen($sql) . " bytes\n";

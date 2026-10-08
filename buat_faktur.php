<?php
// buat_faktur.php - Form Buat Faktur Penjualan & Print View Connected to Custom Faktur (Auto Today Date)
$pageTitle = "Buat Faktur Penjualan";

require_once __DIR__ . '/config/db.php';

$salesList    = loadData('sales.json');
$customerList = loadData('customers.json');
usort($customerList, function($a, $b) {
    return strcasecmp($a['name'] ?? '', $b['name'] ?? '');
});
$itemList     = loadData('items.json');
$settings     = getInvoiceSettings();

// Determine Default Date (Auto Today vs Manual Date)
$defaultInvoiceDate = !empty($settings['auto_today_date']) ? date('Y-m-d') : ($settings['default_date'] ?? date('Y-m-d'));

$salesAlertMessage = '';
$customerAlertMessage = '';

$selectedSalesId = isset($_POST['sales_id']) ? $_POST['sales_id'] : null;
$selectedCustomerId = isset($_POST['customer_id']) ? $_POST['customer_id'] : null;

// Handle Add New Sales via inline form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_sales']) && $_POST['action_sales'] === 'add_sales') {
    $newSalesName = trim($_POST['new_sales_name'] ?? '');
    if (!empty($newSalesName)) {
        $newId = count($salesList) > 0 ? max(array_column($salesList, 'id')) + 1 : 1;
        $code = str_pad($newId, 8, '0', STR_PAD_LEFT);
        $salesList[] = [
            'id' => $newId,
            'code' => $code,
            'name' => $newSalesName,
            'buyer_count' => 0,
            'debt' => 0,
            'limit' => 100000000,
            'due_days' => 0,
            'phone' => null
        ];
        saveData('sales.json', $salesList);
        $salesAlertMessage = "Sukses menambah sales baru!";
        $selectedSalesId = $newId;
    }
}

// Handle Add New Customer via inline form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_customer']) && $_POST['action_customer'] === 'add_customer') {
    $custName    = trim($_POST['new_customer_name'] ?? '');
    $custAddress = trim($_POST['new_customer_address'] ?? '');
    $custPhone   = trim($_POST['new_customer_phone'] ?? '');

    if (!empty($custName)) {
        $newId = count($customerList) > 0 ? max(array_column($customerList, 'id')) + 1 : 1;
        $customerList[] = [
            'id' => $newId,
            'code' => 'CUST-' . str_pad($newId, 3, '0', STR_PAD_LEFT),
            'name' => $custName,
            'address' => $custAddress ?: '-',
            'phone' => $custPhone ?: '0',
            'ktp' => '-'
        ];
        saveData('customers.json', $customerList);
        $customerAlertMessage = "Sukses menambah customer baru!";
        $selectedCustomerId = $newId;
    }
}

$isRenderInvoice = false;
$backUrl = 'buat_faktur.php';

// Handle Printable PDF/Nota Faktur Generation & Saving Custom Prices Permanently
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_invoice') {
    $isEditId    = isset($_POST['edit_id']) ? (int)$_POST['edit_id'] : 0;
    $invoiceDate = $_POST['invoice_date'] ?? $defaultInvoiceDate;
    $salesId     = $_POST['sales_id'] ?? 1;
    $customerId  = $_POST['customer_id'] ?? 1;
    $isCash      = isset($_POST['is_cash']) ? 1 : 0;
    
    // Find Sales Info
    $salesName = '';
    foreach ($salesList as $s) {
        if ((string)$s['id'] === (string)$salesId) { 
            $salesName = $s['name']; 
            break; 
        }
    }
    if (empty($salesName)) {
        $salesName = isset($salesList[0]['name']) ? $salesList[0]['name'] : 'Office';
    }
    
    // Find Customer Info
    $customerName = '';
    foreach ($customerList as $c) {
        if ((string)$c['id'] === (string)$customerId) { 
            $customerName = $c['name']; 
            break; 
        }
    }
    if (empty($customerName)) {
        $customerName = isset($customerList[0]['name']) ? $customerList[0]['name'] : 'Customer';
    }

    $quantities = $_POST['qty'] ?? [];
    $bonuses    = $_POST['bonus'] ?? [];
    $discounts  = $_POST['potongan'] ?? [];
    $prices     = $_POST['harga'] ?? [];

    $invoiceItems = [];
    $totalDus = 0;
    $totalBonus = 0;
    $grandTotal = 0;

    // 1. Update master item prices in items.json so entered prices persist!
    $itemsUpdated = false;
    foreach ($itemList as &$it) {
        $id = $it['id'];
        if (isset($prices[$id]) && $prices[$id] !== '' && (float)$prices[$id] >= 0) {
            $newPrice = (float)$prices[$id];
            if ($it['sell_price'] != $newPrice) {
                $it['sell_price'] = $newPrice;
                $itemsUpdated = true;
            }
        }
    }
    if ($itemsUpdated) {
        saveData('items.json', $itemList);
    }

    // 2. Process invoice line items
    foreach ($itemList as $it) {
        $id = $it['id'];
        $q = isset($quantities[$id]) && $quantities[$id] !== '' ? (int)$quantities[$id] : 0;
        $b = isset($bonuses[$id]) && $bonuses[$id] !== '' ? (int)$bonuses[$id] : 0;
        $p = isset($discounts[$id]) && $discounts[$id] !== '' ? (float)$discounts[$id] : 0;
        $h = isset($prices[$id]) && $prices[$id] !== '' ? (float)$prices[$id] : (float)$it['sell_price'];

        if ($q > 0) {
            $subtotal = ($q * $h) - $p;
            $invoiceItems[] = [
                'item_name' => $it['name'],
                'qty' => $q,
                'bonus' => $b,
                'discount' => $p,
                'price' => $h,
                'subtotal' => $subtotal
            ];
            $totalDus += $q;
            $totalBonus += $b;
            $grandTotal += $subtotal;
        }
    }

    // Save to invoices.json
    $invoices = loadData('invoices.json');
    if ($isEditId > 0) {
        $existingIdx = -1;
        foreach ($invoices as $i => $inv) {
            if (isset($inv['id']) && (int)$inv['id'] === $isEditId) {
                $existingIdx = $i;
                break;
            }
        }
        if ($existingIdx >= 0) {
            $invoiceNo = $invoices[$existingIdx]['invoice_no']; // keep old NO
            $invoices[$existingIdx]['invoice_date'] = $invoiceDate;
            $invoices[$existingIdx]['sales_id'] = (int)$salesId;
            $invoices[$existingIdx]['sales_name'] = $salesName;
            $invoices[$existingIdx]['customer_id'] = (int)$customerId;
            $invoices[$existingIdx]['customer_name'] = $customerName;
            $invoices[$existingIdx]['total_dus'] = (int)$totalDus;
            $invoices[$existingIdx]['total_bonus'] = (int)$totalBonus;
            $invoices[$existingIdx]['total_amount'] = (float)$grandTotal;
            $invoices[$existingIdx]['paid_amount'] = $isCash ? (float)$grandTotal : 0.0;
            $invoices[$existingIdx]['status'] = $isCash ? 'LUNAS' : 'BELUM LUNAS';
            $invoices[$existingIdx]['is_cash'] = (int)$isCash;
            $invoices[$existingIdx]['items'] = $invoiceItems;
            
            saveData('invoices.json', $invoices);
            header("Location: buat_faktur.php?view_id=" . $isEditId . "&from_created=1");
            exit;
        }
    }

    // NEW INVOICE Logic
    $invoiceNo = getNextInvoiceNumber();
    $paymentLabel = $isCash ? 'CASH' : htmlspecialchars($settings['default_payment'] ?? 'CASH');

    $maxId = 0;
    foreach ($invoices as $inv) {
        if (isset($inv['id']) && (int)$inv['id'] > $maxId) {
            $maxId = (int)$inv['id'];
        }
    }
    $newInvoice = [
        'id'            => $maxId + 1,
        'invoice_no'    => $invoiceNo,
        'invoice_date'  => $invoiceDate,
        'sales_id'      => (int)$salesId,
        'sales_name'    => $salesName,
        'customer_id'   => (int)$customerId,
        'customer_name' => $customerName,
        'total_dus'     => (int)$totalDus,
        'total_bonus'   => (int)$totalBonus,
        'total_amount'  => (float)$grandTotal,
        'paid_amount'   => $isCash ? (float)$grandTotal : 0.0,
        'status'        => $isCash ? 'LUNAS' : 'BELUM LUNAS',
        'is_return'     => 0,
        'is_cash'       => (int)$isCash,
        'notes'         => 'Faktur Penjualan',
        'items'         => $invoiceItems
    ];
    array_unshift($invoices, $newInvoice);
    saveData('invoices.json', $invoices);

    // Deduct stock in items.json (ONLY ON NEW INVOICE TO PREVENT DOUBLE COUNTING)
    $items = loadData('items.json');
    $stockChanged = false;
    foreach ($items as &$it) {
        $id = $it['id'];
        $q = isset($quantities[$id]) && $quantities[$id] !== '' ? (int)$quantities[$id] : 0;
        $b = isset($bonuses[$id]) && $bonuses[$id] !== '' ? (int)$bonuses[$id] : 0;
        if (($q + $b) > 0) {
            $it['stock'] = max(0, (int)($it['stock'] ?? 0) - ($q + $b));
            $stockChanged = true;
        }
    }
    if ($stockChanged) {
        saveData('items.json', $items);
    }

    // Record OUT movements in movements.json (ONLY ON NEW)
    $movements = loadData('movements.json');
    $maxMovId = 0;
    foreach ($movements as $m) {
        if (isset($m['id']) && (int)$m['id'] > $maxMovId) {
            $maxMovId = (int)$m['id'];
        }
    }
    foreach ($invoiceItems as $itRow) {
        $totalQty = (int)$itRow['qty'] + (int)$itRow['bonus'];
        if ($totalQty > 0) {
            $maxMovId++;
            $movements[] = [
                'id'            => $maxMovId,
                'movement_date' => $invoiceDate,
                'type'          => 'OUT',
                'item_name'     => $itRow['item_name'],
                'qty'           => $totalQty,
                'ref_no'        => $invoiceNo,
                'sales_name'    => $salesName,
                'customer_name' => $customerName,
                'notes'         => 'Faktur Penjualan'
            ];
        }
    }
    saveData('movements.json', $movements);

    // Redirect to GET view_id (Post-Redirect-Get pattern) to prevent ERR_CACHE_MISS / Confirm Form Resubmission
    header("Location: buat_faktur.php?view_id=" . $newInvoice['id'] . "&from_created=1");
    exit;

} elseif (isset($_GET['view_id'])) {
    $viewId = (int)$_GET['view_id'];
    $fromCreated = isset($_GET['from_created']) && $_GET['from_created'] == '1';
    $allInvoices = loadData('invoices.json');
    $found = null;
    foreach ($allInvoices as $inv) {
        if (isset($inv['id']) && (int)$inv['id'] === $viewId) {
            $found = $inv;
            break;
        }
    }
    if ($found) {
        $invoiceNo    = $found['invoice_no'];
        $invoiceDate  = $found['invoice_date'];
        $salesName    = $found['sales_name'] ?? 'Office';
        $customerName = $found['customer_name'] ?? 'Customer';
        $isCash       = $found['is_cash'] ?? 1;
        $paymentLabel = $isCash ? 'CASH' : htmlspecialchars($settings['default_payment'] ?? 'CASH');
        $invoiceItems = !empty($found['items']) ? $found['items'] : [];
        $totalDus     = $found['total_dus'] ?? 0;
        $totalBonus   = $found['total_bonus'] ?? 0;
        $grandTotal   = $found['total_amount'] ?? 0;

        $isRenderInvoice = true;
        
        if ($fromCreated) {
            $backUrl = 'buat_faktur.php';
        } else {
            $backParams = $_GET;
            unset($backParams['view_id'], $backParams['from_created']);
            if (!empty($backParams)) {
                $backUrl = 'index.php?' . http_build_query($backParams);
            } else {
                $backUrl = 'index.php';
            }
        }
    }
}

if ($isRenderInvoice) {

    // Render Exact Invoice View Connected to Custom Faktur Settings
    $printFontWeight = htmlspecialchars($settings['print_font_weight'] ?? 'bold');
    $printFontSize   = htmlspecialchars($settings['print_font_size'] ?? '13px');
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <title><?= htmlspecialchars($settings['company_name']) ?> - <?= $invoiceNo ?></title>
        <style>
            body {
                font-family: 'Courier New', Courier, monospace;
                font-size: <?= $printFontSize ?>;
                color: #000;
                background: #fff;
                margin: 0;
                padding: 20px 30px;
            }

            /* Action Buttons Bar matching green theme */
            .action-bar {
                display: flex;
                gap: 12px;
                align-items: center;
                margin-bottom: 20px;
                padding-bottom: 15px;
                border-bottom: 1px solid #eee;
            }

            .btn-green {
                background-color: #006600;
                color: #ffffff;
                border: 1px solid #004d00;
                padding: 8px 18px;
                font-size: 13px;
                font-weight: bold;
                font-family: Arial, Helvetica, sans-serif;
                border-radius: 4px;
                cursor: pointer;
                text-decoration: none;
                display: inline-flex;
                align-items: center;
                gap: 6px;
                text-transform: uppercase;
                transition: background 0.15s ease, box-shadow 0.15s ease;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }

            .btn-green:hover {
                background-color: #004d00;
                box-shadow: 0 3px 6px rgba(0,0,0,0.15);
            }

            /* Title Header Section with Store Name, Address & Phone */
            .invoice-title-box {
                text-align: center;
                margin-bottom: 12px;
                padding-bottom: 8px;
                border-bottom: 2px solid #000;
            }
            .invoice-title-box h1 {
                margin: 0 0 4px 0;
                font-size: 20px;
                font-weight: bold;
                text-transform: uppercase;
                letter-spacing: 1px;
            }
            .invoice-title-box .store-details {
                font-size: 13px;
                font-weight: bold;
            }

            /* Info Header Grid matching Screenshot */
            .info-grid {
                display: flex;
                justify-content: space-between;
                margin-bottom: 15px;
                font-weight: bold;
                font-size: 13px;
                line-height: 1.5;
            }

            /* Table Layout matching Screenshot */
            .invoice-table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 20px;
            }
            .invoice-table th, .invoice-table td {
                border: 1px solid #000;
                padding: 5px 8px;
                font-size: <?= $printFontSize ?>;
                font-weight: <?= $printFontWeight ?> !important;
            }
            .invoice-table th {
                font-weight: <?= $printFontWeight ?>;
                text-align: left;
                background: #fff;
            }
            .text-center { text-align: center !important; }
            .text-right { text-align: right !important; }

            /* Signatures Section matching Screenshot */
            .signatures-grid {
                display: flex;
                justify-content: space-between;
                margin-top: 40px;
                font-weight: bold;
                font-size: 13px;
            }
            .signature-box {
                text-align: center;
                min-width: 200px;
            }
            .signature-space {
                height: 60px;
            }

            @media print {
                .no-print { display: none !important; }
                body { padding: 0; }
                * { font-weight: <?= $printFontWeight ?> !important; font-size: <?= $printFontSize ?> !important; }
            }
        </style>
    </head>
    <body>
        <!-- Top Action Bar (Hidden during Print) -->
        <div class="action-bar no-print">
            <a href="<?= htmlspecialchars($backUrl) ?>" class="btn-green">
                &laquo; KEMBALI
            </a>
            <button onclick="window.print()" class="btn-green">
                🖨️ CETAK / SAVE PDF
            </button>
        </div>

        <!-- Title Header with Store Name, Address & Phone -->
        <div class="invoice-title-box">
            <h1><?= htmlspecialchars($settings['company_name']) ?></h1>
            <div class="store-details">
                <?= htmlspecialchars($settings['company_address']) ?> &nbsp;|&nbsp; <?= htmlspecialchars($settings['company_phone']) ?>
            </div>
        </div>

        <!-- Info Grid -->
        <div class="info-grid">
            <div>
                <div>CUSTOMER: <?= htmlspecialchars($customerName) ?></div>
                <div>TANGGAL: <?= date('d/m/Y', strtotime($invoiceDate)) ?></div>
            </div>
            <div style="text-align: right;">
                <div>NO: <?= htmlspecialchars($invoiceNo) ?></div>
                <div>PEMBAYARAN: <?= $paymentLabel ?></div>
            </div>
        </div>

        <!-- Main Invoice Table -->
        <table class="invoice-table">
            <thead>
                <tr>
                    <th style="width: 35px;" class="text-left">NO</th>
                    <th>NAMA ITEM / BARANG</th>
                    <th style="width: 90px;" class="text-right">QTY (DUS)</th>
                    <th style="width: 110px;" class="text-right">HARGA (RP)</th>
                    <th style="width: 130px;" class="text-right">SUBTOTAL (RP)</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $totalRetur = 0;
                $netGrandTotal = 0.0;
                if (empty($invoiceItems)): 
                ?>
                    <tr><td colspan="5" class="text-center">Tidak ada item dipilih.</td></tr>
                <?php else: ?>
                    <?php foreach ($invoiceItems as $idx => $item): 
                        $qty        = (int)($item['qty'] ?? 0);
                        $itemRetur  = (int)($item['retur'] ?? 0);
                        $price      = (float)($item['price'] ?? 0);
                        $discount   = (float)($item['discount'] ?? 0);

                        $netQty     = max(0, $qty - $itemRetur);
                        $itemSubtotal = ($netQty * $price) - $discount;
                        if ($itemSubtotal < 0) $itemSubtotal = 0;

                        $totalRetur    += $itemRetur;
                        $netGrandTotal += $itemSubtotal;
                    ?>
                        <tr>
                            <td><?= $idx + 1 ?></td>
                            <td><?= htmlspecialchars($item['item_name']) ?></td>
                            <td class="text-right"><?= number_format($qty, 0, ',', '.') ?></td>
                            <td class="text-right"><?= number_format($price, 0, ',', '.') ?></td>
                            <td class="text-right"><?= number_format($itemSubtotal, 0, ',', '.') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr style="font-weight: bold;">
                    <td colspan="2">TOTAL DUS</td>
                    <td class="text-right"><?= number_format($totalDus, 0, ',', '.') ?></td>
                    <td class="text-right">TOTAL PENJUALAN:</td>
                    <td class="text-right">Rp <?= number_format($netGrandTotal, 2, ',', '.') ?></td>
                </tr>
            </tfoot>
        </table>

        <!-- Signatures Section Dihilangkan sesuai request -->

        <script>
            window.onload = function() {
                setTimeout(function(){ window.print(); }, 500);
            };
        </script>
    </body>
    </html>
    <?php
    exit;
}

// --- Form Rendering variables for EDIT mode ---
$editInvoice = null;
if (isset($_GET['edit_id'])) {
    $editId = (int)$_GET['edit_id'];
    $allInvs = loadData('invoices.json');
    foreach ($allInvs as $inv) {
        if (isset($inv['id']) && (int)$inv['id'] === $editId) {
            $editInvoice = $inv;
            break;
        }
    }
    if ($editInvoice) {
        $defaultInvoiceDate = $editInvoice['invoice_date'];
        $selectedSalesId = $editInvoice['sales_id'] ?? null;
        $selectedCustomerId = $editInvoice['customer_id'] ?? null;
    }
}
$isCashChecked = ($editInvoice && !empty($editInvoice['is_cash'])) ? 'checked' : '';


require_once __DIR__ . '/includes/header.php';
?>

<h1 class="page-title">Buat Faktur Penjualan</h1>

<form method="POST" action="buat_faktur.php" id="fakturForm">
    <?php if ($editInvoice): ?>
        <input type="hidden" name="edit_id" value="<?= $editInvoice['id'] ?>">
        <div style="background: #ffc107; padding: 10px; margin-bottom: 15px; border-radius: 4px; font-weight: bold; color: #000;">
            ⚠️ ANDA SEDANG DALAM MODE EDIT FAKTUR NO: <?= htmlspecialchars($editInvoice['invoice_no']) ?>
        </div>
    <?php endif; ?>
    
    <!-- Top Filter Header Section matching Screenshot -->
    <div style="margin-bottom: 12px; font-size: 0.95rem;">
        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
            <label style="width: 80px; font-weight: bold;">Tanggal</label>
            <input type="date" name="invoice_date" value="<?= htmlspecialchars($defaultInvoiceDate) ?>" style="width: 140px;" required>
        </div>

        <!-- Sales Row -->
        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
            <label style="width: 80px; font-weight: bold;">Sales</label>
            <select name="sales_id" style="width: 180px;">
                <option value="" <?= ($selectedSalesId === null || $selectedSalesId === '') ? 'selected' : '' ?>>Pilih sales</option>
                <?php foreach ($salesList as $s): ?>
                    <?php 
                        $isSelected = ($selectedSalesId !== null && (string)$selectedSalesId !== '' && (string)$s['id'] === (string)$selectedSalesId);
                    ?>
                    <option value="<?= $s['id'] ?>" <?= $isSelected ? 'selected' : '' ?>>
                        <?= htmlspecialchars($s['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <input type="text" name="new_sales_name" id="new_sales_name" placeholder="Nama sales baru" style="width: 160px;">
            <button type="submit" name="action_sales" value="add_sales" formaction="buat_faktur.php">TAMBAH</button>
        </div>

        <!-- Customer Row -->
        <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
            <label style="width: 80px; font-weight: bold;">Customer</label>
            <select name="customer_id" style="width: 250px;">
                <?php foreach ($customerList as $c): ?>
                    <?php 
                        $isCustSelected = false;
                        if ($selectedCustomerId !== null) {
                            $isCustSelected = ((string)$c['id'] === (string)$selectedCustomerId);
                        }
                    ?>
                    <option value="<?= $c['id'] ?>" <?= $isCustSelected ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <input type="text" name="new_customer_name" placeholder="Nama customer baru" style="width: 160px;">
            <input type="text" name="new_customer_address" placeholder="Alamat customer baru" style="width: 180px;">
            <input type="text" name="new_customer_phone" placeholder="Telepon customer baru" style="width: 140px;">
            <button type="submit" name="action_customer" value="add_customer" formaction="buat_faktur.php">TAMBAH</button>
        </div>
    </div>

<?php if (!empty($salesAlertMessage)): ?>
    <script>
        alert("<?= htmlspecialchars($salesAlertMessage, ENT_QUOTES) ?>");
    </script>
<?php endif; ?>
<?php if (!empty($customerAlertMessage)): ?>
    <script>
        alert("<?= htmlspecialchars($customerAlertMessage, ENT_QUOTES) ?>");
    </script>
<?php endif; ?>

    <!-- Action Buttons Row matching Screenshot -->
    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 12px;">
        <label style="width: 80px; font-weight: bold;">Item</label>
        <button type="button" id="btnSembunyi" onclick="sembunyiItem()" class="btn btn-toggle">SEMBUNYI</button>
        <button type="button" id="btnTampilkan" onclick="tampilkanItem()" class="btn btn-toggle">TAMPILKAN</button>
        <button type="button" id="btnTampilkanSemua" onclick="resetSemuaItem()" class="btn btn-toggle active">TAMPILKAN SEMUA</button>
        <button type="submit" name="action" value="submit_invoice" class="btn" style="background: <?= $editInvoice ? '#ffc107' : '#e1e1e1' ?>; color: #000;">
            <?= $editInvoice ? 'SIMPAN PERUBAHAN FAKTUR' : 'BUAT FAKTUR' ?>
        </button>
        <label style="display: flex; align-items: center; gap: 4px; font-weight: bold; margin-left: 10px;">
            <input type="checkbox" name="is_cash" value="1" <?= $isCashChecked ?>> Cash
        </label>
    </div>

    <!-- Item List Grid (Harga Tersimpan & Dapat Diedit) -->
    <table class="item-grid-table" id="itemTable">
        <tbody>
            <?php foreach ($itemList as $idx => $it): ?>
                <?php 
                    $rowNum = $idx + 1;
                    $isGreen = ($rowNum % 2 == 0) || (in_row_green_list($rowNum)); 
                    $priceVal = (float)$it['sell_price'] > 0 ? (int)$it['sell_price'] : 0;
                    
                    $qVal = '';
                    $bVal = 0;
                    $pVal = 0;
                    if ($editInvoice && !empty($editInvoice['items'])) {
                        foreach ($editInvoice['items'] as $ei) {
                            if ($ei['item_name'] === $it['name']) {
                                $qVal = $ei['qty'] > 0 ? $ei['qty'] : '';
                                $bVal = $ei['bonus'] ?? 0;
                                $pVal = $ei['discount'] ?? 0;
                                $priceVal = $ei['price'] ?? $priceVal;
                                break;
                            }
                        }
                    }
                ?>
                <tr class="item-row <?= ($rowNum % 2 == 0 ? 'row-green-bg' : '') ?> <?= ($qVal !== '') ? 'has-value' : '' ?>" data-item-id="<?= $it['id'] ?>">
                    <td style="width: 30px; font-weight: bold; text-align: right; padding-right: 8px;"><?= $rowNum ?></td>
                    <td style="width: 160px; font-weight: bold; font-size: 0.9rem;"><?= htmlspecialchars($it['name']) ?></td>
                    
                    <td style="width: 135px; white-space: nowrap;">
                        Quantity <input type="number" name="qty[<?= $it['id'] ?>]" class="qty-input <?= ($qVal !== '') ? 'filled' : '' ?>" value="<?= $qVal ?>" placeholder="<?= isset($it['stock']) ? (int)$it['stock'] : 0 ?>" style="width: 60px;" min="0" oninput="highlightFilled(this)">
                    </td>
                    
                    <td style="width: 115px; white-space: nowrap;">
                        Bonus <input type="number" name="bonus[<?= $it['id'] ?>]" class="bonus-input" value="<?= $bVal ?>" style="width: 50px;" min="0">
                    </td>
                    
                    <td style="width: 150px; white-space: nowrap;">
                        Potongan <input type="number" name="potongan[<?= $it['id'] ?>]" class="potongan-input" value="<?= $pVal ?>" style="width: 60px;" min="0">
                    </td>
                    
                    <td style="white-space: nowrap;">
                        Harga <input type="number" name="harga[<?= $it['id'] ?>]" class="harga-input" value="<?= $priceVal ?>" style="width: 80px;" min="0">
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</form>

<?php
function in_row_green_list($num) {
    $greenRows = [6,7,8,9,10,12,13,14,15,16,18,20,22,23,24,25,26,28,30,34,36,37,40,42,44,46,48,50];
    return in_array($num, $greenRows);
}
?>

<script>
function setBtnActive(activeId) {
    document.querySelectorAll('.btn-toggle').forEach(btn => {
        btn.classList.remove('active');
    });
    if (activeId) {
        const el = document.getElementById(activeId);
        if (el) el.classList.add('active');
    }
}

function sembunyiItem() {
    const rows = document.querySelectorAll('.item-row');
    rows.forEach(row => {
        const qtyInput = row.querySelector('.qty-input');
        const qty = parseInt(qtyInput.value) || 0;
        if (qty <= 0) {
            row.style.display = 'none';
        } else {
            row.style.display = '';
        }
    });
    setBtnActive('btnSembunyi');
}

function tampilkanItem() {
    const rows = document.querySelectorAll('.item-row');
    let hasFilled = false;
    rows.forEach(row => {
        const qtyInput = row.querySelector('.qty-input');
        const qty = parseInt(qtyInput.value) || 0;
        if (qty > 0) {
            row.style.display = '';
            hasFilled = true;
        } else {
            row.style.display = 'none';
        }
    });
    if (!hasFilled) {
        alert('Belum ada item yang diisi Quantity-nya. Isi minimal 1 item terlebih dahulu!');
        resetSemuaItem();
    } else {
        setBtnActive('btnTampilkan');
    }
}

function resetSemuaItem() {
    const rows = document.querySelectorAll('.item-row');
    rows.forEach(row => {
        row.style.display = '';
    });
    setBtnActive('btnTampilkanSemua');
}

function highlightFilled(input) {
    const row = input.closest('tr');
    const qty = parseInt(input.value) || 0;
    if (qty > 0) {
        row.style.fontWeight = 'bold';
        row.style.outline = '1px solid #006600';
    } else {
        row.style.fontWeight = 'normal';
        row.style.outline = 'none';
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

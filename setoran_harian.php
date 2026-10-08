<?php
// setoran_harian.php - Daftar Setoran Harian (Based on Screenshot)
$pageTitle = "Daftar Setoran";
require_once __DIR__ . '/includes/header.php';

$allSales     = loadData('sales.json');
$allCustomers = loadData('customers.json');
$allInvoices  = loadData('invoices.json');

// Filter parameters (Default: Today's date)
$today = date('Y-m-d');
$startDate   = (isset($_GET['start_date']) && $_GET['start_date'] !== '') ? $_GET['start_date'] : $today;
$endDate     = (isset($_GET['end_date'])   && $_GET['end_date'] !== '')   ? $_GET['end_date']   : $today;
$salesId     = isset($_GET['sales_id'])    ? $_GET['sales_id']    : 'ALL';
$customerId  = isset($_GET['customer_id']) ? $_GET['customer_id'] : 'ALL';

$deposits    = loadData('daily_deposits.json');

// Map invoices by invoice_no for quick lookup
$invoiceMap = [];
foreach ($allInvoices as $inv) {
    if (!empty($inv['invoice_no'])) {
        $invoiceMap[$inv['invoice_no']] = $inv;
    }
}

$setoranList = [];

foreach ($deposits as $dep) {
    $notes = $dep['notes'] ?? '';
    $customerName = '-';
    $salesName = '-';
    $invoiceDate = '-';
    $invoiceNo = '-';
    $sId = '';
    $cId = '';
    
    if (preg_match('/Faktur\s+#([A-Za-z0-9-]+)/i', $notes, $matches)) {
        $invoiceNo = $matches[1];
        if (isset($invoiceMap[$invoiceNo])) {
            $inv = $invoiceMap[$invoiceNo];
            $customerName = $inv['customer_name'] ?? '-';
            $salesName    = $inv['sales_name'] ?? '-';
            $invoiceDate  = $inv['invoice_date'] ?? '-';
            $sId          = $inv['sales_id'] ?? '';
            $cId          = $inv['customer_id'] ?? '';
        } else {
            if (preg_match('/Hutang\s+(.+?)\s+\(Faktur/i', $notes, $cMatches)) {
                $customerName = trim($cMatches[1]);
            }
        }
    } else {
        $customerName = $notes; // For manual entries, just show notes in Customer column
    }

    $setoranList[] = [
        'customer_name' => $customerName,
        'sales_name'    => $salesName,
        'sales_id'      => $sId,
        'customer_id'   => $cId,
        'invoice_date'  => $invoiceDate,
        'payment_date'  => $dep['deposit_date'] ?? '',
        'invoice_no'    => $invoiceNo,
        'tunai'         => (float)($dep['cash_amount'] ?? 0),
        'transfer'      => (float)($dep['transfer_amount'] ?? 0),
        'selisih'       => 0, 
    ];
}

// Filter the setoranList
$filteredSetoran = array_filter($setoranList, function($s) use ($startDate, $endDate, $salesId, $customerId) {
    if (!empty($startDate) && $s['payment_date'] < $startDate) return false;
    if (!empty($endDate) && $s['payment_date'] > $endDate) return false;
    
    if ($salesId !== 'ALL' && $salesId !== '') {
        if ((string)$s['sales_id'] !== (string)$salesId) return false;
    }
    
    if ($customerId !== 'ALL' && $customerId !== '') {
        if ((string)$s['customer_id'] !== (string)$customerId) return false;
    }
    
    return true;
});

// Sort by payment date desc, then invoice_no desc
usort($filteredSetoran, function($a, $b) {
    $dateCmp = strcmp($b['payment_date'], $a['payment_date']);
    if ($dateCmp === 0) {
        return strcmp($b['invoice_no'], $a['invoice_no']);
    }
    return $dateCmp;
});

$sumTunai = 0;
$sumTransfer = 0;
$sumTotal = 0;
foreach ($filteredSetoran as $s) {
    $sumTunai += $s['tunai'];
    $sumTransfer += $s['transfer'];
    $sumTotal += ($s['tunai'] + $s['transfer']);
}
?>

<h1 class="page-title">Daftar Setoran</h1>

<!-- Filter Section -->
<div class="filter-card" style="margin-bottom: 15px;">
    <form method="GET" action="setoran_harian.php">
        <div class="filter-row" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
            <label style="font-weight: normal; font-size: 1.2rem;">Mulai tanggal</label>
            <input type="date" name="start_date" value="<?= htmlspecialchars($startDate) ?>">
            <label style="font-weight: normal; font-size: 1.2rem;">hingga tanggal</label>
            <input type="date" name="end_date" value="<?= htmlspecialchars($endDate) ?>">
            <button type="submit" class="btn">GANTI TANGGAL</button>
        </div>

        <div class="filter-row" style="margin-top: 10px; display: flex; gap: 10px; flex-wrap: wrap;">
            <select name="sales_id" style="min-width: 180px;" onchange="this.form.submit()">
                <option value="ALL" <?= ($salesId === 'ALL') ? 'selected' : '' ?>>Semua Sales</option>
                <?php foreach ($allSales as $s): ?>
                    <option value="<?= $s['id'] ?>" <?= ($salesId == $s['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($s['name'] ?? '') ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select name="customer_id" style="min-width: 180px;" onchange="this.form.submit()">
                <option value="ALL" <?= ($customerId === 'ALL') ? 'selected' : '' ?>>Semua Pembeli</option>
                <?php foreach ($allCustomers as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= ($customerId == $c['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c['name'] ?? '') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>
</div>

<div class="table-responsive">
    <table class="data-table">
        <thead>
            <tr>
                <th>No</th>
                <th>Customer</th>
                <th>Sales</th>
                <th>Tanggal</th>
                <th>Tgl Byr</th>
                <th>No faktur</th>
                <th style="text-align: right;">Tunai</th>
                <th style="text-align: right;">Transfer</th>
                <th style="text-align: right;">Total setoran</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($filteredSetoran)): ?>
                <tr>
                    <td colspan="10" style="text-align: center; color: #666; padding: 20px;">
                        Tidak ada data setoran harian pada kriteria ini.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach (array_values($filteredSetoran) as $idx => $s): ?>
                    <?php 
                        $totalSetoran = $s['tunai'] + $s['transfer']; 
                        $tgl = !empty($s['invoice_date']) ? date('Y-m-d', strtotime($s['invoice_date'])) : '-';
                        $tglByr = !empty($s['payment_date']) ? date('Y-m-d', strtotime($s['payment_date'])) : '-';
                        $rowClass = ($idx % 2 === 1) ? 'row-green' : '';
                    ?>
                    <tr class="<?= $rowClass ?>">
                        <td style="text-align: center;"><?= $idx + 1 ?></td>
                        <td><?= htmlspecialchars($s['customer_name'] ?? '') ?></td>
                        <td><?= htmlspecialchars($s['sales_name'] ?? '') ?></td>
                        <td style="text-align: center;"><?= $tgl ?></td>
                        <td style="text-align: center;"><?= $tglByr ?></td>
                        <td style="text-align: center; color: #006600; font-weight: bold;"><?= htmlspecialchars($s['invoice_no'] ?? '') ?></td>
                        <td style="text-align: right;"><?= number_format($s['tunai'], 2, ',', '.') ?></td>
                        <td style="text-align: right;"><?= number_format($s['transfer'], 2, ',', '.') ?></td>
                        <td style="text-align: right; font-weight: bold;"><?= number_format($totalSetoran, 2, ',', '.') ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            
            <tr class="summary-bar">
                <td colspan="6" style="text-align: center; font-weight: bold; font-size: 1.05rem;">TOTAL SETORAN</td>
                <td style="text-align: right; font-weight: bold; font-size: 1.05rem;"><?= number_format($sumTunai, 2, ',', '.') ?></td>
                <td style="text-align: right; font-weight: bold; font-size: 1.05rem;"><?= number_format($sumTransfer, 2, ',', '.') ?></td>
                <td style="text-align: right; font-weight: bold; font-size: 1.05rem;"><?= number_format($sumTotal, 2, ',', '.') ?></td>
            </tr>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

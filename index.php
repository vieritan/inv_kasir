<?php
// index.php - Halaman Utama Daftar Faktur Penjualan
$pageTitle = "Daftar Faktur";
require_once __DIR__ . '/includes/header.php';

// Load Data
$allSales    = loadData('sales.json');
$allInvoices = loadData('invoices.json');

// Handle Retur Action
if (isset($_GET['action']) && $_GET['action'] === 'toggle_retur' && isset($_GET['id'])) {
    $targetId = (int)$_GET['id'];
    $updated = false;
    foreach ($allInvoices as &$inv) {
        if (isset($inv['id']) && (int)$inv['id'] === $targetId) {
            $inv['is_return'] = empty($inv['is_return']) ? 1 : 0;
            $updated = true;
            break;
        }
    }
    if ($updated) {
        saveData('invoices.json', $allInvoices);
    }
    
    // Redirect preserving filter parameters
    $params = $_GET;
    unset($params['action'], $params['id']);
    $queryString = http_build_query($params);
    header("Location: index.php" . ($queryString ? "?$queryString" : ""));
    exit;
}

// Handle Delete Action
if (isset($_GET['action']) && $_GET['action'] === 'delete_invoice' && isset($_GET['id'])) {
    $delId = (int)$_GET['id'];
    deleteInvoicesFromStore([$delId]);
    
    // Redirect preserving filter parameters
    $params = $_GET;
    unset($params['action'], $params['id']);
    $queryString = http_build_query($params);
    header("Location: index.php" . ($queryString ? "?$queryString" : ""));
    exit;
}


// Filter parameters (Default: Always today's date)
$today = date('Y-m-d');
$startDate   = (isset($_GET['start_date']) && $_GET['start_date'] !== '') ? $_GET['start_date'] : $today;
$endDate     = (isset($_GET['end_date'])   && $_GET['end_date'] !== '')   ? $_GET['end_date']   : $today;
$salesId     = isset($_GET['sales_id'])    ? $_GET['sales_id']    : 'ALL';
$hanyaRetur  = isset($_GET['hanya_retur']) && $_GET['hanya_retur'] == '1';
$searchQuery = isset($_GET['search'])      ? trim($_GET['search']) : '';

// Find selected sales name if filtered
$selectedSalesName = '';
if ($salesId !== 'ALL' && $salesId !== '') {
    foreach ($allSales as $s) {
        if ((string)$s['id'] === (string)$salesId) {
            $selectedSalesName = strtolower(trim($s['name']));
            break;
        }
    }
}

// Filter Invoices
$invoices = array_filter($allInvoices, function($inv) use ($startDate, $endDate, $salesId, $selectedSalesName, $hanyaRetur, $searchQuery) {
    $invDate = $inv['invoice_date'];
    
    // Date filter
    if ($invDate < $startDate || $invDate > $endDate) {
        return false;
    }
    
    // Sales filter
    if ($salesId !== 'ALL' && $salesId !== '') {
        $invSalesId   = isset($inv['sales_id']) ? (string)$inv['sales_id'] : '';
        $invSalesName = strtolower(trim($inv['sales_name'] ?? ''));

        $matchId   = ($invSalesId !== '' && $invSalesId === (string)$salesId);
        $matchName = ($selectedSalesName !== '' && $invSalesName === $selectedSalesName);

        if (!$matchId && !$matchName) {
            return false;
        }

        if ($selectedSalesName !== '' && $invSalesName !== '' && $invSalesName !== $selectedSalesName) {
            return false;
        }
    }
    
    // Retur filter
    if ($hanyaRetur && empty($inv['is_return'])) {
        return false;
    }

    // Search query filter (Tanggal, No faktur, Sales, Customer)
    if ($searchQuery !== '') {
        $q = strtolower($searchQuery);
        $formattedDate = date('d/m/Y', strtotime($inv['invoice_date']));
        $rawDate       = $inv['invoice_date'];
        $invNo         = strtolower($inv['invoice_no'] ?? '');
        $salesName     = strtolower($inv['sales_name'] ?? '');
        $customerName  = strtolower($inv['customer_name'] ?? '');

        $matchDate = (strpos($formattedDate, $q) !== false || strpos($rawDate, $q) !== false);
        $matchNo   = (strpos($invNo, $q) !== false);
        $matchSales= (strpos($salesName, $q) !== false);
        $matchCust = (strpos($customerName, $q) !== false);

        if (!$matchDate && !$matchNo && !$matchSales && !$matchCust) {
            return false;
        }
    }
    
    return true;
});

// Sort descending
usort($invoices, function($a, $b) {
    return $b['id'] <=> $a['id'];
});

// Helper to calculate net invoice values (Dus, Bonus, Amount) after retur
function getInvoiceNetValues($inv) {
    $origDus    = (int)($inv['total_dus'] ?? 0);
    $origBonus  = (int)($inv['total_bonus'] ?? 0);
    $origAmount = (float)($inv['total_amount'] ?? 0);

    $returDus   = 0;
    $returBonus = 0;
    $returAmt   = 0.0;

    if (!empty($inv['items']) && is_array($inv['items'])) {
        foreach ($inv['items'] as $it) {
            $rQty   = (int)($it['retur'] ?? 0);
            $rBonus = (int)($it['retur_bonus'] ?? 0);
            $price  = (float)($it['price'] ?? 0);

            $returDus   += $rQty;
            $returBonus += $rBonus;
            $returAmt   += ($rQty * $price);
        }
    }
    if (isset($inv['total_retur_amount']) && (float)$inv['total_retur_amount'] > 0) {
        $returAmt = max($returAmt, (float)$inv['total_retur_amount']);
    }

    return [
        'dus'       => max(0, $origDus - $returDus),
        'bonus'     => max(0, $origBonus - $returBonus),
        'amount'    => max(0, $origAmount - $returAmt),
        'has_retur' => ($returDus > 0 || $returBonus > 0 || $returAmt > 0)
    ];
}

// Calculate totals
$totalDus     = 0;
$totalBonus   = 0;
$totalNominal = 0.0;

foreach ($invoices as $inv) {
    $net = getInvoiceNetValues($inv);
    $totalDus     += $net['dus'];
    $totalBonus   += $net['bonus'];
    $totalNominal += $net['amount'];
}
?>

<h1 class="page-title">Daftar Faktur</h1>

<div class="filter-card">
    <form method="GET" action="index.php">
        <!-- Date Row matching screenshot -->
        <div class="filter-row">
            <label>Mulai tanggal</label>
            <input type="date" name="start_date" value="<?= htmlspecialchars($startDate) ?>">
            <label>hingga tanggal</label>
            <input type="date" name="end_date" value="<?= htmlspecialchars($endDate) ?>">
            <button type="submit" class="btn">GANTI TANGGAL</button>
        </div>

        <!-- Sales Dropdown Row -->
        <div class="filter-row">
            <select name="sales_id" style="min-width: 250px;" onchange="this.form.submit()">
                <option value="ALL" <?= ($salesId === 'ALL') ? 'selected' : '' ?>>Semua sales</option>
                <?php foreach ($allSales as $s): ?>
                    <option value="<?= $s['id'] ?>" <?= ($salesId == $s['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($s['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Checkbox Row -->
        <div class="filter-row">
            <label style="display: flex; align-items: center; gap: 4px; cursor: pointer;">
                <input type="checkbox" name="hanya_retur" value="1" <?= $hanyaRetur ? 'checked' : '' ?> onchange="this.form.submit()">
                <span>Hanya retur</span>
            </label>
        </div>

        <!-- Search Input Row (Cari Tanggal, No Faktur, Sales) -->
        <div class="filter-row" style="margin-top: 8px; display: flex; align-items: center; gap: 8px;">
            <input type="text" name="search" id="searchInput" class="form-control" style="width: 280px; padding: 6px 10px;" value="<?= htmlspecialchars($searchQuery) ?>" placeholder="🔍 Cari Tanggal, No Faktur, Sales...">
            <button type="submit" class="btn" style="padding: 5px 14px;">CARI</button>
            <?php if ($searchQuery !== '' || $salesId !== 'ALL'): ?>
                <a href="index.php?start_date=<?= urlencode($startDate) ?>&end_date=<?= urlencode($endDate) ?>" class="btn" style="background: #666; color: #fff; text-decoration: none; padding: 5px 10px; font-size: 0.85rem; border-radius: 4px;">RESET SEARCH</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="table-responsive">
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 30px;">No</th>
                <th>Tanggal</th>
                <th>No faktur</th>
                <th>Sales</th>
                <th>Customer</th>
                <th style="text-align: right;">Dus</th>
                <th style="text-align: right;">Bonus</th>
                <th style="text-align: right;">Total</th>
                <th style="text-align: center; width: 210px;">Aksi</th>
            </tr>
        </thead>
        <tbody>
            <!-- Highlight Yellow Summary Bar matching Screenshot Image 2 & 3 -->
            <tr class="summary-bar">
                <td colspan="5" style="text-align: center; font-weight: bold;">TOTAL PENJUALAN</td>
                <td style="text-align: right; font-weight: bold;"><?= number_format($totalDus, 0, ',', '.') ?></td>
                <td style="text-align: right; font-weight: bold;"><?= number_format($totalBonus, 0, ',', '.') ?></td>
                <td style="text-align: right; font-weight: bold;"><?= number_format($totalNominal, 2, ',', '.') ?></td>
                <td></td>
            </tr>

            <?php if (empty($invoices)): ?>
                <tr>
                    <td colspan="9" style="text-align: center; color: #666; padding: 20px;">
                        Tidak ada data faktur ditemukan.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach (array_values($invoices) as $idx => $inv): ?>
                    <?php $net = getInvoiceNetValues($inv); ?>
                    <tr class="<?= ($idx % 2 === 1) ? 'row-green' : '' ?>">
                        <td><?= $idx + 1 ?></td>
                        <td><?= date('d/m/Y', strtotime($inv['invoice_date'])) ?></td>
                        <td>
                            <a href="buat_faktur.php?<?= http_build_query(array_merge($_GET, ['start_date' => $startDate, 'end_date' => $endDate, 'view_id' => $inv['id']])) ?>" style="color: #006600; text-decoration: none; font-weight: bold;" title="Klik untuk lihat detail / cetak faktur">
                                <?= htmlspecialchars($inv['invoice_no']) ?>
                            </a>
                            <?php if (!empty($inv['is_return']) || $net['has_retur']): ?>
                                <span class="badge badge-danger" style="margin-left: 4px;">RETUR</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($inv['sales_name'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($inv['customer_name'] ?? '-') ?></td>
                        <td style="text-align: right; font-weight: bold;"><?= number_format($net['dus'], 0, ',', '.') ?></td>
                        <td style="text-align: right;"><?= number_format($net['bonus'], 0, ',', '.') ?></td>
                        <td style="text-align: right; font-weight: bold;">
                            Rp <?= number_format($net['amount'], 2, ',', '.') ?>
                        </td>
                        <td style="text-align: center; white-space: nowrap;">
                            <a href="buat_faktur.php?<?= http_build_query(array_merge($_GET, ['start_date' => $startDate, 'end_date' => $endDate, 'view_id' => $inv['id']])) ?>" class="btn-action-cetak" title="Cetak Faktur">CETAK</a>
                            <a href="buat_retur.php?<?= http_build_query(array_merge($_GET, ['start_date' => $startDate, 'end_date' => $endDate, 'id' => $inv['id']])) ?>" class="btn-action-retur <?= (!empty($inv['is_return']) || $net['has_retur']) ? 'active-retur' : '' ?>" title="Buat Retur Penjualan">RETUR</a>
                            <a href="index.php?<?= http_build_query(array_merge($_GET, ['action' => 'delete_invoice', 'id' => $inv['id']])) ?>" class="btn-action-delete" style="background-color: #dc3545; color: white; border-radius: 4px; padding: 4px 8px; font-weight: bold; font-size: 11px; text-decoration: none;" onclick="return confirm('Yakin ingin menghapus faktur ini secara permanen? Stok tidak otomatis kembali jika dihapus manual di sini.')" title="Hapus Faktur">HAPUS</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

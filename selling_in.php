<?php
// selling_in.php - Laporan Selling IN Sesuai Screenshot Image 4
$pageTitle = "Laporan Selling IN";
require_once __DIR__ . '/includes/header.php';

$allSuppliers = loadData('suppliers.json');
$allItems     = loadData('items.json');
$movements    = loadData('movements.json');

// Filter parameters (Default: Always today's date)
$today = date('Y-m-d');
$startDate      = (isset($_GET['start_date']) && $_GET['start_date'] !== '') ? $_GET['start_date'] : $today;
$endDate        = (isset($_GET['end_date'])   && $_GET['end_date'] !== '')   ? $_GET['end_date']   : $today;
$supplierFilter = isset($_GET['supplier'])   ? $_GET['supplier']   : 'SEMUA';
$itemFilter     = isset($_GET['item'])       ? $_GET['item']       : 'SEMUA';
$searchQuery    = isset($_GET['search'])     ? trim($_GET['search']) : '';

$rows = array_filter($movements, function($m) use ($startDate, $endDate, $supplierFilter, $itemFilter, $searchQuery) {
    if (($m['type'] ?? '') !== 'IN') {
        return false;
    }
    
    $mDate = $m['movement_date'] ?? '';
    if ($mDate < $startDate || $mDate > $endDate) {
        return false;
    }

    // Supplier filter
    if ($supplierFilter !== 'SEMUA' && $supplierFilter !== '') {
        if (($m['supplier_id'] ?? '') != $supplierFilter && ($m['supplier_name'] ?? '') != $supplierFilter) {
            return false;
        }
    }

    // Item filter
    if ($itemFilter !== 'SEMUA' && $itemFilter !== '') {
        if (($m['item_id'] ?? '') != $itemFilter && ($m['item_name'] ?? '') != $itemFilter) {
            return false;
        }
    }

    // Search query filter (Tanggal, No Faktur, Sales, Item, Notes)
    if ($searchQuery !== '') {
        $q = strtolower($searchQuery);
        $formattedDate = date('d/m/Y', strtotime($mDate));
        $refNo         = strtolower($m['ref_no'] ?? '');
        $salesName     = strtolower($m['sales_name'] ?? 'office');
        $itemName      = strtolower($m['item_name'] ?? '');
        $itemCode      = strtolower($m['item_code'] ?? '');
        $supplierName  = strtolower($m['supplier_name'] ?? '');
        $notes         = strtolower($m['notes'] ?? '');

        $matchDate     = (strpos($formattedDate, $q) !== false || strpos($mDate, $q) !== false);
        $matchNo       = (strpos($refNo, $q) !== false);
        $matchSales    = (strpos($salesName, $q) !== false);
        $matchItem     = (strpos($itemName, $q) !== false || strpos($itemCode, $q) !== false);
        $matchSupplier = (strpos($supplierName, $q) !== false);
        $matchNotes    = (strpos($notes, $q) !== false);

        if (!$matchDate && !$matchNo && !$matchSales && !$matchItem && !$matchSupplier && !$matchNotes) {
            return false;
        }
    }

    return true;
});

// Sort descending by ID or date
usort($rows, function($a, $b) {
    return ($b['id'] ?? 0) <=> ($a['id'] ?? 0);
});

$totalDus = 0;
foreach ($rows as $r) {
    $totalDus += (int)$r['qty'];
}
?>

<h1 class="page-title">Laporan Selling IN</h1>

<!-- Filter Section -->
<div class="filter-card" style="margin-bottom: 20px;">
    <form method="GET" action="selling_in.php">
        <div class="filter-row">
            <label>Mulai tanggal</label>
            <input type="date" name="start_date" value="<?= htmlspecialchars($startDate) ?>">
            <label>hingga tanggal</label>
            <input type="date" name="end_date" value="<?= htmlspecialchars($endDate) ?>">
            <select name="supplier" onchange="this.form.submit()">
                <option value="SEMUA" <?= ($supplierFilter === 'SEMUA') ? 'selected' : '' ?>>SEMUA SUPLIER</option>
                <?php foreach ($allSuppliers as $sup): ?>
                    <option value="<?= $sup['id'] ?>" <?= ($supplierFilter == $sup['id'] || $supplierFilter == $sup['name']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($sup['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="filter-row">
            <select name="item" onchange="this.form.submit()">
                <option value="SEMUA" <?= ($itemFilter === 'SEMUA') ? 'selected' : '' ?>>SEMUA ITEM</option>
                <?php foreach ($allItems as $it): ?>
                    <option value="<?= $it['id'] ?>" <?= ($itemFilter == $it['id'] || $itemFilter == $it['name']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($it['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn">OK</button>
        </div>

        <!-- Search Input Row (Cari Tanggal, No Faktur, Sales, Item) -->
        <div class="filter-row" style="margin-top: 8px; display: flex; align-items: center; gap: 8px;">
            <input type="text" name="search" id="searchInput" class="form-control" style="width: 280px; padding: 6px 10px;" value="<?= htmlspecialchars($searchQuery) ?>" placeholder="🔍 Cari Tanggal, No Faktur, Sales, Item...">
            <button type="submit" class="btn" style="padding: 5px 14px;">CARI</button>
            <?php if ($searchQuery !== '' || $supplierFilter !== 'SEMUA' || $itemFilter !== 'SEMUA'): ?>
                <a href="selling_in.php?start_date=<?= urlencode($startDate) ?>&end_date=<?= urlencode($endDate) ?>" class="btn" style="background: #666; color: #fff; text-decoration: none; padding: 5px 10px; font-size: 0.85rem; border-radius: 4px;">RESET SEARCH</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div style="margin-bottom: 15px; font-size: 1.05rem; font-weight: bold;">
    <div>Laporan Pembelian</div>
    <div>Tanggal <?= htmlspecialchars($startDate) ?> - <?= htmlspecialchars($endDate) ?></div>
</div>

<div class="table-responsive">
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 40px;">NO</th>
                <th>TANGGAL</th>
                <th>NO FAKTUR</th>
                <th>SALES</th>
                <th>ITEM</th>
                <th style="text-align: right;">DUS</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($rows)): ?>
                <tr>
                    <td colspan="5" style="font-weight: bold;">TOTAL</td>
                    <td style="text-align: right; font-weight: bold;"><?= number_format($totalDus, 0, ',', '.') ?></td>
                </tr>
            <?php else: ?>
                <?php foreach (array_values($rows) as $idx => $r): ?>
                    <tr class="<?= ($idx % 2 === 1) ? 'row-green' : '' ?>">
                        <td><?= $idx + 1 ?></td>
                        <td><?= date('d/m/Y', strtotime($r['movement_date'])) ?></td>
                        <td><?= htmlspecialchars($r['ref_no'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($r['sales_name'] ?? 'Office') ?></td>
                        <td><?= htmlspecialchars($r['item_name']) ?></td>
                        <td style="text-align: right;"><?= number_format($r['qty'], 0, ',', '.') ?></td>
                    </tr>
                <?php endforeach; ?>
                <tr class="summary-bar">
                    <td colspan="5" style="text-align: center;">TOTAL</td>
                    <td style="text-align: right;"><?= number_format($totalDus, 0, ',', '.') ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>


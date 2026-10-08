<?php
// selling_out.php - Laporan Penjualan Out per Item & per Dus (Unrolled Itemized Report)
$pageTitle = "Selling Out";
require_once __DIR__ . '/includes/header.php';

// Load Data
$allSales     = loadData('sales.json');
$allCustomers = loadData('customers.json');
$allItems     = loadData('items.json');
$allInvoices  = loadData('invoices.json');

// Filter parameters (Default: Always today's date)
$today = date('Y-m-d');
$startDate   = (isset($_GET['start_date']) && $_GET['start_date'] !== '') ? $_GET['start_date'] : $today;
$endDate     = (isset($_GET['end_date'])   && $_GET['end_date'] !== '')   ? $_GET['end_date']   : $today;
// Sales, Customer & Item filter parameters
$salesId     = isset($_GET['sales_id'])    ? $_GET['sales_id']    : 'ALL';
$customerId  = isset($_GET['customer_id']) ? $_GET['customer_id'] : 'ALL';
$itemId      = isset($_GET['item_id'])     ? $_GET['item_id']     : 'ALL';
$searchQuery = isset($_GET['search'])      ? trim($_GET['search'])  : '';

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

// Find selected customer name if filtered
$selectedCustomerName = '';
if ($customerId !== 'ALL' && $customerId !== '') {
    foreach ($allCustomers as $c) {
        if ((string)$c['id'] === (string)$customerId) {
            $selectedCustomerName = strtolower(trim($c['name']));
            break;
        }
    }
}

// Find selected item name if filtered
$selectedItemName = '';
if ($itemId !== 'ALL' && $itemId !== '') {
    foreach ($allItems as $it) {
        if ((string)$it['id'] === (string)$itemId) {
            $selectedItemName = strtolower(trim($it['name']));
            break;
        }
    }
}

// Unroll Invoice Items into Individual Itemized Rows
$unrolledRows = [];
$totalDus = 0;
$totalBonus = 0;
$totalRetur = 0;
$totalFinal = 0;

foreach ($allInvoices as $inv) {
    if (!empty($inv['is_return'])) {
        continue;
    }

    // Date filter
    if ($inv['invoice_date'] < $startDate || $inv['invoice_date'] > $endDate) {
        continue;
    }

    // Sales filter
    if ($salesId !== 'ALL' && $salesId !== '') {
        $invSalesId   = isset($inv['sales_id']) ? (string)$inv['sales_id'] : '';
        $invSalesName = strtolower(trim($inv['sales_name'] ?? ''));

        $matchId   = ($invSalesId !== '' && $invSalesId === (string)$salesId);
        $matchName = ($selectedSalesName !== '' && $invSalesName === $selectedSalesName);

        if (!$matchId && !$matchName) {
            continue;
        }
    }

    // Customer filter
    if ($customerId !== 'ALL' && $customerId !== '') {
        $invCustId   = isset($inv['customer_id']) ? (string)$inv['customer_id'] : '';
        $invCustName = strtolower(trim($inv['customer_name'] ?? ''));

        $matchId   = ($invCustId !== '' && $invCustId === (string)$customerId);
        $matchName = ($selectedCustomerName !== '' && $invCustName === $selectedCustomerName);

        if (!$matchId && !$matchName) {
            continue;
        }
    }

    // Unroll each item in invoice
    if (!empty($inv['items']) && is_array($inv['items'])) {
        foreach ($inv['items'] as $itRow) {
            $itemName = $itRow['item_name'] ?? '';
            $qty   = (int)($itRow['qty'] ?? 0);
            $bonus = (int)($itRow['bonus'] ?? 0);
            $retur = (int)($itRow['retur'] ?? 0);

            // Skip items with zero qty and bonus
            if ($qty <= 0 && $bonus <= 0) {
                continue;
            }

            // Item filter
            if ($selectedItemName !== '') {
                $iNameLower = strtolower(trim($itemName));
                if ($iNameLower !== $selectedItemName && strpos($iNameLower, $selectedItemName) === false) {
                    continue;
                }
            }

            // Search query filter (Tanggal, No faktur, Sales, Customer, Item)
            if ($searchQuery !== '') {
                $q = strtolower($searchQuery);
                $formattedDate  = date('d/m/Y', strtotime($inv['invoice_date']));
                $rawDate        = $inv['invoice_date'];
                $invNo          = strtolower($inv['invoice_no'] ?? '');
                $salesName      = strtolower($inv['sales_name'] ?? '');
                $customerName   = strtolower($inv['customer_name'] ?? '');
                $iNameLower     = strtolower($itemName);

                $matchDate  = (strpos($formattedDate, $q) !== false || strpos($rawDate, $q) !== false);
                $matchNo    = (strpos($invNo, $q) !== false);
                $matchSales = (strpos($salesName, $q) !== false);
                $matchCust  = (strpos($customerName, $q) !== false);
                $matchItem  = (strpos($iNameLower, $q) !== false);

                if (!$matchDate && !$matchNo && !$matchSales && !$matchCust && !$matchItem) {
                    continue;
                }
            }

            $final = $qty + $bonus - $retur;

            $unrolledRows[] = [
                'invoice_id'    => $inv['id'],
                'invoice_date'  => $inv['invoice_date'],
                'invoice_no'    => $inv['invoice_no'],
                'sales_name'    => $inv['sales_name'] ?? '-',
                'customer_name' => $inv['customer_name'] ?? '-',
                'item_name'     => $itemName,
                'dus'           => $qty,
                'bonus'         => $bonus,
                'retur'         => $retur,
                'final'         => $final
            ];

            $totalDus   += $qty;
            $totalBonus += $bonus;
            $totalRetur += $retur;
            $totalFinal += $final;
        }
    }
}

// Sort descending by Invoice Date & ID
usort($unrolledRows, function($a, $b) {
    if ($a['invoice_date'] === $b['invoice_date']) {
        return $b['invoice_id'] <=> $a['invoice_id'];
    }
    return strcmp($b['invoice_date'], $a['invoice_date']);
});
?>

<h1 class="page-title">Selling Out (Laporan Penjualan Barang Out)</h1>

<div class="filter-card">
    <form method="GET" action="selling_out.php">
        <!-- Date Row matching screenshot -->
        <div class="filter-row" style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
            <label style="font-weight: bold;">Mulai tanggal</label>
            <input type="date" name="start_date" value="<?= htmlspecialchars($startDate) ?>">
            <label style="font-weight: bold;">hingga tanggal</label>
            <input type="date" name="end_date" value="<?= htmlspecialchars($endDate) ?>">
        </div>

        <!-- Sales, Customer & Item Dropdown Row -->
        <div class="filter-row" style="display: flex; gap: 10px; flex-wrap: wrap; margin-top: 8px; align-items: center;">
            <select name="sales_id" style="min-width: 180px;">
                <option value="ALL" <?= ($salesId === 'ALL') ? 'selected' : '' ?>>SEMUA SALES</option>
                <?php foreach ($allSales as $s): ?>
                    <option value="<?= $s['id'] ?>" <?= ($salesId == $s['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($s['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select name="customer_id" style="min-width: 180px;">
                <option value="ALL" <?= ($customerId === 'ALL') ? 'selected' : '' ?>>SEMUA CUSTOMER</option>
                <?php foreach ($allCustomers as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= ($customerId == $c['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select name="item_id" style="min-width: 200px;">
                <option value="ALL" <?= ($itemId === 'ALL') ? 'selected' : '' ?>>SEMUA ITEM</option>
                <?php foreach ($allItems as $it): ?>
                    <option value="<?= $it['id'] ?>" <?= ($itemId == $it['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($it['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <button type="submit" class="btn" style="padding: 5px 16px; font-weight: bold;">OK</button>
        </div>

        <!-- Search Input Row -->
        <div class="filter-row" style="margin-top: 8px; display: flex; align-items: center; gap: 8px;">
            <input type="text" name="search" id="searchInput" class="form-control" style="width: 280px; padding: 6px 10px;" value="<?= htmlspecialchars($searchQuery) ?>" placeholder="🔍 Cari Tanggal, No Faktur, Sales, Item...">
            <button type="submit" class="btn" style="padding: 5px 14px;">CARI</button>
            <?php if ($searchQuery !== '' || $salesId !== 'ALL' || $customerId !== 'ALL' || $itemId !== 'ALL'): ?>
                <a href="selling_out.php?start_date=<?= urlencode($startDate) ?>&end_date=<?= urlencode($endDate) ?>" class="btn" style="background: #666; color: #fff; text-decoration: none; padding: 5px 10px; font-size: 0.85rem; border-radius: 4px;">RESET SEARCH</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div style="font-weight: bold; margin-bottom: 12px; font-size: 1rem; color: #333;">
    Laporan Penjualan per Sales<br>
    <span style="font-size: 0.9rem; font-weight: normal; color: #555;">Tanggal <?= htmlspecialchars($startDate) ?> - <?= htmlspecialchars($endDate) ?></span>
</div>

<div class="table-responsive">
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 40px;">NO</th>
                <th style="width: 110px;">TANGGAL</th>
                <th style="width: 140px;">NO FAKTUR</th>
                <th>SALES</th>
                <th>CUSTOMER</th>
                <th>SEMUA ITEM</th>
                <th style="text-align: right; width: 70px;">DUS</th>
                <th style="text-align: right; width: 70px;">BONUS</th>
                <th style="text-align: right; width: 70px;">RETUR</th>
                <th style="text-align: right; width: 70px;">FINAL</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($unrolledRows)): ?>
                <tr><td colspan="10" style="text-align: center; color: var(--text-muted); padding: 20px;">Tidak ada data transaksi selling out per item pada periode ini.</td></tr>
            <?php else: ?>
                <?php foreach ($unrolledRows as $idx => $row): ?>
                    <tr class="<?= ($idx % 2 === 1) ? 'row-green' : '' ?>">
                        <td><?= $idx + 1 ?></td>
                        <td><?= htmlspecialchars($row['invoice_date']) ?></td>
                        <td><strong><?= htmlspecialchars($row['invoice_no']) ?></strong></td>
                        <td><?= htmlspecialchars($row['sales_name']) ?></td>
                        <td><?= htmlspecialchars($row['customer_name']) ?></td>
                        <td><strong><?= htmlspecialchars($row['item_name']) ?></strong></td>
                        <td style="text-align: right;"><?= number_format($row['dus'], 0, ',', '.') ?></td>
                        <td style="text-align: right;"><?= number_format($row['bonus'], 0, ',', '.') ?></td>
                        <td style="text-align: right;"><?= number_format($row['retur'], 0, ',', '.') ?></td>
                        <td style="text-align: right; font-weight: bold;"><?= number_format($row['final'], 0, ',', '.') ?></td>
                    </tr>
                <?php endforeach; ?>
                <tr class="summary-bar">
                    <td colspan="6" style="text-align: center;">TOTAL PENJUALAN PER ITEM</td>
                    <td style="text-align: right;"><?= number_format($totalDus, 0, ',', '.') ?></td>
                    <td style="text-align: right;"><?= number_format($totalBonus, 0, ',', '.') ?></td>
                    <td style="text-align: right;"><?= number_format($totalRetur, 0, ',', '.') ?></td>
                    <td style="text-align: right; font-weight: bold;"><?= number_format($totalFinal, 0, ',', '.') ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<?php
// hutang_customer.php - Daftar Hutang Customer Matching Screenshot
$pageTitle = "Daftar Hutang Customer";
require_once __DIR__ . '/includes/header.php';

$allSales     = loadData('sales.json');
$allCustomers = loadData('customers.json');
$allInvoices  = loadData('invoices.json');

// Helper to calculate net invoice total after retur
function calculateInvoiceNetTotal($inv) {
    $totalAmt = (float)($inv['total_amount'] ?? 0);
    $totalReturAmt = 0;
    if (isset($inv['total_retur_amount']) && (float)$inv['total_retur_amount'] > 0) {
        $totalReturAmt = (float)$inv['total_retur_amount'];
    } else {
        if (!empty($inv['items']) && is_array($inv['items'])) {
            foreach ($inv['items'] as $it) {
                $rQty   = (int)($it['retur'] ?? 0);
                $rPrice = (float)($it['price'] ?? 0);
                $totalReturAmt += ($rQty * $rPrice);
            }
        }
    }
    return max(0, $totalAmt - $totalReturAmt);
}

// Filter parameters (Default: Always today's date)
$today = date('Y-m-d');
$startDate   = (isset($_GET['start_date']) && $_GET['start_date'] !== '') ? $_GET['start_date'] : $today;
$endDate     = (isset($_GET['end_date'])   && $_GET['end_date'] !== '')   ? $_GET['end_date']   : $today;
$salesId     = isset($_GET['sales_id'])    ? $_GET['sales_id']    : 'ALL';
$customerId  = isset($_GET['customer_id']) ? $_GET['customer_id'] : 'ALL';

$message = '';

// Handle POST Pay Debt / Lunas Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'pay_debt') {
    $invId    = (int)($_POST['invoice_id'] ?? 0);
    $payAmt   = (float)($_POST['pay_amount'] ?? 0);
    $payDate  = $_POST['pay_date'] ?? date('Y-m-d');
    $isTrans  = isset($_POST['method_transfer']) ? 1 : 0;
    $isCash   = isset($_POST['method_cash']) ? 1 : 0;

    $updated = false;
    $paidCustName = '';
    $paidInvNo    = '';

    foreach ($allInvoices as &$inv) {
        if ((int)($inv['id'] ?? 0) === $invId) {
            $currentPaid = (float)($inv['paid_amount'] ?? 0);
            $newPaid = $currentPaid + $payAmt;
            $inv['paid_amount'] = $newPaid;
            
            $netTotal = calculateInvoiceNetTotal($inv);
            if ($newPaid >= $netTotal) {
                $inv['is_cash'] = 1;
                $inv['status']  = 'LUNAS';
            }
            if (!isset($inv['payment_logs'])) {
                $inv['payment_logs'] = [];
            }
            $inv['payment_logs'][] = [
                'date' => $payDate,
                'amount' => $payAmt,
                'is_transfer' => $isTrans,
                'is_cash' => $isCash
            ];
            $updated = true;
            $paidCustName = $inv['customer_name'] ?? 'Customer';
            $paidInvNo    = $inv['invoice_no'] ?? '';
            break;
        }
    }
    if ($updated) {
        saveData('invoices.json', $allInvoices);

        // Auto sync to Total Setoran Harian (daily_deposits.json)
        if ($payAmt > 0) {
            $deposits = loadData('daily_deposits.json');
            $newDepId = count($deposits) > 0 ? max(array_column($deposits, 'id')) + 1 : 1;
            
            $paymentMethodLabel = $isTrans ? 'Transfer Bank' : 'Tunai (Cash)';
            $deposits[] = [
                'id'              => $newDepId,
                'deposit_date'    => $payDate,
                'cash_amount'     => $isCash ? $payAmt : 0.0,
                'transfer_amount' => $isTrans ? $payAmt : 0.0,
                'notes'           => "Pelunasan Hutang " . $paidCustName . " (Faktur #" . $paidInvNo . " - " . $paymentMethodLabel . ")"
            ];
            saveData('daily_deposits.json', $deposits);
        }

        $message = "Pelunasan hutang sebesar Rp " . number_format($payAmt, 0, ',', '.') . " telah berhasil disimpan dan dicatat ke Total Setoran Harian!";
    }
}

// Build list of debt invoices
$debtInvoices = array_filter($allInvoices, function($inv) use ($startDate, $endDate, $salesId, $customerId) {
    // Exclude fully cash / lunas invoices
    $isCash = (int)($inv['is_cash'] ?? 0);
    $status = $inv['status'] ?? '';
    if ($isCash == 1 || $status === 'LUNAS') {
        return false;
    }

    // Exclude invoices where remaining debt (sisa) is 0 or less
    $netTotal = calculateInvoiceNetTotal($inv);
    $paidAmt  = (float)($inv['paid_amount'] ?? 0);
    $sisa     = max(0, $netTotal - $paidAmt);

    if ($sisa <= 0) {
        return false;
    }

    $invDate = $inv['invoice_date'] ?? '';
    if (!empty($invDate) && ($invDate < $startDate || $invDate > $endDate)) {
        return false;
    }

    if ($salesId !== 'ALL' && $salesId !== '') {
        if ((string)($inv['sales_id'] ?? '') !== (string)$salesId) {
            return false;
        }
    }

    if ($customerId !== 'ALL' && $customerId !== '') {
        if ((string)($inv['customer_id'] ?? '') !== (string)$customerId) {
            return false;
        }
    }

    return true;
});

// Sort descending by ID or invoice date
usort($debtInvoices, function($a, $b) {
    return ($b['id'] ?? 0) <=> ($a['id'] ?? 0);
});

// Calculate total debt sum
$totalHutangSum = 0;
foreach ($debtInvoices as $inv) {
    $netTotal = calculateInvoiceNetTotal($inv);
    $paidAmt  = (float)($inv['paid_amount'] ?? 0);
    $sisa     = max(0, $netTotal - $paidAmt);
    $totalHutangSum += $sisa;
}
?>

<h1 class="page-title">Daftar hutang customer</h1>

<?php if (!empty($message)): ?>
    <div style="background-color: #d1e7dd; color: #0f5132; padding: 10px 15px; border-radius: 4px; margin-bottom: 15px; font-weight: bold;">
        <?= htmlspecialchars($message) ?>
    </div>
<?php endif; ?>

<!-- Filter Section Matching Screenshot -->
<div class="filter-card" style="margin-bottom: 15px;">
    <form method="GET" action="hutang_customer.php">
        <!-- Date Row -->
        <div class="filter-row" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
            <label style="font-weight: normal; font-size: 1.2rem;">Mulai tanggal</label>
            <input type="date" name="start_date" value="<?= htmlspecialchars($startDate) ?>">
            <label style="font-weight: normal; font-size: 1.2rem;">hingga tanggal</label>
            <input type="date" name="end_date" value="<?= htmlspecialchars($endDate) ?>">
            <button type="submit" class="btn">GANTI TANGGAL</button>
        </div>

        <!-- Sales & Customer Dropdowns Row -->
        <div class="filter-row" style="margin-top: 10px; display: flex; gap: 10px; flex-wrap: wrap;">
            <select name="sales_id" style="min-width: 180px;" onchange="this.form.submit()">
                <option value="ALL" <?= ($salesId === 'ALL') ? 'selected' : '' ?>>Semua sales</option>
                <?php foreach ($allSales as $s): ?>
                    <option value="<?= $s['id'] ?>" <?= ($salesId == $s['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($s['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select name="customer_id" style="min-width: 180px;" onchange="this.form.submit()">
                <option value="ALL" <?= ($customerId === 'ALL') ? 'selected' : '' ?>>Semua customer</option>
                <?php foreach ($allCustomers as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= ($customerId == $c['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c['name']) ?>
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
                <th style="color: #006600; font-weight: bold;">Nama customer</th>
                <th>Tanggal</th>
                <th style="color: #006600; font-weight: bold;">No faktur</th>
                <th>Hutang</th>
                <th style="text-align: right; width: 320px;"></th>
            </tr>
        </thead>
        <tbody>
            <!-- Summary Bar Row Matching Screenshot -->
            <tr class="summary-bar">
                <td colspan="3" style="font-weight: bold; font-size: 1.05rem;">TOTAL HUTANG</td>
                <td style="font-weight: bold; font-size: 1.05rem;"><?= number_format($totalHutangSum, 2, ',', '.') ?></td>
                <td></td>
            </tr>

            <?php if (empty($debtInvoices)): ?>
                <tr>
                    <td colspan="5" style="text-align: center; color: #666; padding: 20px;">
                        Tidak ada data hutang customer pada periode ini.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach (array_values($debtInvoices) as $idx => $inv): ?>
                    <?php
                    $netTotal = calculateInvoiceNetTotal($inv);
                    $paidAmt  = (float)($inv['paid_amount'] ?? 0);
                    $sisa     = max(0, $netTotal - $paidAmt);
                    $rowClass = ($idx % 2 === 1) ? 'row-green' : '';
                    ?>
                    <tr class="<?= $rowClass ?>">
                        <td style="font-weight: bold; font-size: 0.95rem; width: 220px;">
                            <?= htmlspecialchars($inv['customer_name'] ?? 'Customer') ?>
                        </td>
                        <td><?= htmlspecialchars($inv['invoice_date'] ?? '-') ?></td>
                        <td>
                            <a href="buat_faktur.php?view_id=<?= $inv['id'] ?>" style="color: #006600; text-decoration: none; font-weight: bold;" title="Klik untuk lihat detail faktur">
                                <?= htmlspecialchars($inv['invoice_no']) ?>
                            </a>
                            <?php if (!empty($inv['is_return']) || (float)($inv['total_retur_amount'] ?? 0) > 0): ?>
                                <span class="badge badge-danger" style="margin-left: 4px;">RETUR</span>
                            <?php endif; ?>
                        </td>
                        <td style="font-weight: bold;">
                            <?= number_format($sisa, 2, ',', '.') ?>
                        </td>
                        <td style="text-align: right; padding: 6px 8px;">
                            <form method="POST" action="hutang_customer.php?<?= http_build_query($_GET) ?>" style="display: flex; flex-direction: column; align-items: flex-end; gap: 4px;">
                                <input type="hidden" name="action" value="pay_debt">
                                <input type="hidden" name="invoice_id" value="<?= $inv['id'] ?>">

                                <!-- Top Row: Date, Transfer Checkbox, Cash Checkbox -->
                                <div style="display: flex; align-items: center; gap: 8px; font-size: 0.85rem;">
                                    <input type="date" name="pay_date" value="<?= date('Y-m-d') ?>" style="padding: 1px 4px; font-size: 0.8rem;">
                                    <label style="display: flex; align-items: center; gap: 3px; cursor: pointer; font-size: 0.88rem; font-weight: bold;">
                                        <input type="checkbox" name="method_transfer" value="1"> Transfer
                                    </label>
                                    <label style="display: flex; align-items: center; gap: 3px; cursor: pointer; font-size: 0.88rem; font-weight: bold;">
                                        <input type="checkbox" name="method_cash" value="1"> Cash
                                    </label>
                                </div>

                                <!-- Bottom Row: Amount Input & LUNAS Pill Button -->
                                <div style="display: flex; align-items: center; gap: 6px;">
                                    <input type="number" name="pay_amount" value="<?= $sisa ?>" max="<?= $sisa ?>" step="any" class="form-control" style="width: 140px; padding: 3px 6px; font-size: 0.85rem; text-align: right; font-weight: bold;" required>
                                    <button type="submit" class="btn" style="background-color: #006600; color: #ffffff; border: 1px solid #004d00; border-radius: 3px; padding: 4px 14px; font-weight: bold; font-size: 0.75rem; cursor: pointer; text-transform: uppercase;">
                                        LUNAS
                                    </button>
                                </div>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

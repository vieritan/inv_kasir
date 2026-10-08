<?php
// piutang_supplier.php - Piutang / Hutang Dagang ke Supplier
$pageTitle = "Piutang Suplier";
require_once __DIR__ . '/includes/header.php';

$message = '';
$payables = loadData('supplier_payables.json');
$suppliers = loadData('suppliers.json');

// Get unique supplier names from payables if suppliers.json is empty
$supplierNames = [];
foreach ($payables as $p) {
    if (!in_array($p['supplier_name'], $supplierNames)) {
        $supplierNames[] = $p['supplier_name'];
    }
}
sort($supplierNames);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'pay') {
    $payableId = (int)$_POST['payable_id'];
    $payAmount = (float)$_POST['pay_amount'];

    if ($payableId > 0 && $payAmount > 0) {
        foreach ($payables as &$sp) {
            if ($sp['id'] == $payableId) {
                // Simplified pelunasan logic for demo
                $sp['status'] = 'LUNAS';
                break;
            }
        }
        saveData('supplier_payables.json', $payables);
        $message = "Pelunasan sebesar Rp " . number_format($payAmount, 0, ',', '.') . " telah berhasil dicatat.";
    }
}

// Filters
$startDate = $_GET['start_date'] ?? '2018-01-01';
$endDate = $_GET['end_date'] ?? date('Y-m-d');
$filterSupplier = $_GET['supplier'] ?? 'ALL';
$showLunas = isset($_GET['show_lunas']) && $_GET['show_lunas'] == '1';

// Filter data
$filteredPayables = array_filter($payables, function($p) use ($startDate, $endDate, $filterSupplier, $showLunas) {
    if (!$showLunas && $p['status'] === 'LUNAS') {
        return false;
    }
    if ($filterSupplier !== 'ALL' && $p['supplier_name'] !== $filterSupplier) {
        return false;
    }
    if ($p['date'] < $startDate || $p['date'] > $endDate) {
        return false;
    }
    return true;
});

?>

<style>

    .pay-form {
        display: flex;
        align-items: center;
        gap: 5px;
        justify-content: flex-end;
    }
    .pay-date-new {
        border: 1px solid #767676;
        border-radius: 2px;
        padding: 2px 4px;
        width: 125px;
        font-family: Arial, sans-serif;
    }
    .pay-input-new {
        border: 1px solid #767676;
        border-radius: 2px;
        padding: 2px 4px;
        font-weight: bold;
    }
    .btn-lunas {
        background-color: #004d00;
        color: white;
        border: 1px solid #003300;
        padding: 4px 12px;
        border-radius: 2px;
        font-weight: bold;
        cursor: pointer;
        text-transform: uppercase;
        font-size: 0.85rem;
    }
    .btn-lunas:hover {
        background-color: #003300;
    }
</style>

<?php if ($message): ?>
    <div style="background-color: #d1e7dd; color: #0f5132; padding: 12px 16px; border-radius: 6px; margin-bottom: 20px;">
        <?= htmlspecialchars($message) ?>
    </div>
<?php endif; ?>

<h1 class="page-title">Hutang / Piutang Suplier</h1>

<div class="filter-card">
    <form method="GET" action="piutang_supplier.php">
        <div class="filter-row">
            <label>Mulai tanggal</label>
            <input type="date" name="start_date" value="<?= htmlspecialchars($startDate) ?>">
            <label>hingga tanggal</label>
            <input type="date" name="end_date" value="<?= htmlspecialchars($endDate) ?>">
            <button type="submit" class="btn">GANTI TANGGAL</button>
        </div>

        <div class="filter-row">
            <select name="supplier" style="min-width: 250px;" onchange="this.form.submit()">
                <option value="ALL">Semua suplier</option>
                <?php foreach ($supplierNames as $name): ?>
                    <option value="<?= htmlspecialchars($name) ?>" <?= $filterSupplier === $name ? 'selected' : '' ?>>
                        <?= htmlspecialchars($name) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <?php if ($showLunas): ?>
                <a href="?start_date=<?= $startDate ?>&end_date=<?= $endDate ?>&supplier=<?= urlencode($filterSupplier) ?>&show_lunas=0" class="btn" style="background-color: #28a745; color: white;">[ Sembunyikan Lunas ]</a>
            <?php else: ?>
                <a href="?start_date=<?= $startDate ?>&end_date=<?= $endDate ?>&supplier=<?= urlencode($filterSupplier) ?>&show_lunas=1" class="btn" style="background-color: #28a745; color: white;">[ Tampilkan Lunas ]</a>
            <?php endif; ?>
            <input type="hidden" name="show_lunas" value="<?= $showLunas ? '1' : '0' ?>">
        </div>
    </form>
</div>

<div class="table-responsive">
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 30px;"></th>
                <th>Nama suplier</th>
                <th>Tanggal</th>
                <th style="text-align: center;">Memo</th>
                <th>Srt Jalan</th>
                <th>Supir</th>
                <th>Fktur Jual</th>
                <th style="text-align: right;">Hutang</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($filteredPayables)): ?>
                <tr>
                    <td colspan="9" style="text-align: center; padding: 20px;">Tidak ada data hutang.</td>
                </tr>
            <?php endif; ?>
            
            <?php 
            $rowIdx = 0;
            foreach ($filteredPayables as $p): 
                $rowClass = ($rowIdx % 2 !== 0) ? 'row-green' : '';
                $rowIdx++;
            ?>
                <tr class="<?= $rowClass ?>">
                    <td><input type="checkbox"></td>
                    <td><?= htmlspecialchars($p['supplier_name']) ?></td>
                    <td><?= htmlspecialchars($p['date']) ?></td>
                    <td style="text-align: center;"><?= htmlspecialchars($p['memo']) ?></td>
                    <td><?= htmlspecialchars($p['surat_jalan']) ?></td>
                    <td><?= htmlspecialchars($p['supir']) ?></td>
                    <td><?= htmlspecialchars($p['faktur_jual']) ?></td>
                    <td style="text-align: right;"><?= number_format($p['hutang'], 2, ',', '.') ?></td>
                    <td>
                        <form method="POST" action="piutang_supplier.php" class="pay-form">
                            <input type="hidden" name="action" value="pay">
                            <input type="hidden" name="payable_id" value="<?= $p['id'] ?>">
                            
                            <div style="display: flex; flex-direction: column; gap: 4px; align-items: flex-end;">
                                <div style="display: flex; gap: 8px; align-items: center;">
                                    <input type="date" name="pay_date" class="pay-date-new" value="<?= date('Y-m-d') ?>">
                                    <label style="display:flex; align-items:center; gap:2px; font-weight: bold; font-size: 13px; cursor: pointer;">
                                        <input type="checkbox" name="pay_method[]" value="Transfer" style="width: 16px; height: 16px; border: 2px solid black; cursor: pointer;">Transfer
                                    </label>
                                    <label style="display:flex; align-items:center; gap:2px; font-weight: bold; font-size: 13px; cursor: pointer;">
                                        <input type="checkbox" name="pay_method[]" value="Cash" style="width: 13px; height: 13px; cursor: pointer;">Cash
                                    </label>
                                </div>
                                <div style="display: flex; gap: 4px;">
                                    <input type="number" name="pay_amount" class="pay-input-new" value="<?= $p['hutang'] ?>" style="text-align: right; width: 140px;">
                                    <button type="submit" class="btn-lunas">LUNAS</button>
                                </div>
                            </div>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

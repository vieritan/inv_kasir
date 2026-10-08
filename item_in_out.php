<?php
// item_in_out.php - Log Mutasi Barang Masuk/Keluar
$pageTitle = "Item In/Out";
require_once __DIR__ . '/includes/header.php';

// Filter parameters (Default: Always today's date)
$today = date('Y-m-d');

$allMovements = loadData('movements.json');
$allItems     = loadData('items.json');

$startDate = (isset($_GET['start_date']) && $_GET['start_date'] !== '') ? $_GET['start_date'] : $today;
$endDate   = (isset($_GET['end_date'])   && $_GET['end_date'] !== '')   ? $_GET['end_date']   : $today;
$type      = isset($_GET['type'])       ? $_GET['type']       : 'ALL';

// Map item codes by ID and Name for automatic lookup if missing
$itemCodeMapById = [];
$itemCodeMapByName = [];
if (is_array($allItems)) {
    foreach ($allItems as $it) {
        $code = $it['code'] ?? '';
        if ($code !== '') {
            if (isset($it['id'])) {
                $itemCodeMapById[(string)$it['id']] = $code;
            }
            if (isset($it['name'])) {
                $itemCodeMapByName[$it['name']] = $code;
            }
        }
    }
}

$rows = array_filter($allMovements, function($sm) use ($startDate, $endDate, $type) {
    $mDate = $sm['movement_date'] ?? '';
    if ($mDate < $startDate || $mDate > $endDate) {
        return false;
    }
    $mType = $sm['type'] ?? '';
    if ($type !== 'ALL' && $mType !== $type) {
        return false;
    }
    return true;
});
?>

<h1 class="page-title">Item In / Out (Mutasi Stok Barang)</h1>

<div class="filter-card">
    <form method="GET" action="item_in_out.php" class="filter-row">
        <div class="filter-group">
            <label>Mulai Tanggal</label>
            <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($startDate) ?>">
        </div>
        <div class="filter-group">
            <label>Hingga Tanggal</label>
            <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($endDate) ?>">
        </div>
        <div class="filter-group">
            <label>Jenis Mutasi</label>
            <select name="type" class="form-select">
                <option value="ALL" <?= ($type === 'ALL') ? 'selected' : '' ?>>Semua (IN & OUT)</option>
                <option value="IN" <?= ($type === 'IN') ? 'selected' : '' ?>>Barang Masuk (IN)</option>
                <option value="OUT" <?= ($type === 'OUT') ? 'selected' : '' ?>>Barang Keluar (OUT)</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">FILTER</button>
    </form>
</div>

<div class="table-responsive">
    <table class="data-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Tanggal</th>
                <th>Jenis</th>
                <th>Kode Barang</th>
                <th>Nama Barang</th>
                <th style="text-align: right;">Jumlah</th>
                <th>No Referensi</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($rows)): ?>
                <tr><td colspan="8" style="text-align: center; color: var(--text-muted); padding: 20px;">Tidak ada histori mutasi barang.</td></tr>
            <?php else: ?>
                <?php foreach (array_values($rows) as $idx => $row): ?>
                    <?php
                    $mType = $row['type'] ?? 'IN';
                    $mDate = !empty($row['movement_date']) ? date('d/m/Y', strtotime($row['movement_date'])) : '-';

                    $itemCode = $row['item_code'] ?? '';
                    if ($itemCode === '') {
                        if (!empty($row['item_id']) && isset($itemCodeMapById[(string)$row['item_id']])) {
                            $itemCode = $itemCodeMapById[(string)$row['item_id']];
                        } elseif (!empty($row['item_name']) && isset($itemCodeMapByName[$row['item_name']])) {
                            $itemCode = $itemCodeMapByName[$row['item_name']];
                        }
                    }
                    if ($itemCode === '') {
                        $itemCode = '-';
                    }

                    $itemName = $row['item_name'] ?? '-';
                    $unit     = $row['unit'] ?? 'Dus';
                    $qty      = (int)($row['qty'] ?? 0);
                    $refNo    = $row['ref_no'] ?? '-';
                    $notes    = $row['notes'] ?? '-';
                    ?>
                    <tr class="<?= ($idx % 2 === 1) ? 'row-green' : '' ?>">
                        <td><?= $idx + 1 ?></td>
                        <td><?= $mDate ?></td>
                        <td>
                            <?php if ($mType === 'IN'): ?>
                                <span class="badge badge-success">IN (MASUK)</span>
                            <?php else: ?>
                                <span class="badge badge-danger">OUT (KELUAR)</span>
                            <?php endif; ?>
                        </td>
                        <td><code><?= htmlspecialchars((string)$itemCode) ?></code></td>
                        <td><?= htmlspecialchars((string)$itemName) ?></td>
                        <td style="text-align: right; font-weight: 700;">
                            <?= ($mType === 'IN' ? '+' : '-') . number_format($qty, 0, ',', '.') ?> <?= htmlspecialchars((string)$unit) ?>
                        </td>
                        <td><strong><?= htmlspecialchars((string)$refNo) ?></strong></td>
                        <td><?= htmlspecialchars((string)$notes) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>


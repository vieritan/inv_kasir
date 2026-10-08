<?php
// list_barang.php - List Barang & Current Stock Management (App Theme Consistent)
$pageTitle = "List Barang";
require_once __DIR__ . '/includes/header.php';

$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';
$defaultInDate = date('Y-m-d');
$alertMessage = '';

$allItems = loadData('items.json');

// Handle Add Stock (Submit Form)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_stock') {
    $inDate = $_POST['in_date'] ?? date('Y-m-d');
    $itemId = (int)($_POST['item_id'] ?? 0);
    $unit   = trim($_POST['unit'] ?? '');
    $qty    = (int)($_POST['qty'] ?? 0);
    $bonus  = (int)($_POST['bonus'] ?? 0);

    if ($itemId <= 0 || empty($unit) || $qty <= 0) {
        $alertMessage = "Mohon pilih Nama Barang, Satuan, dan isi Tambah Stok terlebih dahulu!";
    } else {
        $itemName = '';
        $itemCode = '';
        foreach ($allItems as &$it) {
            if ((int)$it['id'] === $itemId) {
                $itemName = $it['name'];
                $itemCode = $it['code'] ?? '';
                $it['stock'] = (int)($it['stock'] ?? 0) + $qty + $bonus;
                $it['bonus'] = (int)($it['bonus'] ?? 0) + $bonus;
                $it['unit']  = $unit;
                break;
            }
        }
        unset($it);

        saveData('items.json', $allItems);

        // Record movement
        $movements = loadData('movements.json');
        $movements[] = [
            'id' => count($movements) + 1,
            'movement_date' => $inDate,
            'item_id' => $itemId,
            'item_code' => $itemCode,
            'item_name' => $itemName,
            'unit' => $unit,
            'type' => 'IN',
            'qty' => $qty,
            'bonus' => $bonus,
            'notes' => 'Tambah Stok List Barang'
        ];
        saveData('movements.json', $movements);

        $alertMessage = "Sukses menambah stok $itemName sebanyak $qty $unit (Bonus: $bonus)!";
    }
}

// Filter items by search query
$filteredItems = array_filter($allItems, function($it) use ($searchQuery) {
    if (empty($searchQuery)) return true;
    return (stripos($it['name'], $searchQuery) !== false || stripos($it['code'], $searchQuery) !== false);
});

// Calculate totals
$totalStock = 0;
$totalBonus = 0;
foreach ($filteredItems as $it) {
    $totalStock += (int)($it['stock'] ?? 0);
    $totalBonus += (int)($it['bonus'] ?? 0);
}
?>

<h1 class="page-title">List Barang</h1>

<?php if (!empty($alertMessage)): ?>
    <script>
        alert("<?= htmlspecialchars($alertMessage, ENT_QUOTES) ?>");
    </script>
<?php endif; ?>

<!-- Top Filter & Add Stock Card -->
<div class="filter-card">
    <form method="POST" action="list_barang.php" style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap; margin-bottom: 12px;">
        <div style="display: flex; align-items: center; gap: 6px;">
            <label style="font-weight: bold;">Tanggal:</label>
            <input type="date" name="in_date" value="<?= htmlspecialchars($defaultInDate) ?>" style="width: 140px;" required>
        </div>

        <div style="display: flex; align-items: center; gap: 6px;">
            <label style="font-weight: bold;">Pilih Barang:</label>
            <select name="item_id" style="min-width: 220px;" required>
                <option value="">-- Pilih Barang --</option>
                <?php foreach ($allItems as $it): ?>
                    <option value="<?= $it['id'] ?>">
                        <?= htmlspecialchars($it['code']) ?> - <?= htmlspecialchars($it['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div style="display: flex; align-items: center; gap: 6px;">
            <label style="font-weight: bold;">Satuan:</label>
            <select name="unit" style="min-width: 130px;" required>
                <option value="">-- Pilih Satuan --</option>
                <option value="Dus">Dus</option>
                <option value="Botol">Botol</option>
            </select>
        </div>

        <div style="display: flex; align-items: center; gap: 6px;">
            <label style="font-weight: bold;">Tambah Stok:</label>
            <input type="number" name="qty" placeholder="0" style="width: 80px;" min="1" required>
        </div>

        <div style="display: flex; align-items: center; gap: 6px;">
            <label style="font-weight: bold;">Bonus:</label>
            <input type="number" name="bonus" value="0" placeholder="0" style="width: 65px;" min="0">
        </div>

        <button type="submit" name="action" value="submit_stock" class="btn">TAMBAH STOK</button>
    </form>

    <!-- Search Form -->
    <form method="GET" action="list_barang.php" style="display: flex; gap: 8px; align-items: center;">
        <input type="text" name="search" class="form-control" style="width: 280px; padding: 5px 10px;" placeholder="🔍 Cari nama atau kode barang..." value="<?= htmlspecialchars($searchQuery) ?>">
        <button type="submit" class="btn">CARI</button>
        <?php if (!empty($searchQuery)): ?>
            <a href="list_barang.php" class="btn" style="background: #666; color: #fff; text-decoration: none;">RESET</a>
        <?php endif; ?>
    </form>
</div>

<!-- Table Displaying All Application Items and Stock -->
<div class="table-responsive">
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 40px;">#</th>
                <th style="width: 120px;">Kode Barang</th>
                <th>Nama Barang</th>
                <th style="width: 80px;">Satuan</th>
                <th style="text-align: right; width: 120px;">Stok Saat Ini</th>
                <th style="text-align: right; width: 100px;">Bonus</th>
                <th style="width: 120px;">Status Stok</th>
            </tr>
        </thead>
        <tbody>
            <tr class="summary-bar">
                <td colspan="4" style="text-align: center;">TOTAL STOK KESELURUHAN</td>
                <td style="text-align: right; font-weight: bold; font-size: 1.05rem;"><?= number_format($totalStock, 0, ',', '.') ?> Dus</td>
                <td style="text-align: right; font-weight: bold;"><?= number_format($totalBonus, 0, ',', '.') ?></td>
                <td></td>
            </tr>
            <?php if (empty($filteredItems)): ?>
                <tr><td colspan="7" style="text-align: center; color: var(--text-muted); padding: 20px;">Tidak ada data barang ditemukan.</td></tr>
            <?php else: ?>
                <?php foreach (array_values($filteredItems) as $idx => $it): ?>
                    <tr>
                        <td><?= $idx + 1 ?></td>
                        <td><code><?= htmlspecialchars($it['code']) ?></code></td>
                        <td><strong><?= htmlspecialchars($it['name']) ?></strong></td>
                        <td><?= htmlspecialchars($it['unit']) ?></td>
                        <td style="text-align: right; font-size: 1.05rem; font-weight: 800;">
                            <?= number_format($it['stock'], 0, ',', '.') ?>
                        </td>
                        <td style="text-align: right;"><?= number_format($it['bonus'] ?? 0, 0, ',', '.') ?></td>
                        <td>
                            <?php if ($it['stock'] <= 5): ?>
                                <span class="badge badge-danger">STOK MENIPIS</span>
                            <?php elseif ($it['stock'] <= 20): ?>
                                <span class="badge badge-warning">CUKUP</span>
                            <?php else: ?>
                                <span class="badge badge-success">AMAN</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

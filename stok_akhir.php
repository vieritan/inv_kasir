<?php
// stok_akhir.php - Laporan Stok Akhir Barang
$pageTitle = "Stok Akhir";
require_once __DIR__ . '/includes/header.php';

$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$allItems = loadData('items.json');

$items = array_filter($allItems, function($it) use ($search) {
    if (empty($search)) return true;
    return (stripos($it['name'], $search) !== false || stripos($it['code'], $search) !== false);
});

$totalStock = 0;
$totalAssetValue = 0;
foreach ($items as $it) {
    $totalStock += (int)($it['stock'] ?? 0);
    $totalAssetValue += ((int)($it['stock'] ?? 0) * (float)($it['cost_price'] ?? 0));
}

// Handle Delete
if (isset($_GET['delete_id'])) {
    $delId = (int)$_GET['delete_id'];
    $allItems = array_filter($allItems, function($it) use ($delId) {
        return (int)$it['id'] !== $delId;
    });
    saveData('items.json', array_values($allItems));
    header("Location: stok_akhir.php");
    exit;
}

// Handle Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_item') {
    $editId = (int)$_POST['edit_id'];
    foreach ($allItems as &$it) {
        if ((int)$it['id'] === $editId) {
            $it['code'] = trim($_POST['code']);
            $it['name'] = trim($_POST['name']);
            $it['unit'] = trim($_POST['unit']);
            $it['cost_price'] = (float)$_POST['cost_price'];
            $it['stock'] = (int)$_POST['stock'];
            break;
        }
    }
    unset($it);
    saveData('items.json', $allItems);
    header("Location: stok_akhir.php");
    exit;
}

// Check if Editing
$editItem = null;
if (isset($_GET['edit_id'])) {
    $editId = (int)$_GET['edit_id'];
    foreach ($allItems as $it) {
        if ((int)$it['id'] === $editId) {
            $editItem = $it;
            break;
        }
    }
}
?>

<h1 class="page-title">Stok Akhir Barang</h1>

<div class="filter-card">
    <form method="GET" action="stok_akhir.php" class="filter-row">
        <div class="filter-group" style="flex-grow: 1; max-width: 400px;">
            <input type="text" name="search" class="form-control" style="width: 100%;" placeholder="Cari nama atau kode barang..." value="<?= htmlspecialchars($search) ?>">
        </div>
        <button type="submit" class="btn btn-primary">CARI</button>
        <?php if (!empty($search)): ?>
            <a href="stok_akhir.php" class="btn">RESET</a>
        <?php endif; ?>
    </form>
</div>

<?php if ($editItem): ?>
<div class="filter-card" style="background: #fdf5d3; border: 1px solid #ffc107;">
    <h3 style="margin-top: 0;">Edit Data Barang</h3>
    <form method="POST" action="stok_akhir.php" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
        <input type="hidden" name="action" value="update_item">
        <input type="hidden" name="edit_id" value="<?= $editItem['id'] ?>">
        
        <div>
            <label>Kode:</label><br>
            <input type="text" name="code" value="<?= htmlspecialchars($editItem['code']) ?>" class="form-control" style="width: 100px;" required>
        </div>
        <div>
            <label>Nama Barang:</label><br>
            <input type="text" name="name" value="<?= htmlspecialchars($editItem['name']) ?>" class="form-control" style="width: 200px;" required>
        </div>
        <div>
            <label>Satuan:</label><br>
            <input type="text" name="unit" value="<?= htmlspecialchars($editItem['unit'] ?? 'Dus') ?>" class="form-control" style="width: 80px;" required>
        </div>
        <div>
            <label>Harga Beli:</label><br>
            <input type="number" name="cost_price" value="<?= $editItem['cost_price'] ?>" class="form-control" style="width: 120px;" min="0" required>
        </div>
        <div>
            <label>Stok Akhir:</label><br>
            <input type="number" name="stock" value="<?= $editItem['stock'] ?>" class="form-control" style="width: 100px;" min="0" required>
        </div>
        <div style="margin-top: 18px;">
            <button type="submit" class="btn" style="background: #28a745; color: white;">SIMPAN</button>
            <a href="stok_akhir.php" class="btn" style="background: #6c757d; color: white; text-decoration: none;">BATAL</a>
        </div>
    </form>
</div>
<?php endif; ?>

<div class="table-responsive">
    <table class="data-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Kode Barang</th>
                <th>Nama Barang</th>
                <th>Satuan</th>
                <th style="text-align: right;">Harga Beli</th>
                <th style="text-align: right;">Stok Akhir</th>
                <th style="text-align: right;">Nilai Aset</th>
                <th>Status Stok</th>
                <th style="text-align: center;">Aksi</th>
            </tr>
        </thead>
        <tbody>
            <tr class="summary-bar">
                <td colspan="5" style="text-align: center;">TOTAL STOK & NILAI ASET</td>
                <td style="text-align: right;"><?= number_format($totalStock, 0, ',', '.') ?> Dus</td>
                <td style="text-align: right;">Rp <?= number_format($totalAssetValue, 2, ',', '.') ?></td>
                <td></td>
                <td></td>
            </tr>
            <?php if (empty($items)): ?>
                <tr><td colspan="9" style="text-align: center; color: var(--text-muted); padding: 20px;">Tidak ada data barang ditemukan.</td></tr>
            <?php else: ?>
                <?php foreach (array_values($items) as $idx => $it): ?>
                    <tr class="<?= ($idx % 2 === 1) ? 'row-green' : '' ?>">
                        <td><?= $idx + 1 ?></td>
                        <td><code><?= htmlspecialchars($it['code']) ?></code></td>
                        <td><strong><?= htmlspecialchars($it['name']) ?></strong></td>
                        <td><?= htmlspecialchars($it['unit']) ?></td>
                        <td style="text-align: right;">Rp <?= number_format($it['cost_price'], 0, ',', '.') ?></td>
                        <td style="text-align: right; font-size: 1.05rem; font-weight: 800;">
                            <?= number_format($it['stock'], 0, ',', '.') ?>
                        </td>
                        <td style="text-align: right;">Rp <?= number_format($it['stock'] * $it['cost_price'], 2, ',', '.') ?></td>
                        <td>
                            <?php if ($it['stock'] <= 5): ?>
                                <span class="badge badge-danger">STOK MENIPIS</span>
                            <?php elseif ($it['stock'] <= 20): ?>
                                <span class="badge badge-warning">CUKUP</span>
                            <?php else: ?>
                                <span class="badge badge-success">AMAN</span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align: center; white-space: nowrap;">
                            <a href="stok_akhir.php?edit_id=<?= $it['id'] ?>" class="btn" style="background: #ffc107; color: #000; padding: 4px 8px; font-size: 11px; text-decoration: none;">EDIT</a>
                            <a href="stok_akhir.php?delete_id=<?= $it['id'] ?>" class="btn" style="background: #dc3545; color: #fff; padding: 4px 8px; font-size: 11px; text-decoration: none;" onclick="return confirm('Yakin ingin menghapus barang ini secara permanen?')">HAPUS</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

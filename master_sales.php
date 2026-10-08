<?php
// master_sales.php - Daftar sales Sesuai Screenshot
$pageTitle = "Daftar sales";
require_once __DIR__ . '/includes/header.php';

$message = '';
$error = '';
$sales = loadData('sales.json');

// Add Sales
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $name = trim($_POST['name']);

    if (!empty($name)) {
        $newId = count($sales) > 0 ? max(array_column($sales, 'id')) + 1 : 1;
        $code = str_pad($newId, 8, '0', STR_PAD_LEFT);
        $sales[] = [
            'id' => $newId,
            'code' => $code,
            'name' => $name,
            'buyer_count' => 0,
            'debt' => 0,
            'limit' => 100000000,
            'due_days' => 0
        ];
        saveData('sales.json', $sales);
        $message = "Sales $name berhasil ditambahkan!";
    } else {
        $error = "Nama sales wajib diisi!";
    }
}
?>

<h1 class="page-title">Daftar sales</h1>

<?php if ($message): ?>
    <div style="background-color: #d1e7dd; color: #0f5132; padding: 6px 12px; margin-bottom: 12px; font-size: 0.85rem;">
        <?= htmlspecialchars($message) ?>
    </div>
<?php endif; ?>

<!-- Top Inline Add Form matching Screenshot Image 1 -->
<div style="margin-bottom: 15px;">
    <form method="POST" action="master_sales.php" style="display: flex; gap: 6px; align-items: center;">
        <input type="hidden" name="action" value="add">
        <input type="text" name="name" style="width: 250px;" placeholder="" required>
        <button type="submit" class="btn">TAMBAH SALES</button>
    </form>
</div>

<!-- Sales Data Table matching Screenshot Image 1 -->
<div class="table-responsive">
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 120px;">Kode sales</th>
                <th>Nama</th>
                <th style="text-align: right; width: 120px;">Jumlah Pembeli</th>
                <th style="text-align: right; width: 120px;">Hutang</th>
                <th style="text-align: right; width: 140px;">Limit</th>
                <th style="text-align: right; width: 100px;">Jth Tempo</th>
                <th style="width: 150px; text-align: center;"></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($sales)): ?>
                <tr>
                    <td colspan="7" style="text-align: center; padding: 20px;">Belum ada data sales.</td>
                </tr>
            <?php else: ?>
                <?php foreach (array_values($sales) as $s): ?>
                    <?php 
                        $buyerCount = isset($s['buyer_count']) ? $s['buyer_count'] : 0;
                        $debtVal    = isset($s['debt']) ? $s['debt'] : 0;
                        $limitVal   = isset($s['limit']) ? $s['limit'] : 100000000;
                        $dueDays    = isset($s['due_days']) ? $s['due_days'] : 0;
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($s['code']) ?></td>
                        <td><strong><?= htmlspecialchars($s['name']) ?></strong></td>
                        <td style="text-align: right;"><?= number_format($buyerCount, 0, ',', '.') ?></td>
                        <td style="text-align: right;"><?= number_format($debtVal, 0, ',', '.') ?></td>
                        <td style="text-align: right;"><?= number_format($limitVal, 0, ',', '.') ?></td>
                        <td style="text-align: right;"><?= number_format($dueDays, 0, ',', '.') ?></td>
                        <td style="text-align: center; display: flex; flex-direction: column; gap: 2px;">
                            <button type="button" class="btn" style="padding: 1px 6px; font-size: 0.7rem;">LIHAT PEMBELI</button>
                            <button type="button" class="btn" style="padding: 1px 6px; font-size: 0.7rem;">LIHAT HUTANG</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

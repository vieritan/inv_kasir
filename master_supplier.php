<?php
// master_supplier.php - Daftar distributor Sesuai Screenshot Image 5
$pageTitle = "Daftar distributor";
require_once __DIR__ . '/includes/header.php';

$message = '';
$suppliers = loadData('suppliers.json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $name = trim($_POST['name']);
    if (!empty($name)) {
        $newId = count($suppliers) > 0 ? max(array_column($suppliers, 'id')) + 1 : 1;
        $code  = str_pad(rand(40, 60), 8, '0', STR_PAD_LEFT);
        $suppliers[] = [
            'id' => $newId,
            'code' => $code,
            'name' => $name,
            'address' => '-',
            'phone' => '-'
        ];
        saveData('suppliers.json', $suppliers);
        $message = "Distributor $name berhasil ditambahkan!";
    }
}
?>

<h1 class="page-title">Daftar distributor</h1>

<?php if ($message): ?>
    <div style="background-color: #d1e7dd; color: #0f5132; padding: 6px 12px; margin-bottom: 12px; font-size: 0.85rem;">
        <?= htmlspecialchars($message) ?>
    </div>
<?php endif; ?>

<div class="table-responsive">
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 50px; text-align: center;">#</th>
                <th style="width: 140px; text-align: center;">Kode suplier</th>
                <th>Nama</th>
                <th style="width: 120px; text-align: right;"></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach (array_values($suppliers) as $idx => $s): ?>
                <?php $rowNum = $idx + 1; ?>
                <!-- Green row highlight on odd rows (1, 3, 5, 7, 9, 11) matching Screenshot Image 5 -->
                <tr class="<?= ($rowNum % 2 != 0 ? 'row-green' : '') ?>">
                    <td style="text-align: center; font-weight: bold;"><?= $rowNum ?></td>
                    <td style="text-align: center; font-family: monospace; font-size: 0.95rem;"><?= htmlspecialchars($s['code']) ?></td>
                    <td style="font-weight: bold;"><?= htmlspecialchars($s['name']) ?></td>
                    <td style="text-align: right;">
                        <button type="button" class="btn" style="padding: 2px 8px; font-size: 0.75rem;">EDIT DATA</button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

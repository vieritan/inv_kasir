<?php
// masuk_barang.php - Form Masuk Barang (Penerimaan Stok dari Suplier) Matching Screenshot
$pageTitle = "Masuk Barang";

require_once __DIR__ . '/config/db.php';

$supplierList = loadData('suppliers.json');
$itemList     = loadData('items.json');

$alertMessage = '';
$selectedSupplierId = isset($_POST['supplier_id']) ? $_POST['supplier_id'] : null;
$defaultInDate = date('Y-m-d');
$defaultRefNo  = rand(100000, 999999);

// Handle Add New Supplier via inline form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_supplier']) && $_POST['action_supplier'] === 'add_supplier') {
    $newSupplierName = trim($_POST['new_supplier_name'] ?? '');
    if (!empty($newSupplierName)) {
        $newId = count($supplierList) > 0 ? max(array_column($supplierList, 'id')) + 1 : 1;
        $code = 'SUP-' . str_pad($newId, 3, '0', STR_PAD_LEFT);
        $supplierList[] = [
            'id' => $newId,
            'code' => $code,
            'name' => $newSupplierName,
            'address' => '-',
            'phone' => '-'
        ];
        saveData('suppliers.json', $supplierList);
        $alertMessage = "Sukses menambah suplier baru!";
        $selectedSupplierId = $newId;
    }
}

// Handle Submit Masuk Barang & Update Item Stock
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_masuk_barang') {
    $inDate     = $_POST['in_date'] ?? date('Y-m-d');
    $supplierId = $_POST['supplier_id'] ?? null;
    $refNo      = trim($_POST['ref_no'] ?? '');

    // Find Supplier Name
    $supplierName = '';
    if ($supplierId) {
        foreach ($supplierList as $sup) {
            if ((string)$sup['id'] === (string)$supplierId) {
                $supplierName = $sup['name'];
                break;
            }
        }
    }
    if (empty($supplierName)) {
        $supplierName = isset($supplierList[0]['name']) ? $supplierList[0]['name'] : 'Suplier General';
    }

    $quantities = $_POST['qty'] ?? [];
    $bonuses    = $_POST['bonus'] ?? [];

    $itemsUpdatedCount = 0;
    $totalQtyAdded = 0;
    $totalHutangAmount = 0;
    $movements = loadData('movements.json');

    foreach ($itemList as &$it) {
        $id = $it['id'];
        $q = isset($quantities[$id]) && $quantities[$id] !== '' ? (int)$quantities[$id] : 0;
        $b = isset($bonuses[$id]) && $bonuses[$id] !== '' ? (int)$bonuses[$id] : 0;
        $totalAdd = $q + $b;

        if ($totalAdd > 0) {
            $it['stock'] = (int)($it['stock'] ?? 0) + $totalAdd;
            $itemsUpdatedCount++;
            $totalQtyAdded += $totalAdd;
            
            // Hitung total nilai hutang berdasarkan qty (tanpa bonus) dikali harga modal
            $costPrice = isset($it['cost_price']) ? (float)$it['cost_price'] : 0;
            $totalHutangAmount += ($q * $costPrice);

            // Record movement
            $movements[] = [
                'id' => count($movements) + 1,
                'movement_date' => $inDate,
                'item_id' => $id,
                'item_code' => $it['code'] ?? '',
                'item_name' => $it['name'] ?? '',
                'unit' => $it['unit'] ?? 'Dus',
                'type' => 'IN',
                'qty' => $q,
                'bonus' => $b,
                'ref_no' => $refNo ?: rand(100000, 999999),
                'supplier_name' => $supplierName,
                'notes' => 'Penerimaan Stok (Masuk Barang)'
            ];
        }
    }
    unset($it);

    if ($itemsUpdatedCount > 0) {
        saveData('items.json', $itemList);
        saveData('movements.json', $movements);
        
        // --- CATAT KE PIUTANG/HUTANG SUPLIER ---
        $payables = loadData('supplier_payables.json');
        $newPayableId = count($payables) > 0 ? max(array_column($payables, 'id')) + 1 : 1;
        $payables[] = [
            'id' => $newPayableId,
            'supplier_name' => $supplierName,
            'date' => $inDate,
            'memo' => 'Barang Masuk',
            'surat_jalan' => $refNo ?: '-',
            'supir' => '',
            'faktur_jual' => '',
            'hutang' => $totalHutangAmount,
            'status' => 'BELUM LUNAS'
        ];
        saveData('supplier_payables.json', $payables);

        $alertMessage = "Sukses mencatat barang masuk ($totalQtyAdded Dus dari $supplierName)! Stok akhir dan Piutang Suplier telah diperbarui.";
    } else {
        $alertMessage = "Belum ada item yang diisi Quantity-nya. Isi minimal 1 item terlebih dahulu.";
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<h1 class="page-title">Masuk Barang</h1>

<?php if (!empty($alertMessage)): ?>
    <script>
        alert("<?= htmlspecialchars($alertMessage, ENT_QUOTES) ?>");
    </script>
<?php endif; ?>

<form method="POST" action="masuk_barang.php" id="masukBarangForm">
    
    <!-- Top Filter Header Section matching Screenshot -->
    <div style="margin-bottom: 12px; font-size: 0.95rem;">
        <!-- Tanggal Row -->
        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
            <label style="width: 80px; font-weight: bold;">Tanggal</label>
            <input type="date" name="in_date" value="<?= htmlspecialchars($defaultInDate) ?>" style="width: 140px;" required>
        </div>

        <!-- Suplier Row -->
        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px; flex-wrap: wrap;">
            <label style="width: 80px; font-weight: bold;">Suplier</label>
            <select name="supplier_id" style="width: 250px;">
                <option value="" <?= ($selectedSupplierId === null || $selectedSupplierId === '') ? 'selected' : '' ?>>Pilih suplier</option>
                <?php foreach ($supplierList as $sup): ?>
                    <?php 
                        $isSelected = ($selectedSupplierId !== null && (string)$selectedSupplierId !== '' && (string)$sup['id'] === (string)$selectedSupplierId);
                    ?>
                    <option value="<?= $sup['id'] ?>" <?= $isSelected ? 'selected' : '' ?>>
                        <?= htmlspecialchars($sup['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <input type="text" name="new_supplier_name" placeholder="Nama suplier baru" style="width: 180px;">
            <button type="submit" name="action_supplier" value="add_supplier" class="btn">TAMBAH</button>
        </div>

        <!-- Reference / PO No Row -->
        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 10px;">
            <div style="width: 80px;"></div>
            <input type="text" name="ref_no" value="<?= htmlspecialchars($defaultRefNo) ?>" placeholder="No Referensi / Surat Jalan" style="width: 180px;">
        </div>

        <!-- Action Buttons Row -->
        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 12px;">
            <label style="width: 80px; font-weight: bold;">Item</label>
            <button type="button" id="btnSembunyi" onclick="sembunyiItem()" class="btn btn-toggle">SEMBUNYI</button>
            <button type="button" id="btnTampilkan" onclick="tampilkanItem()" class="btn btn-toggle active">TAMPILKAN</button>
            <button type="submit" name="action" value="submit_masuk_barang" class="btn" style="background: #e1e1e1; color: #000;">MASUK BARANG</button>
        </div>
    </div>

    <!-- Item List Grid (Quantity & Bonus) -->
    <table class="item-grid-table" id="itemTable">
        <tbody>
            <?php foreach ($itemList as $idx => $it): ?>
                <?php 
                    $rowNum = $idx + 1;
                ?>
                <tr class="item-row <?= ($rowNum % 2 == 0 ? 'row-green-bg' : '') ?>" data-item-id="<?= $it['id'] ?>">
                    <td style="width: 30px; font-weight: bold; text-align: right; padding-right: 8px;"><?= $rowNum ?></td>
                    <td style="width: 180px; font-weight: bold; font-size: 0.9rem;"><?= htmlspecialchars($it['name']) ?></td>
                    
                    <td style="width: 145px; white-space: nowrap;">
                        Quantity <input type="number" name="qty[<?= $it['id'] ?>]" class="qty-input" value="" placeholder="0" style="width: 60px;" min="0" oninput="highlightFilled(this)">
                    </td>
                    
                    <td style="white-space: nowrap;">
                        Bonus <input type="number" name="bonus[<?= $it['id'] ?>]" class="bonus-input" value="0" style="width: 50px;" min="0">
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</form>

<script>
function setBtnActive(activeId) {
    document.querySelectorAll('.btn-toggle').forEach(btn => {
        btn.classList.remove('active');
    });
    if (activeId) {
        const el = document.getElementById(activeId);
        if (el) el.classList.add('active');
    }
}

function sembunyiItem() {
    const rows = document.querySelectorAll('.item-row');
    rows.forEach(row => {
        const qtyInput = row.querySelector('.qty-input');
        const qty = parseInt(qtyInput.value) || 0;
        if (qty <= 0) {
            row.style.display = 'none';
        } else {
            row.style.display = 'table-row';
        }
    });
    setBtnActive('btnSembunyi');
}

function tampilkanItem() {
    const rows = document.querySelectorAll('.item-row');
    rows.forEach(row => {
        row.style.display = 'table-row';
    });
    setBtnActive('btnTampilkan');
}

function highlightFilled(input) {
    const row = input.closest('tr');
    const qty = parseInt(input.value) || 0;
    if (qty > 0) {
        row.style.fontWeight = 'bold';
        row.style.outline = '1px solid #006600';
    } else {
        row.style.fontWeight = 'normal';
        row.style.outline = 'none';
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

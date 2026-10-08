<?php
// master_customer.php - Master Data Customer
$pageTitle = "Master Data Customer";
require_once __DIR__ . '/includes/header.php';

$message = '';
$error = '';
$customers = loadData('customers.json');

// Handle Add Customer
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $code    = trim($_POST['code']);
    $name    = trim($_POST['name']);
    $address = trim($_POST['address']);
    $phone   = trim($_POST['phone']);

    if (!empty($code) && !empty($name)) {
        $newId = count($customers) > 0 ? max(array_column($customers, 'id')) + 1 : 1;
        $customers[] = ['id' => $newId, 'code' => $code, 'name' => $name, 'address' => $address, 'phone' => $phone];
        saveData('customers.json', $customers);
        $message = "Customer $name berhasil ditambahkan!";
    } else {
        $error = "Kode dan Nama Customer wajib diisi!";
    }
}

// Handle Edit Customer
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $editId  = (int)($_POST['edit_id'] ?? 0);
    $code    = trim($_POST['code']);
    $name    = trim($_POST['name']);
    $address = trim($_POST['address']);
    $phone   = trim($_POST['phone']);

    if ($editId > 0 && !empty($code) && !empty($name)) {
        $found = false;
        foreach ($customers as &$c) {
            if ((int)$c['id'] === $editId) {
                $c['code']    = $code;
                $c['name']    = $name;
                $c['address'] = $address;
                $c['phone']   = $phone;
                $found = true;
                break;
            }
        }
        unset($c);

        if ($found) {
            saveData('customers.json', $customers);
            $message = "Data customer $name berhasil diperbarui!";
        } else {
            $error = "Customer tidak ditemukan!";
        }
    } else {
        $error = "Kode dan Nama Customer wajib diisi!";
    }
}

// Handle Delete Customer
if (isset($_GET['delete'])) {
    $delId = (int)$_GET['delete'];
    $customers = array_filter($customers, function($c) use ($delId) { return (int)$c['id'] !== $delId; });
    saveData('customers.json', array_values($customers));
    $message = "Data customer berhasil dihapus.";
}

// Check if currently editing
$editCustomer = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    foreach ($customers as $c) {
        if ((int)$c['id'] === $editId) {
            $editCustomer = $c;
            break;
        }
    }
}
?>

<h1 class="page-title">Master Data Customer (Pelanggan)</h1>

<?php if ($message): ?>
    <div style="background-color: #d1e7dd; color: #0f5132; border: 1px solid #badbcc; padding: 12px 16px; border-radius: 6px; margin-bottom: 20px; font-weight: bold;">
        <?= htmlspecialchars($message) ?>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div style="background-color: #f8d7da; color: #842029; border: 1px solid #f5c2c7; padding: 12px 16px; border-radius: 6px; margin-bottom: 20px; font-weight: bold;">
        <?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: 1fr 2fr; gap: 20px;">
    <div class="filter-card">
        <h3 style="font-size: 1.1rem; margin-bottom: 15px; color: var(--primary-color);">
            <?= $editCustomer ? '✏️ Edit Customer' : 'Tambah Customer Baru' ?>
        </h3>
        <form method="POST" action="master_customer.php">
            <input type="hidden" name="action" value="<?= $editCustomer ? 'edit' : 'add' ?>">
            <?php if ($editCustomer): ?>
                <input type="hidden" name="edit_id" value="<?= $editCustomer['id'] ?>">
            <?php endif; ?>

            <div style="margin-bottom: 12px;">
                <label style="display: block; font-weight: 600; margin-bottom: 4px;">Kode Customer</label>
                <input type="text" name="code" class="form-control" style="width: 100%;" 
                       value="<?= htmlspecialchars($editCustomer ? $editCustomer['code'] : 'CUST-00' . (count($customers)+1)) ?>" required>
            </div>
            <div style="margin-bottom: 12px;">
                <label style="display: block; font-weight: 600; margin-bottom: 4px;">Nama Toko / Customer</label>
                <input type="text" name="name" class="form-control" style="width: 100%;" 
                       placeholder="Contoh: Toko Berkah Jaya" 
                       value="<?= htmlspecialchars($editCustomer ? $editCustomer['name'] : '') ?>" required>
            </div>
            <div style="margin-bottom: 12px;">
                <label style="display: block; font-weight: 600; margin-bottom: 4px;">Alamat</label>
                <textarea name="address" class="form-control" style="width: 100%; height: 60px;"><?= htmlspecialchars($editCustomer ? ($editCustomer['address'] ?? '') : '') ?></textarea>
            </div>
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-weight: 600; margin-bottom: 4px;">No. Telepon / HP</label>
                <input type="text" name="phone" class="form-control" style="width: 100%;" 
                       value="<?= htmlspecialchars($editCustomer ? ($editCustomer['phone'] ?? '') : '') ?>">
            </div>

            <div style="display: flex; gap: 8px;">
                <button type="submit" class="btn btn-primary" style="flex: 1; font-weight: bold;">
                    <?= $editCustomer ? 'UPDATE CUSTOMER' : 'SIMPAN CUSTOMER' ?>
                </button>
                <?php if ($editCustomer): ?>
                    <a href="master_customer.php" class="btn" style="background-color: #6c757d; color: #fff; text-decoration: none; padding: 6px 12px; display: flex; align-items: center; justify-content: center; font-weight: bold;">
                        BATAL
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Kode</th>
                    <th>Nama Customer</th>
                    <th>Alamat</th>
                    <th>Telepon</th>
                    <th style="width: 130px; text-align: center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($customers)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 20px;">Belum ada data customer.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach (array_values($customers) as $idx => $c): 
                        $isEditingThis = ($editCustomer && (int)$editCustomer['id'] === (int)$c['id']);
                    ?>
                        <tr style="<?= $isEditingThis ? 'background-color: #fff3cd;' : '' ?>">
                            <td><?= $idx + 1 ?></td>
                            <td><code><?= htmlspecialchars($c['code']) ?></code></td>
                            <td><strong><?= htmlspecialchars($c['name']) ?></strong></td>
                            <td><?= htmlspecialchars($c['address'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($c['phone'] ?? '-') ?></td>
                            <td style="text-align: center; white-space: nowrap;">
                                <a href="master_customer.php?edit=<?= $c['id'] ?>" class="btn" style="background-color: #0d6efd; color: #fff; border: none; padding: 2px 8px; font-size: 0.75rem; text-decoration: none; margin-right: 4px; display: inline-block;">EDIT</a>
                                <a href="master_customer.php?delete=<?= $c['id'] ?>" onclick="return confirm('Yakin menghapus customer ini?')" class="btn btn-danger" style="padding: 2px 8px; font-size: 0.75rem; text-decoration: none; display: inline-block;">HAPUS</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>


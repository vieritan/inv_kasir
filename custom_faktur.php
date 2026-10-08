<?php
// custom_faktur.php - Pengaturan Custom Faktur & Header Toko (Tanggal, Pembayaran)
$pageTitle = "Custom Faktur";
require_once __DIR__ . '/includes/header.php';

$message = '';
$settings = getInvoiceSettings();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $settings['company_name']        = trim($_POST['company_name']);
    $settings['company_address']     = trim($_POST['company_address']);
    $settings['company_phone']       = trim($_POST['company_phone']);
    $settings['auto_today_date']     = isset($_POST['auto_today_date']) ? 1 : 0;
    $settings['default_date']        = trim($_POST['default_date']);
    $settings['print_font_weight']   = trim($_POST['print_font_weight'] ?? 'bold');
    $settings['print_font_size']     = trim($_POST['print_font_size'] ?? '13px');
    unset($settings['default_status']);
    $settings['last_invoice_number'] = (int)$_POST['last_invoice_number'];

    saveInvoiceSettings($settings);
    $message = "Pengaturan Custom Faktur berhasil diperbarui!";
}

$nextInvoiceNo = peekNextInvoiceNumber();
?>

<h1 class="page-title">Custom Faktur & Pengaturan Toko</h1>

<?php if ($message): ?>
    <div style="background-color: #d1e7dd; color: #0f5132; padding: 8px 12px; border-radius: 4px; margin-bottom: 15px; font-size: 0.9rem;">
        <?= htmlspecialchars($message) ?>
    </div>
<?php endif; ?>

<div class="filter-card" style="max-width: 650px;">
    <form method="POST" action="custom_faktur.php">
        <div style="margin-bottom: 12px;">
            <label style="display: block; font-weight: bold; margin-bottom: 4px;">Nama Toko / Usaha</label>
            <input type="text" name="company_name" class="form-control" style="width: 100%;" value="<?= htmlspecialchars($settings['company_name']) ?>" required>
        </div>

        <div style="margin-bottom: 12px;">
            <label style="display: block; font-weight: bold; margin-bottom: 4px;">Alamat Toko</label>
            <input type="text" name="company_address" class="form-control" style="width: 100%;" value="<?= htmlspecialchars($settings['company_address']) ?>" required>
        </div>

        <div style="margin-bottom: 12px;">
            <label style="display: block; font-weight: bold; margin-bottom: 4px;">No. Telepon Toko</label>
            <input type="text" name="company_phone" class="form-control" style="width: 100%;" value="<?= htmlspecialchars($settings['company_phone']) ?>" required>
        </div>

        <!-- Mode Tanggal (Otomatis Hari Ini vs Tanggal Tetap) -->
        <div style="background: #f8f9fa; border: 1px solid #ddd; padding: 10px 12px; border-radius: 4px; margin-bottom: 12px;">
            <label style="display: flex; align-items: center; gap: 8px; font-weight: bold; cursor: pointer; margin-bottom: 8px;">
                <input type="checkbox" name="auto_today_date" value="1" <?= !empty($settings['auto_today_date']) ? 'checked' : '' ?> onchange="toggleDateInput(this)">
                <span>Otomatis Ikuti Tanggal Hari Ini (Real-time Today Date)</span>
            </label>
            
            <div id="manual_date_box" style="display: <?= !empty($settings['auto_today_date']) ? 'none' : 'block' ?>; margin-top: 6px;">
                <label style="display: block; font-weight: bold; margin-bottom: 4px;">Tanggal Tetap Manual:</label>
                <input type="date" name="default_date" class="form-control" style="width: 180px;" value="<?= htmlspecialchars($settings['default_date']) ?>">
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
            <div>
                <label style="display: block; font-weight: bold; margin-bottom: 4px;">Ketebalan Tulisan (Cetak)</label>
                <select name="print_font_weight" class="form-control" style="width: 100%;">
                    <option value="normal" <?= (isset($settings['print_font_weight']) && $settings['print_font_weight'] === 'normal') ? 'selected' : '' ?>>Normal (Tipis)</option>
                    <option value="bold" <?= (!isset($settings['print_font_weight']) || $settings['print_font_weight'] === 'bold') ? 'selected' : '' ?>>Tebal (Bold)</option>
                </select>
            </div>
            <div>
                <label style="display: block; font-weight: bold; margin-bottom: 4px;">Ukuran Huruf (Cetak)</label>
                <select name="print_font_size" class="form-control" style="width: 100%;">
                    <option value="11px" <?= (isset($settings['print_font_size']) && $settings['print_font_size'] === '11px') ? 'selected' : '' ?>>Sangat Kecil (11px)</option>
                    <option value="12px" <?= (isset($settings['print_font_size']) && $settings['print_font_size'] === '12px') ? 'selected' : '' ?>>Kecil (12px)</option>
                    <option value="13px" <?= (!isset($settings['print_font_size']) || $settings['print_font_size'] === '13px') ? 'selected' : '' ?>>Standar (13px)</option>
                    <option value="14px" <?= (isset($settings['print_font_size']) && $settings['print_font_size'] === '14px') ? 'selected' : '' ?>>Besar (14px)</option>
                    <option value="15px" <?= (isset($settings['print_font_size']) && $settings['print_font_size'] === '15px') ? 'selected' : '' ?>>Sangat Besar (15px)</option>
                </select>
            </div>
        </div>

        <div style="margin-bottom: 16px;">
            <label style="display: block; font-weight: bold; margin-bottom: 4px;">Nomor Faktur Terakhir (Nomor Berikutnya: <code><?= $nextInvoiceNo ?></code>)</label>
            <input type="number" name="last_invoice_number" class="form-control" style="width: 100%;" value="<?= (int)$settings['last_invoice_number'] ?>" required>
            <small style="color: #666;">Faktur berikutnya otomatis bertambah 1 secara berurutan.</small>
        </div>

        <button type="submit" class="btn btn-primary" style="padding: 6px 16px;">SIMPAN PENGATURAN FAKTUR</button>
    </form>
</div>

<script>
function toggleDateInput(checkbox) {
    const box = document.getElementById('manual_date_box');
    if (checkbox.checked) {
        box.style.display = 'none';
    } else {
        box.style.display = 'block';
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<?php
// history_faktur.php - Halaman History Faktur & Hapus Berdasarkan Periode
$pageTitle = "History Faktur Penjualan";
require_once __DIR__ . '/includes/header.php';

// Load data (fast local cache)
$allSales    = loadData('sales.json');
$allCustomers= loadData('customers.json');
$allInvoices = loadData('invoices.json');

$message = '';
$error = '';

// Handle Single Delete
if (isset($_GET['action']) && $_GET['action'] === 'delete_single' && isset($_GET['id'])) {
    $delId = (int)$_GET['id'];
    if ($delId > 0) {
        $count = deleteInvoicesFromStore([$delId]);
        if ($count > 0) {
            $message = "Faktur berhasil dihapus.";
        } else {
            $error = "Faktur tidak ditemukan atau gagal dihapus.";
        }
        // Reload invoices list from local cache
        $allInvoices = loadData('invoices.json');
    }
}

// Handle Bulk Delete by Period / Range
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_bulk') {
    $deleteMode = $_POST['delete_mode'] ?? 'period';
    $idsToDelete = [];
    $periodLabel = '';

    if ($deleteMode === 'period') {
        $delYear  = trim($_POST['del_year'] ?? '');
        $delMonth = trim($_POST['del_month'] ?? '');

        if (empty($delYear)) {
            $error = "Pilihlah Tahun yang ingin dihapus!";
        } else {
            foreach ($allInvoices as $inv) {
                if (empty($inv['invoice_date'])) continue;
                $invTime  = strtotime($inv['invoice_date']);
                $invYear  = date('Y', $invTime);
                $invMonth = date('m', $invTime);

                $matchYear  = ($delYear === 'ALL' || $invYear === $delYear);
                $matchMonth = ($delMonth === 'ALL' || $invMonth === str_pad($delMonth, 2, '0', STR_PAD_LEFT));

                if ($matchYear && $matchMonth) {
                    $idsToDelete[] = (int)$inv['id'];
                }
            }

            $monthNames = [
                '01'=>'Januari','02'=>'Februari','03'=>'Maret','04'=>'April',
                '05'=>'Mei','06'=>'Juni','07'=>'Juli','08'=>'Agustus',
                '09'=>'September','10'=>'Oktober','11'=>'November','12'=>'Desember'
            ];
            $mLabel = ($delMonth !== 'ALL' && isset($monthNames[str_pad($delMonth, 2, '0', STR_PAD_LEFT)])) 
                ? $monthNames[str_pad($delMonth, 2, '0', STR_PAD_LEFT)] 
                : 'Semua Bulan';
            $yLabel = ($delYear === 'ALL') ? 'Semua Tahun' : $delYear;
            $periodLabel = "Periode $mLabel $yLabel";
        }
    } elseif ($deleteMode === 'range') {
        $startDate = trim($_POST['del_start_date'] ?? '');
        $endDate   = trim($_POST['del_end_date'] ?? '');

        if (empty($startDate) || empty($endDate)) {
            $error = "Tanggal Mulai dan Tanggal Selesai wajib diisi!";
        } elseif ($startDate > $endDate) {
            $error = "Tanggal Mulai tidak boleh lebih besar dari Tanggal Selesai!";
        } else {
            foreach ($allInvoices as $inv) {
                if (empty($inv['invoice_date'])) continue;
                if ($inv['invoice_date'] >= $startDate && $inv['invoice_date'] <= $endDate) {
                    $idsToDelete[] = (int)$inv['id'];
                }
            }
            $periodLabel = "Rentang Tanggal " . date('d/m/Y', strtotime($startDate)) . " s/d " . date('d/m/Y', strtotime($endDate));
        }
    }

    if (empty($error)) {
        if (empty($idsToDelete)) {
            $error = "Tidak ada faktur yang ditemukan pada $periodLabel.";
        } else {
            $deletedCount = deleteInvoicesFromStore($idsToDelete);
            $message = "Berhasil menghapus $deletedCount faktur pada $periodLabel.";
            // Reload invoices list from local cache
            $allInvoices = loadData('invoices.json');
        }
    }
}

// Get all unique available years from existing invoices
$availableYears = [];
foreach ($allInvoices as $inv) {
    if (!empty($inv['invoice_date'])) {
        $y = date('Y', strtotime($inv['invoice_date']));
        $availableYears[$y] = true;
    }
}
$availableYears = array_keys($availableYears);
rsort($availableYears); // Sort newest year first

// Filter Parameters for Display
$filterYear      = $_GET['year']        ?? 'ALL';
$filterMonth     = $_GET['month']       ?? 'ALL';
$filterStartDate = $_GET['start_date']  ?? '';
$filterEndDate   = $_GET['end_date']    ?? '';
$filterSales     = $_GET['sales_id']    ?? 'ALL';
$filterCustomer  = $_GET['customer_id'] ?? 'ALL';
$searchQuery     = trim($_GET['search'] ?? '');
$showDeleteCard  = isset($_GET['show_delete']) && $_GET['show_delete'] == '1';

// Filtered Invoices for Table Display
$filteredInvoices = array_filter($allInvoices, function($inv) use ($filterYear, $filterMonth, $filterStartDate, $filterEndDate, $filterSales, $filterCustomer, $searchQuery) {
    if (empty($inv['invoice_date'])) return false;

    $invTime  = strtotime($inv['invoice_date']);
    $invYear  = date('Y', $invTime);
    $invMonth = date('m', $invTime);

    // Year filter
    if ($filterYear !== 'ALL' && $filterYear !== '' && $invYear !== $filterYear) {
        return false;
    }

    // Month filter
    if ($filterMonth !== 'ALL' && $filterMonth !== '' && $invMonth !== str_pad($filterMonth, 2, '0', STR_PAD_LEFT)) {
        return false;
    }

    // Custom Date Range filter
    if ($filterStartDate !== '' && $inv['invoice_date'] < $filterStartDate) {
        return false;
    }
    if ($filterEndDate !== '' && $inv['invoice_date'] > $filterEndDate) {
        return false;
    }

    // Sales filter
    if ($filterSales !== 'ALL' && $filterSales !== '') {
        if ((string)($inv['sales_id'] ?? '') !== (string)$filterSales) {
            return false;
        }
    }

    // Customer filter
    if ($filterCustomer !== 'ALL' && $filterCustomer !== '') {
        if ((string)($inv['customer_id'] ?? '') !== (string)$filterCustomer) {
            return false;
        }
    }

    // Search query filter
    if ($searchQuery !== '') {
        $q = strtolower($searchQuery);
        $invNo    = strtolower($inv['invoice_no'] ?? '');
        $sales    = strtolower($inv['sales_name'] ?? '');
        $customer = strtolower($inv['customer_name'] ?? '');
        $dateStr  = date('d/m/Y', $invTime);
        $notes    = strtolower($inv['notes'] ?? '');

        $match = (strpos($invNo, $q) !== false) ||
                 (strpos($sales, $q) !== false) ||
                 (strpos($customer, $q) !== false) ||
                 (strpos($dateStr, $q) !== false) ||
                 (strpos($notes, $q) !== false);

        if (!$match) return false;
    }

    return true;
});

// Sort descending by ID / Date
usort($filteredInvoices, function($a, $b) {
    return ($b['id'] ?? 0) <=> ($a['id'] ?? 0);
});

// Calculate totals for filtered set
$totalFaktur  = count($filteredInvoices);
$totalDus     = 0;
$totalBonus   = 0;
$totalNominal = 0.0;

foreach ($filteredInvoices as $inv) {
    $totalDus     += (int)($inv['total_dus'] ?? 0);
    $totalBonus   += (int)($inv['total_bonus'] ?? 0);
    $totalNominal += (float)($inv['total_amount'] ?? 0);
}
?>

<div style="margin-bottom: 20px;">
    <h1 class="page-title" style="margin-bottom: 5px;">History Faktur Penjualan</h1>
    <p style="font-size: 0.9rem; color: #555;">Arsip & Riwayat lengkap faktur dari semua periode (tahun dan bulan).</p>
</div>

<?php if ($message): ?>
    <div style="background-color: #d1e7dd; color: #0f5132; border: 1px solid #badbcc; padding: 12px 16px; border-radius: 4px; margin-bottom: 15px; font-weight: bold;">
        <?= htmlspecialchars($message) ?>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div style="background-color: #f8d7da; color: #842029; border: 1px solid #f5c2c7; padding: 12px 16px; border-radius: 4px; margin-bottom: 15px; font-weight: bold;">
        <?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<!-- Summary Statistics Cards -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; margin-bottom: 20px;">
    <div style="background: #ffffff; border: 1px solid #ccc; border-left: 4px solid #006600; padding: 12px 15px; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
        <div style="font-size: 0.75rem; text-transform: uppercase; color: #666; font-weight: bold;">Total Faktur</div>
        <div style="font-size: 1.4rem; font-weight: bold; color: #006600; margin-top: 4px;"><?= number_format($totalFaktur, 0, ',', '.') ?></div>
    </div>
    <div style="background: #ffffff; border: 1px solid #ccc; border-left: 4px solid #0056b3; padding: 12px 15px; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
        <div style="font-size: 0.75rem; text-transform: uppercase; color: #666; font-weight: bold;">Total Dus</div>
        <div style="font-size: 1.4rem; font-weight: bold; color: #0056b3; margin-top: 4px;"><?= number_format($totalDus, 0, ',', '.') ?> Dus</div>
    </div>
    <div style="background: #ffffff; border: 1px solid #ccc; border-left: 4px solid #d97706; padding: 12px 15px; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
        <div style="font-size: 0.75rem; text-transform: uppercase; color: #666; font-weight: bold;">Total Bonus</div>
        <div style="font-size: 1.4rem; font-weight: bold; color: #d97706; margin-top: 4px;"><?= number_format($totalBonus, 0, ',', '.') ?> Dus</div>
    </div>
    <div style="background: #ffffff; border: 1px solid #ccc; border-left: 4px solid #2563eb; padding: 12px 15px; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
        <div style="font-size: 0.75rem; text-transform: uppercase; color: #666; font-weight: bold;">Total Nominal</div>
        <div style="font-size: 1.4rem; font-weight: bold; color: #1e40af; margin-top: 4px;">Rp <?= number_format($totalNominal, 0, ',', '.') ?></div>
    </div>
</div>

<!-- Filter Box & Bulk Delete Accordion Header -->
<div style="background-color: #f9f9f9; border: 1px solid #ccc; padding: 15px; border-radius: 4px; margin-bottom: 20px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; flex-wrap: wrap; gap: 10px;">
        <h3 style="font-size: 1rem; font-weight: bold; margin: 0; color: #333;">
            🔍 Filter History Faktur
        </h3>
        <button type="button" onclick="toggleDeleteSection()" class="btn" style="background-color: #dc3545; color: #fff; border-color: #bd2130; padding: 5px 12px; font-size: 0.8rem;">
            🗑️ HAPUS FAKTUR BERDASARKAN PERIODE
        </button>
    </div>

    <!-- Filter Form -->
    <form method="GET" action="history_faktur.php">
        <div class="filter-row" style="display: flex; flex-wrap: wrap; gap: 12px; align-items: flex-end;">
            <div>
                <label style="display: block; font-weight: bold; margin-bottom: 4px;">Tahun:</label>
                <select name="year" style="min-width: 110px; padding: 5px;">
                    <option value="ALL" <?= $filterYear === 'ALL' ? 'selected' : '' ?>>Semua Tahun</option>
                    <?php foreach ($availableYears as $y): ?>
                        <option value="<?= $y ?>" <?= $filterYear === $y ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label style="display: block; font-weight: bold; margin-bottom: 4px;">Bulan:</label>
                <select name="month" style="min-width: 130px; padding: 5px;">
                    <option value="ALL" <?= $filterMonth === 'ALL' ? 'selected' : '' ?>>Semua Bulan</option>
                    <?php
                    $monthsList = [
                        '1'=>'Januari', '2'=>'Februari', '3'=>'Maret', '4'=>'April',
                        '5'=>'Mei', '6'=>'Juni', '7'=>'Juli', '8'=>'Agustus',
                        '9'=>'September', '10'=>'Oktober', '11'=>'November', '12'=>'Desember'
                    ];
                    foreach ($monthsList as $num => $name):
                        $padNum = str_pad($num, 2, '0', STR_PAD_LEFT);
                    ?>
                        <option value="<?= $padNum ?>" <?= ((string)$filterMonth === (string)$num || (string)$filterMonth === $padNum) ? 'selected' : '' ?>><?= $name ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label style="display: block; font-weight: bold; margin-bottom: 4px;">Sales:</label>
                <select name="sales_id" style="min-width: 140px; padding: 5px;">
                    <option value="ALL">Semua Sales</option>
                    <?php foreach ($allSales as $s): ?>
                        <option value="<?= $s['id'] ?>" <?= (string)$filterSales === (string)$s['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($s['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label style="display: block; font-weight: bold; margin-bottom: 4px;">Customer:</label>
                <select name="customer_id" style="min-width: 160px; padding: 5px;">
                    <option value="ALL">Semua Customer</option>
                    <?php foreach ($allCustomers as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= (string)$filterCustomer === (string)$c['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="flex-grow: 1; min-width: 180px;">
                <label style="display: block; font-weight: bold; margin-bottom: 4px;">Cari (No Faktur / Nama):</label>
                <input type="text" name="search" value="<?= htmlspecialchars($searchQuery) ?>" placeholder="No Faktur, Sales, Customer..." style="width: 100%; padding: 5px;">
            </div>

            <div style="display: flex; gap: 6px;">
                <button type="submit" class="btn btn-toggle active" style="padding: 6px 14px;">FILTER</button>
                <a href="history_faktur.php" class="btn" style="padding: 6px 12px;">RESET</a>
            </div>
        </div>
    </form>
</div>

<!-- Collapsible Bulk Delete Section -->
<div id="bulkDeleteCard" style="display: <?= $showDeleteCard ? 'block' : 'none' ?>; background-color: #fff0f1; border: 2px solid #dc3545; padding: 15px; border-radius: 4px; margin-bottom: 20px;">
    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #f5c6cb; padding-bottom: 8px; margin-bottom: 12px;">
        <h3 style="font-size: 1.05rem; font-weight: bold; color: #842029; margin: 0;">
            ⚠️ Hapus History Faktur Berdasarkan Periode
        </h3>
        <button type="button" onclick="toggleDeleteSection()" style="background: none; border: none; font-size: 1.2rem; cursor: pointer; color: #842029; font-weight: bold;">✕</button>
    </div>

    <p style="font-size: 0.85rem; color: #666; margin-bottom: 12px;">
        Fitur ini memungkinkan Anda menghapus faktur secara massal untuk periode tertentu (seperti 1 bulan penuh atau 1 tahun penuh). <strong>Gunakan tombol "LIHAT DULU FAKTUR" untuk memeriksa data di tabel sebelum melakukan penghapusan!</strong>
    </p>

    <!-- Sub-tabs for mode selection -->
    <div style="display: flex; gap: 8px; margin-bottom: 12px;">
        <button type="button" id="btnModePeriod" onclick="switchDeleteMode('period')" class="btn btn-toggle active">Hapus Per Bulan / Tahun</button>
        <button type="button" id="btnModeRange" onclick="switchDeleteMode('range')" class="btn btn-toggle">Hapus Per Rentang Tanggal</button>
    </div>

    <form method="POST" action="history_faktur.php" id="formBulkDelete" onsubmit="return confirmBulkDelete()">
        <input type="hidden" name="action" value="delete_bulk">
        <input type="hidden" name="delete_mode" id="delete_mode_input" value="period">

        <!-- Mode 1: Period (Month & Year) -->
        <div id="modePeriodContainer" style="display: flex; flex-wrap: wrap; gap: 12px; align-items: flex-end;">
            <div>
                <label style="display: block; font-weight: bold; margin-bottom: 4px;">Pilih Tahun:</label>
                <select name="del_year" id="del_year" style="padding: 6px; min-width: 140px;">
                    <option value="">-- Pilih Tahun --</option>
                    <option value="ALL" <?= $filterYear === 'ALL' ? 'selected' : '' ?>>Semua Tahun</option>
                    <?php foreach ($availableYears as $y): ?>
                        <option value="<?= $y ?>" <?= $filterYear === (string)$y ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label style="display: block; font-weight: bold; margin-bottom: 4px;">Pilih Bulan:</label>
                <select name="del_month" id="del_month" style="padding: 6px; min-width: 160px;">
                    <option value="ALL" <?= $filterMonth === 'ALL' ? 'selected' : '' ?>>Semua Bulan (Full Year)</option>
                    <?php foreach ($monthsList as $num => $name): 
                        $padNum = str_pad($num, 2, '0', STR_PAD_LEFT);
                    ?>
                        <option value="<?= $padNum ?>" <?= ((string)$filterMonth === (string)$num || (string)$filterMonth === $padNum) ? 'selected' : '' ?>><?= $name ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="display: flex; gap: 8px;">
                <button type="button" onclick="previewPeriodInvoices()" class="btn" style="background-color: #0d6efd; color: #fff; border-color: #0b5ed7; padding: 6px 14px;">
                    👁️ LIHAT DULU FAKTUR
                </button>
                <button type="submit" class="btn" style="background-color: #dc3545; color: #fff; border-color: #bd2130; padding: 6px 16px;">
                    🗑️ PROSES HAPUS PERIODE
                </button>
            </div>
        </div>

        <!-- Mode 2: Custom Date Range -->
        <div id="modeRangeContainer" style="display: none; flex-wrap: wrap; gap: 12px; align-items: flex-end;">
            <div>
                <label style="display: block; font-weight: bold; margin-bottom: 4px;">Dari Tanggal:</label>
                <input type="date" name="del_start_date" id="del_start_date" value="<?= htmlspecialchars($filterStartDate ?: date('Y-m-01')) ?>" style="padding: 5px;">
            </div>

            <div>
                <label style="display: block; font-weight: bold; margin-bottom: 4px;">Sampai Tanggal:</label>
                <input type="date" name="del_end_date" id="del_end_date" value="<?= htmlspecialchars($filterEndDate ?: date('Y-m-d')) ?>" style="padding: 5px;">
            </div>

            <div style="display: flex; gap: 8px;">
                <button type="button" onclick="previewRangeInvoices()" class="btn" style="background-color: #0d6efd; color: #fff; border-color: #0b5ed7; padding: 6px 14px;">
                    👁️ LIHAT DULU FAKTUR
                </button>
                <button type="submit" class="btn" style="background-color: #dc3545; color: #fff; border-color: #bd2130; padding: 6px 16px;">
                    🗑️ PROSES HAPUS RENTANG
                </button>
            </div>
        </div>
    </form>

    <!-- Live Preview Status Indicator -->
    <div style="margin-top: 12px; background: #ffffff; border: 1px solid #f5c6cb; border-radius: 4px; padding: 8px 12px; font-size: 0.85rem; color: #333; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">
        <div>
            <strong>📌 Status Pratinjau Tabel Saat Ini:</strong> 
            <span style="color: #006600; font-weight: bold;"><?= number_format($totalFaktur, 0, ',', '.') ?> faktur</span> ditemukan
            (Total Nominal: <span style="color: #1e40af; font-weight: bold;">Rp <?= number_format($totalNominal, 0, ',', '.') ?></span>)
        </div>
        <div style="font-size: 0.8rem; font-style: italic; color: #777;">
            Tabel di bawah memperlihatkan faktur yang akan terhapus jika Anda memproses hapus.
        </div>
    </div>
</div>

<!-- History Invoices Table -->
<div class="table-responsive">
    <table class="data-table" style="border: 1px solid #ccc; width: 100%;">
        <thead>
            <tr style="background-color: #f0f0f0; border-bottom: 2px solid #ccc;">
                <th style="width: 40px; text-align: center;">No</th>
                <th style="width: 100px;">No Faktur</th>
                <th style="width: 100px;">Tanggal</th>
                <th>Sales</th>
                <th>Customer</th>
                <th style="width: 80px; text-align: center;">Total Dus</th>
                <th style="width: 80px; text-align: center;">Bonus</th>
                <th style="width: 120px; text-align: right;">Total Nominal</th>
                <th style="width: 90px; text-align: center;">Jenis</th>
                <th style="width: 130px; text-align: center;">Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($filteredInvoices)): ?>
                <tr>
                    <td colspan="10" style="text-align: center; padding: 25px; color: #777; font-style: italic;">
                        Tidak ada data history faktur untuk filter / periode yang dipilih.
                    </td>
                </tr>
            <?php else: ?>
                <?php 
                $no = 1; 
                foreach ($filteredInvoices as $inv): 
                    $invId       = $inv['id'];
                    $invNo       = htmlspecialchars($inv['invoice_no'] ?? '-');
                    $invDate     = !empty($inv['invoice_date']) ? date('d/m/Y', strtotime($inv['invoice_date'])) : '-';
                    $salesName   = htmlspecialchars($inv['sales_name'] ?? '-');
                    $custName    = htmlspecialchars($inv['customer_name'] ?? '-');
                    $dus         = (int)($inv['total_dus'] ?? 0);
                    $bonus       = (int)($inv['total_bonus'] ?? 0);
                    $amount      = (float)($inv['total_amount'] ?? 0);
                    $isRetur     = !empty($inv['is_return']);
                    $isCash      = !empty($inv['is_cash']);
                    $itemsJson   = htmlspecialchars(json_encode($inv['items'] ?? []), ENT_QUOTES, 'UTF-8');
                ?>
                <tr style="border-bottom: 1px solid #eee; <?= ($no % 2 === 0) ? 'background-color: #fafafa;' : '' ?>">
                    <td style="text-align: center; color: #666;"><?= $no++ ?></td>
                    <td style="font-weight: bold; color: #006600;"><?= $invNo ?></td>
                    <td><?= $invDate ?></td>
                    <td><?= $salesName ?></td>
                    <td><?= $custName ?></td>
                    <td style="text-align: center; font-weight: bold;"><?= $dus ?></td>
                    <td style="text-align: center; color: #d97706; font-weight: bold;"><?= $bonus ?></td>
                    <td style="text-align: right; font-weight: bold;">Rp <?= number_format($amount, 0, ',', '.') ?></td>
                    <td style="text-align: center;">
                        <?php if ($isRetur): ?>
                            <span style="background: #ffecb3; color: #b78103; padding: 2px 6px; border-radius: 3px; font-size: 0.75rem; font-weight: bold;">RETUR</span>
                        <?php elseif ($isCash): ?>
                            <span style="background: #d1e7dd; color: #0f5132; padding: 2px 6px; border-radius: 3px; font-size: 0.75rem; font-weight: bold;">CASH</span>
                        <?php else: ?>
                            <span style="background: #e2e3e5; color: #41464b; padding: 2px 6px; border-radius: 3px; font-size: 0.75rem; font-weight: bold;">KREDIT</span>
                        <?php endif; ?>
                    </td>
                    <td style="text-align: center; white-space: nowrap;">
                        <button type="button" class="btn" onclick="showInvoiceDetail('<?= $invNo ?>', '<?= $invDate ?>', '<?= addslashes($salesName) ?>', '<?= addslashes($custName) ?>', 'Rp <?= number_format($amount, 0, ',', '.') ?>', <?= $itemsJson ?>)" style="padding: 2px 6px; font-size: 0.7rem; background-color: #0d6efd; color: #fff; border-color: #0b5ed7;">
                            👁️ DETAIL
                        </button>
                        <a href="history_faktur.php?action=delete_single&id=<?= $invId ?>" onclick="return confirm('Apakah Anda yakin ingin menghapus faktur No. <?= $invNo ?>? Data yang dihapus tidak dapat dipulihkan.')" class="btn" style="padding: 2px 6px; font-size: 0.7rem; background-color: #dc3545; color: #fff; border-color: #bd2130;">
                            🗑️ HAPUS
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr class="summary-bar">
                <td colspan="5" style="text-align: right; font-weight: bold; font-size: 0.95rem;">TOTAL KESELURUHAN (FILTERED):</td>
                <td style="text-align: center; font-weight: bold; font-size: 0.95rem;"><?= number_format($totalDus, 0, ',', '.') ?></td>
                <td style="text-align: center; font-weight: bold; font-size: 0.95rem; color: #d97706;"><?= number_format($totalBonus, 0, ',', '.') ?></td>
                <td style="text-align: right; font-weight: bold; font-size: 0.95rem;">Rp <?= number_format($totalNominal, 0, ',', '.') ?></td>
                <td colspan="2"></td>
            </tr>
        </tfoot>
    </table>
</div>

<!-- Modal Detail Items -->
<div id="detailModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; justify-content: center; align-items: center;">
    <div style="background: #fff; width: 90%; max-width: 650px; border-radius: 6px; padding: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.3); max-height: 85vh; display: flex; flex-direction: column;">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 12px;">
            <h3 style="margin: 0; font-size: 1.1rem; color: #006600;" id="modalTitle">Detail Faktur</h3>
            <button type="button" onclick="closeDetailModal()" style="background: none; border: none; font-size: 1.3rem; cursor: pointer; color: #999;">✕</button>
        </div>

        <div style="font-size: 0.85rem; color: #444; margin-bottom: 12px; display: grid; grid-template-columns: 1fr 1fr; gap: 8px;" id="modalMeta">
            <!-- Filled dynamically -->
        </div>

        <div style="overflow-y: auto; flex-grow: 1; margin-bottom: 15px;">
            <table style="width: 100%; border-collapse: collapse; font-size: 0.85rem;">
                <thead>
                    <tr style="background: #f5f5f5; border-bottom: 2px solid #ddd;">
                        <th style="padding: 6px; text-align: left;">Item / Barang</th>
                        <th style="padding: 6px; text-align: center; width: 50px;">Qty</th>
                        <th style="padding: 6px; text-align: center; width: 50px;">Bonus</th>
                        <th style="padding: 6px; text-align: right; width: 90px;">Harga</th>
                        <th style="padding: 6px; text-align: right; width: 70px;">Diskon</th>
                        <th style="padding: 6px; text-align: right; width: 100px;">Subtotal</th>
                    </tr>
                </thead>
                <tbody id="modalItemsBody">
                    <!-- Filled dynamically -->
                </tbody>
            </table>
        </div>

        <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #eee; padding-top: 10px;">
            <div style="font-weight: bold; font-size: 1rem; color: #006600;" id="modalTotalAmount">Total: Rp 0</div>
            <button type="button" onclick="closeDetailModal()" class="btn" style="padding: 6px 16px;">TUTUP</button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('show_delete') === '1' || <?= (!empty($error) || !empty($message)) ? 'true' : 'false' ?>) {
        const card = document.getElementById('bulkDeleteCard');
        if (card) {
            card.style.display = 'block';
        }
    }
    if (urlParams.get('start_date') && urlParams.get('end_date')) {
        switchDeleteMode('range');
    }
});

function toggleDeleteSection() {
    const card = document.getElementById('bulkDeleteCard');
    if (card.style.display === 'none') {
        card.style.display = 'block';
        card.scrollIntoView({ behavior: 'smooth' });
    } else {
        card.style.display = 'none';
    }
}

function switchDeleteMode(mode) {
    document.getElementById('delete_mode_input').value = mode;
    const btnPeriod = document.getElementById('btnModePeriod');
    const btnRange = document.getElementById('btnModeRange');
    const containerPeriod = document.getElementById('modePeriodContainer');
    const containerRange = document.getElementById('modeRangeContainer');

    if (mode === 'period') {
        btnPeriod.classList.add('active');
        btnRange.classList.remove('active');
        containerPeriod.style.display = 'flex';
        containerRange.style.display = 'none';
    } else {
        btnRange.classList.add('active');
        btnPeriod.classList.remove('active');
        containerPeriod.style.display = 'none';
        containerRange.style.display = 'flex';
    }
}

function previewPeriodInvoices() {
    const year = document.getElementById('del_year').value || 'ALL';
    const month = document.getElementById('del_month').value || 'ALL';
    if (!year) {
        alert('Silakan pilih Tahun terlebih dahulu untuk melihat pratinjau faktur!');
        return;
    }
    window.location.href = `history_faktur.php?year=${encodeURIComponent(year)}&month=${encodeURIComponent(month)}&show_delete=1`;
}

function previewRangeInvoices() {
    const start = document.getElementById('del_start_date').value;
    const end = document.getElementById('del_end_date').value;
    if (!start || !end) {
        alert('Silakan tentukan Tanggal Mulai dan Tanggal Selesai terlebih dahulu!');
        return;
    }
    window.location.href = `history_faktur.php?start_date=${encodeURIComponent(start)}&end_date=${encodeURIComponent(end)}&show_delete=1`;
}

function confirmBulkDelete() {
    const mode = document.getElementById('delete_mode_input').value;
    let periodText = '';

    if (mode === 'period') {
        const yearSelect = document.getElementById('del_year');
        const monthSelect = document.getElementById('del_month');

        if (!yearSelect.value) {
            alert('Silakan pilih Tahun yang ingin dihapus terlebih dahulu.');
            return false;
        }

        const yearText = yearSelect.options[yearSelect.selectedIndex].text;
        const monthText = monthSelect.options[monthSelect.selectedIndex].text;
        periodText = `${monthText} - ${yearText}`;
    } else {
        const start = document.getElementById('del_start_date').value;
        const end = document.getElementById('del_end_date').value;
        if (!start || !end) {
            alert('Silakan tentukan Tanggal Mulai dan Tanggal Selesai.');
            return false;
        }
        periodText = `Rentang Tanggal ${start} s/d ${end}`;
    }

    const currentCount = <?= (int)$totalFaktur ?>;
    const currentAmount = 'Rp <?= number_format($totalNominal, 0, ',', '.') ?>';

    return confirm(`PERINGATAN SANGAT PENTING!\n\nApakah Anda YAKIN ingin MENGHAPUS SELURUH FAKTUR untuk ${periodText}?\n\nPratinjau saat ini: Ditemukan ${currentCount} faktur (${currentAmount}).\n\nSemua faktur pada periode ini akan dihapus PERMANEN dari penyimpanan lokal dan Supabase. Action ini TIDAK DAPAT DIBATALKAN.`);
}

function showInvoiceDetail(invNo, date, sales, cust, totalStr, items) {
    document.getElementById('modalTitle').innerText = 'Detail Faktur No. ' + invNo;
    document.getElementById('modalMeta').innerHTML = `
        <div><strong>Tanggal:</strong> ${date}</div>
        <div><strong>Sales:</strong> ${sales}</div>
        <div><strong>Customer:</strong> ${cust}</div>
    `;
    document.getElementById('modalTotalAmount').innerText = 'Total Faktur: ' + totalStr;

    const tbody = document.getElementById('modalItemsBody');
    tbody.innerHTML = '';

    if (!items || items.length === 0) {
        tbody.innerHTML = `<tr><td colspan="6" style="text-align: center; padding: 15px; color: #888;">Tidak ada rincian barang.</td></tr>`;
    } else {
        items.forEach((item, idx) => {
            const row = document.createElement('tr');
            row.style.borderBottom = '1px solid #eee';
            row.style.backgroundColor = (idx % 2 === 0) ? '#ffffff' : '#f9f9f9';

            const name = item.item_name || item.name || '-';
            const qty = item.qty || 0;
            const bonus = item.bonus || 0;
            const price = parseFloat(item.price || 0).toLocaleString('id-ID');
            const discount = parseFloat(item.discount || 0).toLocaleString('id-ID');
            const subtotal = parseFloat(item.subtotal || 0).toLocaleString('id-ID');

            row.innerHTML = `
                <td style="padding: 6px; font-weight: bold;">${name}</td>
                <td style="padding: 6px; text-align: center;">${qty}</td>
                <td style="padding: 6px; text-align: center; color: #d97706;">${bonus}</td>
                <td style="padding: 6px; text-align: right;">Rp ${price}</td>
                <td style="padding: 6px; text-align: right;">Rp ${discount}</td>
                <td style="padding: 6px; text-align: right; font-weight: bold;">Rp ${subtotal}</td>
            `;
            tbody.appendChild(row);
        });
    }

    const modal = document.getElementById('detailModal');
    modal.style.display = 'flex';
}

function closeDetailModal() {
    document.getElementById('detailModal').style.display = 'none';
}

// Close modal when clicking outside content
window.onclick = function(event) {
    const modal = document.getElementById('detailModal');
    if (event.target === modal) {
        modal.style.display = 'none';
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

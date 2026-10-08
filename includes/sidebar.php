<?php
// includes/sidebar.php
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar">
    <!-- Group 1 -->
    <div class="menu-group">
        <a href="buat_faktur.php" class="menu-item <?= ($currentPage == 'buat_faktur.php') ? 'active' : '' ?>">BUAT FAKTUR</a>
        <a href="index.php" class="menu-item <?= ($currentPage == 'index.php' || $currentPage == 'daftar_faktur.php') ? 'active' : '' ?>">DAFTAR FAKTUR</a>
        <a href="history_faktur.php" class="menu-item <?= ($currentPage == 'history_faktur.php') ? 'active' : '' ?>">HISTORY FAKTUR</a>
        <a href="selling_out.php" class="menu-item <?= ($currentPage == 'selling_out.php') ? 'active' : '' ?>">SELLING OUT</a>
        <a href="selling_in.php" class="menu-item <?= ($currentPage == 'selling_in.php') ? 'active' : '' ?>">SELLING IN</a>
        <a href="item_in_out.php" class="menu-item <?= ($currentPage == 'item_in_out.php') ? 'active' : '' ?>">ITEM IN/OUT</a>
        <a href="stok_akhir.php" class="menu-item <?= ($currentPage == 'stok_akhir.php') ? 'active' : '' ?>">STOK AKHIR</a>
    </div>

    <!-- Group 2 -->
    <div class="menu-group">
        <a href="master_sales.php" class="menu-item <?= ($currentPage == 'master_sales.php') ? 'active' : '' ?>">SALES</a>
        <a href="master_customer.php" class="menu-item <?= ($currentPage == 'master_customer.php') ? 'active' : '' ?>">CUSTOMER</a>
        <a href="master_supplier.php" class="menu-item <?= ($currentPage == 'master_supplier.php') ? 'active' : '' ?>">SUPLIER</a>
    </div>

    <!-- Group 3 -->
    <div class="menu-group">
        <a href="hutang_customer.php" class="menu-item <?= ($currentPage == 'hutang_customer.php') ? 'active' : '' ?>">HUTANG CUSTOMER</a>
        <a href="piutang_supplier.php" class="menu-item <?= ($currentPage == 'piutang_supplier.php') ? 'active' : '' ?>">PIUTANG SUPLIER</a>
        <a href="setoran_harian.php" class="menu-item <?= ($currentPage == 'setoran_harian.php') ? 'active' : '' ?>">TOTAL SETORAN HARIAN</a>
        <a href="masuk_barang.php" class="menu-item <?= ($currentPage == 'masuk_barang.php') ? 'active' : '' ?>">MASUK BARANG</a>
    </div>

    <!-- Group 4 -->
    <div class="menu-group">
        <a href="custom_faktur.php" class="menu-item <?= ($currentPage == 'custom_faktur.php') ? 'active' : '' ?>">CUSTOM FAKTUR</a>
        <a href="ganti_password.php" class="menu-item <?= ($currentPage == 'ganti_password.php') ? 'active' : '' ?>">GANTI PASSWORD</a>
        <a href="logout.php" onclick="return confirm('Apakah Anda yakin ingin keluar dari sistem?')" class="menu-item">LOGOUT</a>
    </div>

    <div style="margin-top: 30px; text-align: center; color: #666666; font-size: 0.8rem; padding-bottom: 20px; font-family: Arial, sans-serif; font-weight: bold;">
        &copy; copyright VTA 2026
    </div>
</aside>

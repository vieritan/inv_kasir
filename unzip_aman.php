<?php
echo "<h3>Proses Ekstrak File ZIP...</h3>";
$zipFile = 'Deploy_Hostinger_Fix.zip';

if (!file_exists($zipFile)) {
    die("<h4 style='color:red'>File $zipFile tidak ditemukan di hosting! Pastikan sudah diupload.</h4>");
}

if (class_exists('ZipArchive')) {
    $zip = new ZipArchive;
    if ($zip->open($zipFile) === TRUE) {
        $zip->extractTo(__DIR__);
        $zip->close();
        echo "<h4 style='color:green'>BERHASIL! Semua file dan folder sudah terekstrak dengan rapi.</h4>";
        echo "<p>Silakan kembali ke File Manager dan refresh halamannya. Anda sudah bisa mencoba akses webnya.</p>";
        echo "<p>Penting: Hapus file <b>unzip_aman.php</b> dan <b>Deploy_Hostinger_Fix.zip</b> demi keamanan.</p>";
    } else {
        echo "<h4 style='color:red'>GAGAL mengekstrak ZIP! File mungkin korup saat proses upload.</h4>";
    }
} else {
    echo "<h4 style='color:red'>Server hosting Bapak tidak mendukung fitur ZipArchive PHP.</h4>";
}
?>

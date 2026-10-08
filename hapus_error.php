<?php
echo "<h3>Membersihkan file error...</h3>";
$dir = __DIR__;
$files = scandir($dir);
$count = 0;
foreach ($files as $f) {
    if ($f === '.' || $f === '..') continue;
    // Jika nama file mengandung backslash (\)
    if (strpos($f, '\\') !== false) {
        if (unlink($dir . '/' . $f)) {
            echo "Dihapus: " . htmlspecialchars($f) . "<br>";
            $count++;
        } else {
            echo "<span style='color:red'>Gagal menghapus: " . htmlspecialchars($f) . "</span><br>";
        }
    }
}
echo "<h4>Selesai! $count file rusak berhasil dihapus. Silakan hapus file hapus_error.php ini lalu ekstrak Deploy_Hostinger_Fix.zip.</h4>";
?>

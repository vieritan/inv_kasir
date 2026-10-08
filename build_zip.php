<?php
$zip = new ZipArchive();
if ($zip->open('Deploy_Hostinger_V3.zip', ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
    $dir = new RecursiveDirectoryIterator('.');
    $iter = new RecursiveIteratorIterator($dir);
    foreach ($iter as $file) {
        if ($file->isDir()) continue;
        $path = $file->getPathname();
        $path = str_replace('\\', '/', $path);
        if (strpos($path, './') === 0) {
            $path = substr($path, 2);
        }
        
        if (strpos($path, '.git') === 0 || strpos($path, 'Deploy_Hostinger') === 0 || $path === 'build_zip.php') {
            continue;
        }
        
        if ($path === 'config/db.php') {
            $zip->addFile('config/db_hostinger.php', 'config/db.php');
        } else {
            $zip->addFile($file->getPathname(), $path);
        }
    }
    $zip->close();
    echo "ZIP created successfully with correct Linux paths!";
} else {
    echo "Failed to create ZIP!";
}

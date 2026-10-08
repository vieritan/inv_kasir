<?php
// includes/header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$currentPage = basename($_SERVER['PHP_SELF']);
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    if ($currentPage !== 'login.php') {
        header("Location: login.php");
        exit;
    }
}

require_once __DIR__ . '/../config/db.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? $pageTitle : 'Sistem Inventaris & Daftar Faktur'; ?></title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?= time() ?>">
</head>
<body>
<div class="app-container">
    <?php include __DIR__ . '/sidebar.php'; ?>
    <main class="main-content">

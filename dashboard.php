<?php
// dashboard.php - File pengalih sementara
include 'config/database.php';

// Cek apakah user sudah login
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Redirect berdasarkan role
switch($_SESSION['role']) {
    case 'admin':
        header("Location: admin/dashboard.php");
        break;
    case 'petugas':
        header("Location: petugas/dashboard.php");
        break;
    case 'nasabah':
        header("Location: nasabah/dashboard.php");
        break;
    default:
        // Jika role tidak dikenal, logout
        session_destroy();
        header("Location: login.php?error=unknown_role");
}
exit();
?>
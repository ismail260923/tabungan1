<?php
// petugas/cek_login.php
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'petugas') {
    header("Location: ../login.php");
    exit();
}
?>
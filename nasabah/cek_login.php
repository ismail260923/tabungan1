<?php
// nasabah/cek_login.php
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'nasabah') {
    header("Location: ../login.php");
    exit();
}
?>
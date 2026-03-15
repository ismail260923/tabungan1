<?php
// nasabah/cek_session.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'nasabah') {
    header("Location: ../login.php?error=session_invalid");
    exit();
}
?>
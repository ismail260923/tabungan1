<?php
// cek_session.php
function cekLogin() {
    if(!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }
}

function cekRole($allowed_roles = []) {
    if(!empty($allowed_roles) && !in_array($_SESSION['role'], $allowed_roles)) {
        header("Location: dashboard.php?error=unauthorized");
        exit();
    }
}

function getUserData($conn, $user_id) {
    $query = "SELECT * FROM users WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if(mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result);
    }
    return null;
}

function getSaldo($conn, $user_id) {
    $query = "SELECT total_saldo FROM saldo WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if(mysqli_num_rows($result) > 0) {
        $data = mysqli_fetch_assoc($result);
        return $data['total_saldo'];
    }
    
    // Buat saldo baru jika tidak ada
    $insert = "INSERT INTO saldo (user_id, total_saldo) VALUES (?, 0)";
    $stmt = mysqli_prepare($conn, $insert);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    
    return 0;
}
?>
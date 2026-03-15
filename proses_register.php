<?php
include 'config/database.php';

if(isset($_POST['register'])) {
    $nama_lengkap = mysqli_real_escape_string($conn, $_POST['nama_lengkap']);
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $no_telepon = mysqli_real_escape_string($conn, $_POST['no_telepon']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validasi password
    if($password !== $confirm_password) {
        header("Location: register.php?error=password_mismatch");
        exit();
    }
    
    // Cek username sudah ada atau belum
    $check_username = mysqli_query($conn, "SELECT id FROM users WHERE username = '$username'");
    if(mysqli_num_rows($check_username) > 0) {
        header("Location: register.php?error=username_exists");
        exit();
    }
    
    // Cek email sudah ada atau belum
    $check_email = mysqli_query($conn, "SELECT id FROM users WHERE email = '$email'");
    if(mysqli_num_rows($check_email) > 0) {
        header("Location: register.php?error=email_exists");
        exit();
    }
    
    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Simpan user baru
    $query = "INSERT INTO users (nama_lengkap, username, password, email, no_telepon, role) 
              VALUES ('$nama_lengkap', '$username', '$hashed_password', '$email', '$no_telepon', 'nasabah')";
    
    if(mysqli_query($conn, $query)) {
        $user_id = mysqli_insert_id($conn);
        
        // Buat saldo awal untuk user
        $insert_saldo = "INSERT INTO saldo (user_id, total_saldo) VALUES ($user_id, 0)";
        mysqli_query($conn, $insert_saldo);
        
        // Redirect ke login dengan pesan sukses
        header("Location: login.php?register=success");
        exit();
    } else {
        header("Location: register.php?error=registration_failed");
        exit();
    }
} else {
    header("Location: register.php");
    exit();
}
?>
<?php
// proses_login.php
include 'config/database.php';

if(isset($_POST['login'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];
    
    $query = "SELECT * FROM users WHERE username = '$username'";
    $result = mysqli_query($conn, $query);
    
    if(mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);
        
        if(password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
            $_SESSION['role'] = $user['role'];
            
            // Redirect berdasarkan role dengan BASE_URL
            switch($user['role']) {
                case 'admin':
                    $redirect = $base_url . 'admin/dashboard.php';
                    break;
                case 'petugas':
                    $redirect = $base_url . 'petugas/dashboard.php';
                    break;
                case 'nasabah':
                    $redirect = $base_url . 'nasabah/dashboard.php';
                    break;
                default:
                    $redirect = $base_url . 'login.php?error=unknown_role';
            }
            
            header("Location: " . $redirect);
            exit();
        } else {
            header("Location: " . $base_url . "login.php?error=invalid");
            exit();
        }
    } else {
        header("Location: " . $base_url . "login.php?error=invalid");
        exit();
    }
} else {
    header("Location: " . $base_url . "login.php");
    exit();
}
?>
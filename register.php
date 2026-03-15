<?php
include 'config/database.php';

// Redirect jika sudah login
if(isset($_SESSION['user_id'])) {
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
            header("Location: dashboard.php");
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar - Tabungan Siswa</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <div class="register-box">
            <h2>Daftar Akun Tabungan Siswa</h2>
            
            <?php
            if(isset($_GET['error'])) {
                $error = $_GET['error'];
                if($error == 'username_exists') {
                    echo '<div class="alert alert-danger">Username sudah digunakan!</div>';
                } elseif($error == 'email_exists') {
                    echo '<div class="alert alert-danger">Email sudah terdaftar!</div>';
                } elseif($error == 'password_mismatch') {
                    echo '<div class="alert alert-danger">Password tidak cocok!</div>';
                }
            }
            ?>
            
            <form action="proses_register.php" method="POST">
                <div class="form-group">
                    <label for="nama_lengkap">Nama Lengkap</label>
                    <input type="text" id="nama_lengkap" name="nama_lengkap" required>
                </div>
                
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="no_telepon">No. Telepon</label>
                    <input type="text" id="no_telepon" name="no_telepon">
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Konfirmasi Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                
                <button type="submit" name="register" class="btn btn-primary btn-block">Daftar</button>
            </form>
            
            <p class="text-center">
                Sudah punya akun? <a href="login.php">Login disini</a>
            </p>
        </div>
    </div>
</body>
</html>
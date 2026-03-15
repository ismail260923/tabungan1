<?php
include 'config/database.php';

// Redirect jika sudah login
if(isset($_SESSION['user_id'])) {
    // Redirect berdasarkan role dengan BASE_URL
    switch($_SESSION['role']) {
        case 'admin':
            header("Location: " . $base_url . "admin/dashboard.php");
            break;
        case 'petugas':
            header("Location: " . $base_url . "petugas/dashboard.php");
            break;
        case 'nasabah':
            header("Location: " . $base_url . "nasabah/dashboard.php");
            break;
        default:
            session_destroy();
            header("Location: " . $base_url . "login.php");
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Tabungan Siswa</title>
    <base href="<?php echo $base_url; ?>">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <div class="login-box">
            <h2>Login Aplikasi Tabungan Siswa</h2>
            
            <?php
            if(isset($_GET['error'])) {
                echo '<div class="alert alert-danger">Username atau password salah!</div>';
            }
            if(isset($_GET['register']) && $_GET['register'] == 'success') {
                echo '<div class="alert alert-success">Pendaftaran berhasil! Silakan login.</div>';
            }
            ?>
            
            <form action="proses_login.php" method="POST">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit" name="login" class="btn btn-primary btn-block">Login</button>
            </form>
            
            <p class="text-center">
                Belum punya akun? <a href="register.php">Daftar disini</a>
            </p>
        </div>
    </div>
</body>
</html>
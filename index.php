<?php
// index.php
include 'config/database.php';

// Jika sudah login, redirect ke dashboard masing-masing
if(isset($_SESSION['user_id'])) {
    switch($_SESSION['role']) {
        case 'admin':
            header("Location: admin/dashboard.php");
            exit();
        case 'petugas':
            header("Location: petugas/dashboard.php");
            exit();
        case 'nasabah':
            header("Location: nasabah/dashboard.php");
            exit();
        default:
            // Jika role tidak dikenal, logout
            session_destroy();
            header("Location: index.php");
            exit();
    }
}

// Ambil statistik untuk ditampilkan
$total_nasabah = 0;
$total_transaksi = 0;
$total_saldo = 0;

$result_nasabah = mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE role='nasabah'");
if($result_nasabah) {
    $total_nasabah = mysqli_fetch_assoc($result_nasabah)['total'];
}

$result_transaksi = mysqli_query($conn, "SELECT COUNT(*) as total FROM tabungan");
if($result_transaksi) {
    $total_transaksi = mysqli_fetch_assoc($result_transaksi)['total'];
}

$result_saldo = mysqli_query($conn, "SELECT COALESCE(SUM(total_saldo), 0) as total FROM saldo");
if($result_saldo) {
    $total_saldo = mysqli_fetch_assoc($result_saldo)['total'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aplikasi Tabungan Siswa</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }

        /* Navbar */
        .navbar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
        }

        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .logo i {
            font-size: 2em;
            color: #667eea;
        }

        .logo h2 {
            color: #2d3748;
            font-size: 1.5em;
        }

        .nav-menu {
            display: flex;
            gap: 20px;
            align-items: center;
        }

        .nav-link {
            color: #4a5568;
            text-decoration: none;
            font-weight: 500;
            padding: 8px 15px;
            border-radius: 8px;
            transition: all 0.3s;
        }

        .nav-link:hover {
            color: #667eea;
            background: #f7fafc;
        }

        .btn-nav-login {
            background: #667eea;
            color: white !important;
            padding: 10px 25px;
            border-radius: 50px;
        }

        .btn-nav-login:hover {
            background: #5a67d8;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-nav-register {
            background: #48bb78;
            color: white !important;
            padding: 10px 25px;
            border-radius: 50px;
        }

        .btn-nav-register:hover {
            background: #38a169;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(72, 187, 120, 0.4);
        }

        /* Container */
        .container {
            max-width: 1200px;
            margin: 80px auto 0;
            padding: 20px;
        }

        /* Hero Section */
        .hero-section {
            display: flex;
            align-items: center;
            gap: 50px;
            padding: 60px 0;
            color: white;
        }

        .hero-content {
            flex: 1;
        }

        .hero-content h1 {
            font-size: 3.5em;
            margin-bottom: 20px;
            line-height: 1.2;
        }

        .hero-content p {
            font-size: 1.2em;
            margin-bottom: 30px;
            opacity: 0.9;
        }

        .hero-buttons {
            display: flex;
            gap: 20px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 15px 35px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            font-size: 1.1em;
            transition: all 0.3s;
        }

        .btn-primary {
            background: white;
            color: #667eea;
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(255,255,255,0.3);
        }

        .btn-secondary {
            background: transparent;
            border: 2px solid white;
            color: white;
        }

        .btn-secondary:hover {
            background: white;
            color: #667eea;
            transform: translateY(-3px);
        }

        .hero-image {
            flex: 1;
            text-align: center;
        }

        .hero-image i {
            font-size: 15em;
            color: rgba(255,255,255,0.2);
        }

        /* Stats Section */
        .stats-section {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 30px;
            margin: 50px 0;
            padding: 40px;
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
        }

        .stat-item {
            text-align: center;
            color: white;
        }

        .stat-number {
            font-size: 3em;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .stat-label {
            font-size: 1.1em;
            opacity: 0.9;
        }

        /* Features */
        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin: 60px 0;
        }

        .feature-box {
            background: white;
            padding: 40px 30px;
            border-radius: 20px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: all 0.3s;
        }

        .feature-box:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
        }

        .feature-icon {
            font-size: 3.5em;
            margin-bottom: 20px;
        }

        .feature-box h3 {
            color: #2d3748;
            font-size: 1.5em;
            margin-bottom: 15px;
        }

        .feature-box p {
            color: #718096;
            line-height: 1.6;
        }

        /* CTA Section */
        .cta-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 80px 40px;
            border-radius: 20px;
            text-align: center;
            color: white;
            margin: 60px 0;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }

        .cta-section h2 {
            font-size: 2.5em;
            margin-bottom: 20px;
        }

        .cta-section p {
            font-size: 1.2em;
            margin-bottom: 40px;
            opacity: 0.9;
        }

        .cta-buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
        }

        .btn-cta {
            padding: 18px 45px;
            font-size: 1.2em;
        }

        /* Menu Login & Daftar Khusus */
        .auth-menu {
            display: flex;
            gap: 20px;
            justify-content: center;
            margin: 40px 0;
        }

        .auth-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            text-align: center;
            flex: 1;
            max-width: 400px;
            transition: all 0.3s;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .auth-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
        }

        .auth-icon {
            font-size: 4em;
            margin-bottom: 20px;
        }

        .auth-icon.login {
            color: #667eea;
        }

        .auth-icon.register {
            color: #48bb78;
        }

        .auth-card h3 {
            font-size: 1.8em;
            margin-bottom: 15px;
            color: #2d3748;
        }

        .auth-card p {
            color: #718096;
            margin-bottom: 30px;
        }

        .btn-auth {
            display: inline-block;
            padding: 15px 40px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-auth.login {
            background: #667eea;
            color: white;
        }

        .btn-auth.login:hover {
            background: #5a67d8;
            transform: scale(1.05);
        }

        .btn-auth.register {
            background: #48bb78;
            color: white;
        }

        .btn-auth.register:hover {
            background: #38a169;
            transform: scale(1.05);
        }

        /* Footer */
        .footer {
            text-align: center;
            padding: 40px;
            color: rgba(255,255,255,0.8);
            margin-top: 60px;
            border-top: 1px solid rgba(255,255,255,0.2);
        }

        .footer-links {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin-bottom: 20px;
        }

        .footer-links a {
            color: white;
            text-decoration: none;
        }

        .footer-links a:hover {
            text-decoration: underline;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .nav-container {
                flex-direction: column;
                gap: 15px;
            }
            
            .nav-menu {
                flex-wrap: wrap;
                justify-content: center;
            }
            
            .hero-section {
                flex-direction: column;
                text-align: center;
            }
            
            .hero-content h1 {
                font-size: 2.5em;
            }
            
            .hero-buttons {
                justify-content: center;
            }
            
            .stats-section {
                grid-template-columns: 1fr;
            }
            
            .auth-menu {
                flex-direction: column;
                align-items: center;
            }
            
            .cta-buttons {
                flex-direction: column;
                align-items: center;
            }
            
            .btn-cta {
                width: 100%;
                max-width: 300px;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="logo">
                <i class="fas fa-piggy-bank"></i>
                <h2>Tabungan Siswa</h2>
            </div>
            <div class="nav-menu">
                <a href="#home" class="nav-link">Home</a>
                <a href="#features" class="nav-link">Fitur</a>
                <a href="#about" class="nav-link">Tentang</a>
                <a href="#contact" class="nav-link">Kontak</a>
                <a href="login.php" class="nav-link btn-nav-login">
                    <i class="fas fa-sign-in-alt"></i> Login
                </a>
                <a href="register.php" class="nav-link btn-nav-register">
                    <i class="fas fa-user-plus"></i> Daftar
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container">
        <!-- Hero Section -->
        <div class="hero-section" id="home">
            <div class="hero-content">
                <h1>Kelola Tabungan Siswa dengan Mudah</h1>
                <p>Aplikasi digital untuk mengelola tabungan siswa secara efisien, aman, dan transparan. Cocok untuk sekolah, koperasi, dan unit usaha simpan pinjam.</p>
                <div class="hero-buttons">
                    <a href="register.php" class="btn btn-primary">
                        <i class="fas fa-user-plus"></i> Daftar Sekarang
                    </a>
                    <a href="#features" class="btn btn-secondary">
                        <i class="fas fa-info-circle"></i> Pelajari Lebih
                    </a>
                </div>
            </div>
            <div class="hero-image">
                <i class="fas fa-chart-line"></i>
            </div>
        </div>

        <!-- Stats Section -->
        <div class="stats-section">
            <div class="stat-item">
                <div class="stat-number"><?php echo number_format($total_nasabah ?: 0); ?></div>
                <div class="stat-label"><i class="fas fa-users"></i> Nasabah Aktif</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?php echo number_format($total_transaksi ?: 0); ?></div>
                <div class="stat-label"><i class="fas fa-exchange-alt"></i> Total Transaksi</div>
            </div>
            <div class="stat-item">
                <div class="stat-number">Rp <?php echo number_format($total_saldo ?: 0, 0, ',', '.'); ?></div>
                <div class="stat-label"><i class="fas fa-wallet"></i> Total Saldo</div>
            </div>
        </div>

        <!-- Menu Login & Daftar (Fitur Utama) -->
        <div class="auth-menu" id="auth">
            <div class="auth-card">
                <div class="auth-icon login">
                    <i class="fas fa-sign-in-alt"></i>
                </div>
                <h3>Sudah Punya Akun?</h3>
                <p>Masuk ke akun Anda untuk mengakses dashboard dan melakukan transaksi tabungan.</p>
                <a href="login.php" class="btn-auth login">
                    <i class="fas fa-sign-in-alt"></i> Login Sekarang
                </a>
            </div>
            <div class="auth-card">
                <div class="auth-icon register">
                    <i class="fas fa-user-plus"></i>
                </div>
                <h3>Belum Punya Akun?</h3>
                <p>Daftar sekarang untuk mulai menabung dan menikmati kemudahan transaksi digital.</p>
                <a href="register.php" class="btn-auth register">
                    <i class="fas fa-user-plus"></i> Daftar Sekarang
                </a>
            </div>
        </div>

        <!-- Features -->
        <div class="features" id="features">
            <div class="feature-box">
                <div class="feature-icon">🔒</div>
                <h3>Aman & Terpercaya</h3>
                <p>Data terenkripsi dengan sistem keamanan berlapis. Setiap transaksi tercatat dengan detail.</p>
            </div>
            <div class="feature-box">
                <div class="feature-icon">📱</div>
                <h3>Mudah Digunakan</h3>
                <p>Antarmuka yang user-friendly, dapat diakses dari berbagai perangkat dengan mudah.</p>
            </div>
            <div class="feature-box">
                <div class="feature-icon">📊</div>
                <h3>Laporan Lengkap</h3>
                <p>Dilengkapi dengan grafik dan laporan transaksi yang informatif dan mudah dipahami.</p>
            </div>
            <div class="feature-box">
                <div class="feature-icon">⚡</div>
                <h3>Transaksi Cepat</h3>
                <p>Proses setor dan tarik tunai real-time tanpa perlu menunggu lama.</p>
            </div>
            <div class="feature-box">
                <div class="feature-icon">👥</div>
                <h3>Multi Role</h3>
                <p>Mendukung 3 role pengguna: Admin, Petugas, dan Nasabah dengan fitur yang berbeda.</p>
            </div>
            <div class="feature-box">
                <div class="feature-icon">📈</div>
                <h3>Monitor Perkembangan</h3>
                <p>Pantau perkembangan tabungan dengan grafik dan statistik yang menarik.</p>
            </div>
        </div>

        <!-- CTA Section -->
        <div class="cta-section">
            <h2>Siap untuk Memulai Menabung?</h2>
            <p>Bergabunglah dengan ribuan siswa lainnya yang telah menggunakan aplikasi ini untuk mengelola tabungan mereka.</p>
            <div class="cta-buttons">
                <a href="register.php" class="btn btn-primary btn-cta">
                    <i class="fas fa-user-plus"></i> Daftar Gratis
                </a>
                <a href="login.php" class="btn btn-secondary btn-cta">
                    <i class="fas fa-sign-in-alt"></i> Login
                </a>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer" id="contact">
            <div class="footer-links">
                <a href="#home">Home</a>
                <a href="#features">Fitur</a>
                <a href="#about">Tentang</a>
                <a href="#contact">Kontak</a>
                <a href="login.php">Login</a>
                <a href="register.php">Daftar</a>
            </div>
            <p><i class="fas fa-copyright"></i> <?php echo date('Y'); ?> Aplikasi Tabungan Siswa. All rights reserved.</p>
            <p style="margin-top: 10px;">Dikembangkan untuk kemudahan menabung siswa di era digital</p>
        </div>
    </div>

    <!-- Smooth Scroll -->
    <script>
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            document.querySelector(this.getAttribute('href')).scrollIntoView({
                behavior: 'smooth'
            });
        });
    });
    </script>
</body>
</html>
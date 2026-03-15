<?php
// nasabah/profil.php
include '../config/database.php';

// Cek login dan role nasabah
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'nasabah') {
    header("Location: " . BASE_URL . "login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Ambil data user
$query_user = mysqli_query($conn, "SELECT * FROM users WHERE id = $user_id");
$user = mysqli_fetch_assoc($query_user);

// Ambil saldo
$saldo = mysqli_fetch_assoc(mysqli_query($conn, "SELECT total_saldo FROM saldo WHERE user_id = $user_id"));
$total_saldo = $saldo['total_saldo'] ?? 0;

// Proses update profil
if(isset($_POST['update_profil'])) {
    $nama_lengkap = mysqli_real_escape_string($conn, $_POST['nama_lengkap']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $no_telepon = mysqli_real_escape_string($conn, $_POST['no_telepon']);
    $kelas = mysqli_real_escape_string($conn, $_POST['kelas']);
    $nis = isset($_POST['nis']) ? mysqli_real_escape_string($conn, $_POST['nis']) : '';
    $alamat = mysqli_real_escape_string($conn, $_POST['alamat']);
    
    // Cek apakah kolom nis ada di tabel
    $check_column = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'nis'");
    if(mysqli_num_rows($check_column) > 0) {
        // Kolom nis ada
        $query = "UPDATE users SET 
                  nama_lengkap = '$nama_lengkap',
                  email = '$email',
                  no_telepon = '$no_telepon',
                  kelas = '$kelas',
                  nis = '$nis',
                  alamat = '$alamat'
                  WHERE id = $user_id";
    } else {
        // Kolom nis tidak ada
        $query = "UPDATE users SET 
                  nama_lengkap = '$nama_lengkap',
                  email = '$email',
                  no_telepon = '$no_telepon',
                  kelas = '$kelas',
                  alamat = '$alamat'
                  WHERE id = $user_id";
    }
    
    if(mysqli_query($conn, $query)) {
        $success_message = "Profil berhasil diperbarui!";
        // Update session
        $_SESSION['nama_lengkap'] = $nama_lengkap;
        // Refresh data
        $user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id = $user_id"));
    } else {
        $error_message = "Gagal memperbarui profil: " . mysqli_error($conn);
    }
}

// Proses ganti password
if(isset($_POST['ganti_password'])) {
    $password_lama = $_POST['password_lama'];
    $password_baru = $_POST['password_baru'];
    $konfirmasi_password = $_POST['konfirmasi_password'];
    
    // Verifikasi password lama
    if(!password_verify($password_lama, $user['password'])) {
        $error_message = "Password lama salah!";
    } elseif($password_baru != $konfirmasi_password) {
        $error_message = "Konfirmasi password tidak cocok!";
    } elseif(strlen($password_baru) < 6) {
        $error_message = "Password minimal 6 karakter!";
    } else {
        $password_hash = password_hash($password_baru, PASSWORD_DEFAULT);
        $query = "UPDATE users SET password = '$password_hash' WHERE id = $user_id";
        if(mysqli_query($conn, $query)) {
            $success_message = "Password berhasil diganti!";
        } else {
            $error_message = "Gagal mengganti password: " . mysqli_error($conn);
        }
    }
}

// Ambil total transaksi untuk statistik
$total_trans = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM tabungan WHERE user_id = $user_id"));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Saya - Tabungan Siswa</title>
    <link rel="stylesheet" href="../css/style.css">
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

        .nasabah-wrapper {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 280px;
            background: linear-gradient(180deg, #2d3748 0%, #1a202c 100%);
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            box-shadow: 4px 0 15px rgba(0,0,0,0.1);
            z-index: 100;
        }

        .sidebar-header {
            padding: 30px 20px;
            text-align: center;
            border-bottom: 1px solid #4a5568;
        }

        .sidebar-header h2 {
            font-size: 1.5em;
            margin-bottom: 5px;
            color: #fff;
        }

        .sidebar-header p {
            color: #a0aec0;
            font-size: 0.9em;
        }

        .user-welcome {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid #4a5568;
        }

        .user-avatar-large {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 2em;
            margin: 0 auto 15px;
            border: 3px solid #48bb78;
        }

        .user-welcome .name {
            font-size: 1.2em;
            font-weight: 600;
            margin-bottom: 5px;
            color: white;
        }

        .user-welcome .role {
            color: #48bb78;
            font-size: 0.9em;
        }

        .sidebar-menu {
            padding: 20px 0;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 15px 25px;
            color: #cbd5e0;
            text-decoration: none;
            transition: all 0.3s;
            font-size: 1em;
        }

        .sidebar-menu a:hover {
            background: #4a5568;
            color: white;
            padding-left: 35px;
        }

        .sidebar-menu a.active {
            background: #48bb78;
            color: white;
            border-left: 4px solid #48bb78;
        }

        .sidebar-footer {
            padding: 20px 25px;
            background: #1a202c;
            position: absolute;
            bottom: 0;
            width: 100%;
            border-top: 1px solid #4a5568;
        }

        .logout-link {
            color: #fc8181 !important;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 10px;
            padding: 8px 12px;
            border-radius: 8px;
            transition: all 0.3s;
        }

        .logout-link:hover {
            background: #fc8181;
            color: white !important;
        }

        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 30px;
            min-height: 100vh;
        }

        .content-wrapper {
            max-width: 1200px;
            margin: 0 auto;
        }

        .page-header {
            background: white;
            border-radius: 20px;
            padding: 25px 30px;
            margin-bottom: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .page-header h1 {
            color: #2d3748;
            font-size: 2em;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .page-header h1 i {
            color: #48bb78;
        }

        .breadcrumb {
            color: #718096;
            font-size: 0.95em;
        }

        .profile-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .profile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px;
            text-align: center;
            color: white;
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 3em;
            font-weight: bold;
            color: #667eea;
            border: 4px solid white;
        }

        .profile-header h2 {
            font-size: 1.8em;
            margin-bottom: 5px;
        }

        .profile-header p {
            opacity: 0.9;
        }

        .profile-body {
            padding: 30px;
        }

        .tab-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 10px;
        }

        .tab-btn {
            padding: 10px 20px;
            background: none;
            border: none;
            font-size: 1em;
            font-weight: 600;
            color: #718096;
            cursor: pointer;
            transition: all 0.3s;
            border-radius: 8px 8px 0 0;
        }

        .tab-btn:hover {
            color: #48bb78;
        }

        .tab-btn.active {
            color: #48bb78;
            border-bottom: 3px solid #48bb78;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #4a5568;
            font-weight: 600;
        }

        .form-group label i {
            color: #48bb78;
            margin-right: 5px;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 1em;
            transition: all 0.3s;
        }

        .form-control:focus {
            outline: none;
            border-color: #48bb78;
            box-shadow: 0 0 0 3px rgba(72, 187, 120, 0.1);
        }

        .form-control[readonly] {
            background: #f7fafc;
            cursor: not-allowed;
        }

        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 10px;
            font-size: 1em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: #48bb78;
            color: white;
        }

        .btn-primary:hover {
            background: #38a169;
            transform: translateY(-2px);
        }

        .btn-warning {
            background: #ed8936;
            color: white;
        }

        .btn-warning:hover {
            background: #dd6b20;
            transform: translateY(-2px);
        }

        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background: #c6f6d5;
            color: #22543d;
            border: 1px solid #9ae6b4;
        }

        .alert-danger {
            background: #fed7d7;
            color: #742a2a;
            border: 1px solid #fc8181;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .info-item {
            background: #f7fafc;
            padding: 15px;
            border-radius: 10px;
        }

        .info-item .label {
            color: #718096;
            font-size: 0.9em;
            margin-bottom: 5px;
        }

        .info-item .value {
            color: #2d3748;
            font-size: 1.1em;
            font-weight: 600;
        }

        /* Cek apakah kolom nis ada */
        .nis-field {
            display: <?php echo mysqli_num_rows(mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'nis'")) > 0 ? 'block' : 'none'; ?>;
        }

        @media (max-width: 768px) {
            .sidebar {
                display: none;
            }
            .main-content {
                margin-left: 0;
                padding: 15px;
            }
            .info-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="nasabah-wrapper">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h2>Tabungan Siswa</h2>
                <p>Nasabah Panel</p>
            </div>
            <div class="user-welcome">
                <div class="user-avatar-large">
                    <?php echo strtoupper(substr($user['nama_lengkap'] ?? 'U', 0, 1)); ?>
                </div>
                <div class="name"><?php echo htmlspecialchars($user['nama_lengkap'] ?? 'User'); ?></div>
                <div class="role">Nasabah</div>
            </div>
            <div class="sidebar-menu">
                <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="setor_tunai.php"><i class="fas fa-plus-circle"></i> Setor Tunai</a>
                <a href="tarik_tunai.php"><i class="fas fa-minus-circle"></i> Tarik Tunai</a>
                <a href="history_transaksi.php"><i class="fas fa-history"></i> History Transaksi</a>
                <a href="mutasi_rekening.php"><i class="fas fa-file-alt"></i> Mutasi Rekening</a>
                <a href="profil.php" class="active"><i class="fas fa-user"></i> Profil Saya</a>
            </div>
            <div class="sidebar-footer">
                <a href="../logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="content-wrapper">
                <!-- Page Header -->
                <div class="page-header">
                    <div>
                        <h1><i class="fas fa-user"></i> Profil Saya</h1>
                        <div class="breadcrumb">
                            <a href="dashboard.php">Dashboard</a> / Profil Saya
                        </div>
                    </div>
                </div>

                <!-- Alert Messages -->
                <?php if($success_message): ?>
                    <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $success_message; ?></div>
                <?php endif; ?>
                <?php if($error_message): ?>
                    <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?></div>
                <?php endif; ?>

                <!-- Profile Card -->
                <div class="profile-card">
                    <div class="profile-header">
                        <div class="profile-avatar">
                            <?php echo strtoupper(substr($user['nama_lengkap'] ?? 'U', 0, 1)); ?>
                        </div>
                        <h2><?php echo htmlspecialchars($user['nama_lengkap'] ?? 'User'); ?></h2>
                        <p><i class="fas fa-user-graduate"></i> <?php echo htmlspecialchars($user['kelas'] ?? 'Belum mengisi kelas'); ?></p>
                    </div>

                    <div class="profile-body">
                        <!-- Tab Navigation -->
                        <div class="tab-buttons">
                            <button class="tab-btn active" onclick="openTab('profil')">
                                <i class="fas fa-id-card"></i> Data Pribadi
                            </button>
                            <button class="tab-btn" onclick="openTab('password')">
                                <i class="fas fa-lock"></i> Ganti Password
                            </button>
                            <button class="tab-btn" onclick="openTab('statistik')">
                                <i class="fas fa-chart-bar"></i> Statistik
                            </button>
                        </div>

                        <!-- Tab Profil -->
                        <div id="profil" class="tab-content active">
                            <form method="POST">
                                <div class="info-grid">
                                    <div class="info-item">
                                        <div class="label"><i class="fas fa-user"></i> Username</div>
                                        <div class="value"><?php echo htmlspecialchars($user['username'] ?? '-'); ?></div>
                                    </div>
                                    <div class="info-item">
                                        <div class="label"><i class="fas fa-id-card"></i> NIS</div>
                                        <div class="value"><?php echo htmlspecialchars($user['nis'] ?? '-'); ?></div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label><i class="fas fa-user-tag"></i> Nama Lengkap</label>
                                    <input type="text" name="nama_lengkap" class="form-control" value="<?php echo htmlspecialchars($user['nama_lengkap'] ?? ''); ?>" required>
                                </div>

                                <div class="form-group">
                                    <label><i class="fas fa-envelope"></i> Email</label>
                                    <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                                </div>

                                <div class="form-group">
                                    <label><i class="fas fa-phone"></i> No. Telepon</label>
                                    <input type="text" name="no_telepon" class="form-control" value="<?php echo htmlspecialchars($user['no_telepon'] ?? ''); ?>">
                                </div>

                                <div class="form-group">
                                    <label><i class="fas fa-school"></i> Kelas</label>
                                    <input type="text" name="kelas" class="form-control" value="<?php echo htmlspecialchars($user['kelas'] ?? ''); ?>" placeholder="Contoh: X IPA 1">
                                </div>

                                <!-- Field NIS (ditampilkan hanya jika kolom ada) -->
                                <div class="form-group nis-field" style="display: <?php echo mysqli_num_rows(mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'nis'")) > 0 ? 'block' : 'none'; ?>;">
                                    <label><i class="fas fa-id-card"></i> NIS</label>
                                    <input type="text" name="nis" class="form-control" value="<?php echo htmlspecialchars($user['nis'] ?? ''); ?>" placeholder="Nomor Induk Siswa">
                                </div>

                                <div class="form-group">
                                    <label><i class="fas fa-map-marker-alt"></i> Alamat</label>
                                    <textarea name="alamat" class="form-control" rows="3"><?php echo htmlspecialchars($user['alamat'] ?? ''); ?></textarea>
                                </div>

                                <button type="submit" name="update_profil" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Simpan Perubahan
                                </button>
                            </form>
                        </div>

                        <!-- Tab Ganti Password -->
                        <div id="password" class="tab-content">
                            <form method="POST">
                                <div class="form-group">
                                    <label><i class="fas fa-lock"></i> Password Lama</label>
                                    <input type="password" name="password_lama" class="form-control" required>
                                </div>

                                <div class="form-group">
                                    <label><i class="fas fa-lock"></i> Password Baru</label>
                                    <input type="password" name="password_baru" class="form-control" required minlength="6">
                                    <small style="color: #718096;">Minimal 6 karakter</small>
                                </div>

                                <div class="form-group">
                                    <label><i class="fas fa-lock"></i> Konfirmasi Password Baru</label>
                                    <input type="password" name="konfirmasi_password" class="form-control" required>
                                </div>

                                <button type="submit" name="ganti_password" class="btn btn-warning">
                                    <i class="fas fa-key"></i> Ganti Password
                                </button>
                            </form>
                        </div>

                        <!-- Tab Statistik -->
                        <div id="statistik" class="tab-content">
                            <div class="info-grid">
                                <div class="info-item">
                                    <div class="label"><i class="fas fa-calendar-alt"></i> Member Sejak</div>
                                    <div class="value"><?php echo isset($user['created_at']) ? date('d F Y', strtotime($user['created_at'])) : '-'; ?></div>
                                </div>
                                <div class="info-item">
                                    <div class="label"><i class="fas fa-clock"></i> Terakhir Update</div>
                                    <div class="value"><?php echo isset($user['updated_at']) && $user['updated_at'] ? date('d F Y', strtotime($user['updated_at'])) : '-'; ?></div>
                                </div>
                                <div class="info-item">
                                    <div class="label"><i class="fas fa-wallet"></i> Total Saldo</div>
                                    <div class="value">Rp <?php echo number_format($total_saldo, 0, ',', '.'); ?></div>
                                </div>
                                <div class="info-item">
                                    <div class="label"><i class="fas fa-exchange-alt"></i> Total Transaksi</div>
                                    <div class="value"><?php echo $total_trans['total'] ?? 0; ?>x</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    function openTab(tabName) {
        // Sembunyikan semua tab
        var tabs = document.getElementsByClassName('tab-content');
        for(var i = 0; i < tabs.length; i++) {
            tabs[i].classList.remove('active');
        }
        
        // Nonaktifkan semua tombol
        var buttons = document.getElementsByClassName('tab-btn');
        for(var i = 0; i < buttons.length; i++) {
            buttons[i].classList.remove('active');
        }
        
        // Aktifkan tab yang dipilih
        document.getElementById(tabName).classList.add('active');
        event.target.classList.add('active');
    }
    </script>
</body>
</html>
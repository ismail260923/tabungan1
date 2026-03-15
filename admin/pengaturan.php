<?php
// admin/pengaturan.php
include '../config/database.php';

// Cek login dan role admin
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

$success_message = '';
$error_message = '';

// ============================================
// UPDATE PROFIL ADMIN
// ============================================
if(isset($_POST['update_profil'])) {
    $id = $_SESSION['user_id'];
    $nama_lengkap = mysqli_real_escape_string($conn, $_POST['nama_lengkap']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $no_telepon = mysqli_real_escape_string($conn, $_POST['no_telepon']);
    
    $query = "UPDATE users SET 
              nama_lengkap = '$nama_lengkap',
              email = '$email',
              no_telepon = '$no_telepon'
              WHERE id = $id";
    
    if(mysqli_query($conn, $query)) {
        $_SESSION['nama_lengkap'] = $nama_lengkap;
        $success_message = "Profil berhasil diupdate!";
    } else {
        $error_message = "Gagal mengupdate profil: " . mysqli_error($conn);
    }
}

// ============================================
// GANTI PASSWORD
// ============================================
if(isset($_POST['ganti_password'])) {
    $id = $_SESSION['user_id'];
    $password_lama = $_POST['password_lama'];
    $password_baru = $_POST['password_baru'];
    $konfirmasi_password = $_POST['konfirmasi_password'];
    
    // Verifikasi password lama
    $query = mysqli_query($conn, "SELECT password FROM users WHERE id = $id");
    $user = mysqli_fetch_assoc($query);
    
    if(!password_verify($password_lama, $user['password'])) {
        $error_message = "Password lama salah!";
    } elseif($password_baru != $konfirmasi_password) {
        $error_message = "Konfirmasi password tidak cocok!";
    } elseif(strlen($password_baru) < 6) {
        $error_message = "Password minimal 6 karakter!";
    } else {
        $password_hash = password_hash($password_baru, PASSWORD_DEFAULT);
        $update = mysqli_query($conn, "UPDATE users SET password = '$password_hash' WHERE id = $id");
        
        if($update) {
            $success_message = "Password berhasil diganti!";
        } else {
            $error_message = "Gagal mengganti password: " . mysqli_error($conn);
        }
    }
}

// ============================================
// UPDATE PENGATURAN APLIKASI
// ============================================
if(isset($_POST['update_pengaturan'])) {
    $nama_sekolah = mysqli_real_escape_string($conn, $_POST['nama_sekolah']);
    $alamat_sekolah = mysqli_real_escape_string($conn, $_POST['alamat_sekolah']);
    $telepon_sekolah = mysqli_real_escape_string($conn, $_POST['telepon_sekolah']);
    $email_sekolah = mysqli_real_escape_string($conn, $_POST['email_sekolah']);
    $kepala_sekolah = mysqli_real_escape_string($conn, $_POST['kepala_sekolah']);
    $bendahara = mysqli_real_escape_string($conn, $_POST['bendahara']);
    $min_setor = str_replace('.', '', $_POST['min_setor']);
    $max_tarik = str_replace('.', '', $_POST['max_tarik']);
    
    // Simpan ke file config atau database
    // Untuk contoh, kita simpan ke file konfigurasi
    $config_content = "<?php
// Konfigurasi Aplikasi Tabungan Siswa
define('NAMA_SEKOLAH', '$nama_sekolah');
define('ALAMAT_SEKOLAH', '$alamat_sekolah');
define('TELEPON_SEKOLAH', '$telepon_sekolah');
define('EMAIL_SEKOLAH', '$email_sekolah');
define('KEPALA_SEKOLAH', '$kepala_sekolah');
define('BENDAHARA', '$bendahara');
define('MIN_SETOR', $min_setor);
define('MAX_TARIK', $max_tarik);
?>";
    
    if(file_put_contents('../config/pengaturan.php', $config_content)) {
        $success_message = "Pengaturan aplikasi berhasil disimpan!";
    } else {
        $error_message = "Gagal menyimpan pengaturan!";
    }
}

// Ambil data admin
$admin = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id = " . $_SESSION['user_id']));

// Ambil pengaturan (jika file exist)
$min_setor = 5000;
$max_tarik = 500000;
if(file_exists('../config/pengaturan.php')) {
    include '../config/pengaturan.php';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan - Tabungan Siswa</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h2>Tabungan Siswa</h2>
                <p>Admin Panel</p>
            </div>
            <div class="sidebar-menu">
                <a href="dashboard.php">📊 Dashboard</a>
                <a href="kelola_petugas.php">👥 Kelola Petugas</a>
                <a href="kelola_nasabah.php">👤 Kelola Nasabah</a>
                <a href="transaksi.php">💰 Semua Transaksi</a>
                <a href="laporan.php">📈 Laporan</a>
                <a href="pengaturan.php" class="active">⚙️ Pengaturan</a>
                <a href="../logout.php">🚪 Logout</a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <h1>Pengaturan Aplikasi</h1>
                <div class="date"><?php echo date('d F Y'); ?></div>
            </div>

            <!-- Alert Messages -->
            <?php if($success_message): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <?php if($error_message): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <!-- Tab Navigation -->
            <div class="tab-container">
                <div class="tab-buttons">
                    <button class="tab-btn active" onclick="openTab('profil')">👤 Profil Admin</button>
                    <button class="tab-btn" onclick="openTab('password')">🔐 Ganti Password</button>
                    <button class="tab-btn" onclick="openTab('aplikasi')">⚙️ Pengaturan Aplikasi</button>
                    <button class="tab-btn" onclick="openTab('backup')">💾 Backup & Restore</button>
                </div>

                <!-- Tab Profil -->
                <div id="profil" class="tab-content active">
                    <div class="card">
                        <div class="card-header">
                            <h2>Edit Profil Admin</h2>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="form-group">
                                    <label>Nama Lengkap</label>
                                    <input type="text" name="nama_lengkap" value="<?php echo $admin['nama_lengkap']; ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>Username</label>
                                    <input type="text" value="<?php echo $admin['username']; ?>" readonly disabled>
                                    <small>Username tidak dapat diubah</small>
                                </div>
                                <div class="form-group">
                                    <label>Email</label>
                                    <input type="email" name="email" value="<?php echo $admin['email']; ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>No. Telepon</label>
                                    <input type="text" name="no_telepon" value="<?php echo $admin['no_telepon']; ?>">
                                </div>
                                <button type="submit" name="update_profil" class="btn btn-primary">Update Profil</button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Tab Ganti Password -->
                <div id="password" class="tab-content">
                    <div class="card">
                        <div class="card-header">
                            <h2>Ganti Password</h2>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="form-group">
                                    <label>Password Lama</label>
                                    <input type="password" name="password_lama" required>
                                </div>
                                <div class="form-group">
                                    <label>Password Baru</label>
                                    <input type="password" name="password_baru" required minlength="6">
                                    <small>Minimal 6 karakter</small>
                                </div>
                                <div class="form-group">
                                    <label>Konfirmasi Password Baru</label>
                                    <input type="password" name="konfirmasi_password" required>
                                </div>
                                <button type="submit" name="ganti_password" class="btn btn-warning">Ganti Password</button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Tab Pengaturan Aplikasi -->
                <div id="aplikasi" class="tab-content">
                    <div class="card">
                        <div class="card-header">
                            <h2>Pengaturan Aplikasi</h2>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <h3>Informasi Sekolah</h3>
                                <div class="form-group">
                                    <label>Nama Sekolah</label>
                                    <input type="text" name="nama_sekolah" value="<?php echo defined('NAMA_SEKOLAH') ? NAMA_SEKOLAH : 'SMP Negeri 1'; ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>Alamat Sekolah</label>
                                    <textarea name="alamat_sekolah" rows="2"><?php echo defined('ALAMAT_SEKOLAH') ? ALAMAT_SEKOLAH : 'Jl. Pendidikan No. 1'; ?></textarea>
                                </div>
                                <div class="form-group">
                                    <label>Telepon Sekolah</label>
                                    <input type="text" name="telepon_sekolah" value="<?php echo defined('TELEPON_SEKOLAH') ? TELEPON_SEKOLAH : '(021) 1234567'; ?>">
                                </div>
                                <div class="form-group">
                                    <label>Email Sekolah</label>
                                    <input type="email" name="email_sekolah" value="<?php echo defined('EMAIL_SEKOLAH') ? EMAIL_SEKOLAH : 'info@sekolah.sch.id'; ?>">
                                </div>
                                
                                <h3>Pejabat Sekolah</h3>
                                <div class="form-group">
                                    <label>Kepala Sekolah</label>
                                    <input type="text" name="kepala_sekolah" value="<?php echo defined('KEPALA_SEKOLAH') ? KEPALA_SEKOLAH : 'Drs. H. Ahmad, M.Pd'; ?>">
                                </div>
                                <div class="form-group">
                                    <label>Bendahara</label>
                                    <input type="text" name="bendahara" value="<?php echo defined('BENDAHARA') ? BENDAHARA : 'Siti Aminah, S.Pd'; ?>">
                                </div>
                                
                                <h3>Pengaturan Transaksi</h3>
                                <div class="form-group">
                                    <label>Minimal Setoran</label>
                                    <input type="text" name="min_setor" class="format-rupiah" value="<?php echo number_format($min_setor, 0, ',', '.'); ?>" required>
                                    <small>Minimal nominal setoran</small>
                                </div>
                                <div class="form-group">
                                    <label>Maksimal Penarikan per Transaksi</label>
                                    <input type="text" name="max_tarik" class="format-rupiah" value="<?php echo number_format($max_tarik, 0, ',', '.'); ?>" required>
                                    <small>Maksimal nominal penarikan sekali transaksi</small>
                                </div>
                                
                                <button type="submit" name="update_pengaturan" class="btn btn-primary">Simpan Pengaturan</button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Tab Backup & Restore -->
                <div id="backup" class="tab-content">
                    <div class="card">
                        <div class="card-header">
                            <h2>Backup & Restore Database</h2>
                        </div>
                        <div class="card-body">
                            <div class="backup-info">
                                <p>📊 Ukuran Database: <span id="dbSize">Mengecek...</span></p>
                                <p>📅 Backup Terakhir: <span id="lastBackup">Belum pernah</span></p>
                            </div>
                            
                            <div class="backup-actions">
                                <button class="btn btn-success" onclick="backupDatabase()">
                                    💾 Backup Database
                                </button>
                                <button class="btn btn-info" onclick="restoreDatabase()">
                                    🔄 Restore Database
                                </button>
                                <button class="btn btn-warning" onclick="optimizeDatabase()">
                                    ⚡ Optimasi Database
                                </button>
                            </div>
                            
                            <hr>
                
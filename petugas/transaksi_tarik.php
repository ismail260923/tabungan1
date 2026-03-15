<?php
include '../config/database.php';

// Cek login dan role petugas
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'petugas') {
    header("Location: ../login.php");
    exit();
}

$success_message = '';
$error_message = '';

// Proses transaksi tarik
if(isset($_POST['simpan_tarik'])) {
    $nasabah_id = $_POST['nasabah_id'];
    $jumlah = str_replace('.', '', $_POST['jumlah']);
    $keterangan = mysqli_real_escape_string($conn, $_POST['keterangan']);
    $petugas_id = $_SESSION['user_id'];
    
    // Validasi jumlah minimal
    if($jumlah < 5000) {
        $error_message = "Minimal penarikan adalah Rp 5.000!";
    } else {
        // Cek saldo cukup
        $cek_saldo = mysqli_fetch_assoc(mysqli_query($conn, "SELECT total_saldo FROM saldo WHERE user_id = $nasabah_id"));
        if($cek_saldo['total_saldo'] < $jumlah) {
            $error_message = "Saldo tidak mencukupi! Saldo tersedia: Rp " . number_format($cek_saldo['total_saldo'], 0, ',', '.');
        } else {
            // Mulai transaksi
            mysqli_begin_transaction($conn);
            
            try {
                // Simpan ke tabel tabungan
                $query_tabungan = "INSERT INTO tabungan (user_id, jenis_transaksi, jumlah, keterangan) 
                                   VALUES ('$nasabah_id', 'tarik', '$jumlah', '$keterangan')";
                if(!mysqli_query($conn, $query_tabungan)) {
                    throw new Exception("Gagal menyimpan transaksi");
                }
                
                // Update saldo
                $query_saldo = "UPDATE saldo SET total_saldo = total_saldo - $jumlah WHERE user_id = '$nasabah_id'";
                if(!mysqli_query($conn, $query_saldo)) {
                    throw new Exception("Gagal mengupdate saldo");
                }
                
                mysqli_commit($conn);
                $success_message = "Transaksi tarik tunai berhasil!";
            } catch (Exception $e) {
                mysqli_rollback($conn);
                $error_message = "Transaksi gagal: " . $e->getMessage();
            }
        }
    }
}

// Ambil data nasabah jika ada ID
$selected_nasabah = null;
if(isset($_GET['id'])) {
    $id = $_GET['id'];
    $query = mysqli_query($conn, "SELECT u.*, s.total_saldo 
                                   FROM users u 
                                   LEFT JOIN saldo s ON u.id = s.user_id 
                                   WHERE u.id = $id AND u.role = 'nasabah'");
    if($query && mysqli_num_rows($query) > 0) {
        $selected_nasabah = mysqli_fetch_assoc($query);
    }
}

// Ambil semua nasabah untuk dropdown
$nasabah = mysqli_query($conn, "SELECT u.*, s.total_saldo 
                                 FROM users u 
                                 LEFT JOIN saldo s ON u.id = s.user_id 
                                 WHERE u.role = 'nasabah' AND u.status = 'aktif'
                                 ORDER BY u.nama_lengkap");

// Ambil riwayat transaksi tarik hari ini
$hari_ini = date('Y-m-d');
$riwayat_hari_ini = mysqli_query($conn, "
    SELECT t.*, u.nama_lengkap, u.username, u.kelas
    FROM tabungan t 
    JOIN users u ON t.user_id = u.id 
    WHERE DATE(t.tanggal) = '$hari_ini' AND t.jenis_transaksi = 'tarik'
    ORDER BY t.tanggal DESC 
    LIMIT 10
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaksi Tarik - Tabungan Siswa</title>
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

        .petugas-wrapper {
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
            background: #ed8936;
            color: white;
            border-left: 4px solid #48bb78;
        }

        .sidebar-menu a i {
            width: 20px;
            font-size: 1.2em;
        }

        .sidebar-footer {
            padding: 20px 25px;
            background: #1a202c;
            position: absolute;
            bottom: 0;
            width: 100%;
            border-top: 1px solid #4a5568;
        }

        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 30px;
            min-height: 100vh;
        }

        .content-wrapper {
            max-width: 1400px;
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
            color: #ed8936;
        }

        .breadcrumb {
            color: #718096;
            font-size: 0.95em;
        }

        .breadcrumb a {
            color: #667eea;
            text-decoration: none;
        }

        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
            margin-bottom: 30px;
        }

        @media (max-width: 1024px) {
            .grid-2 {
                grid-template-columns: 1fr;
            }
        }

        .card {
            background: white;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }

        .card-header h2 {
            color: #2d3748;
            font-size: 1.3em;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-header h2 i {
            color: #ed8936;
        }

        .badge {
            background: #ed8936;
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.9em;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #4a5568;
            font-weight: 600;
            font-size: 0.95em;
        }

        .form-group label i {
            color: #ed8936;
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
            border-color: #ed8936;
            box-shadow: 0 0 0 3px rgba(237, 137, 54, 0.1);
        }

        .input-group {
            display: flex;
            align-items: center;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            overflow: hidden;
        }

        .input-group-prepend {
            background: #f7fafc;
            padding: 12px 15px;
            color: #4a5568;
            font-weight: 600;
            border-right: 2px solid #e2e8f0;
        }

        .input-group .form-control {
            border: none;
            border-radius: 0;
        }

        .info-saldo {
            background: linear-gradient(135deg, #fff5f0 0%, #ffe9e0 100%);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 25px;
            border: 1px solid #fed7d0;
        }

        .info-saldo h3 {
            color: #c05621;
            margin-bottom: 10px;
            font-size: 1.1em;
        }

        .saldo-nominal {
            font-size: 2em;
            font-weight: bold;
            color: #ed8936;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .saldo-nominal small {
            font-size: 0.5em;
            color: #718096;
            font-weight: normal;
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

        .btn-warning {
            background: #ed8936;
            color: white;
        }

        .btn-warning:hover {
            background: #dd6b20;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(237, 137, 54, 0.4);
        }

        .btn-block {
            width: 100%;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 25px;
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

        .table-responsive {
            overflow-x: auto;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th {
            text-align: left;
            padding: 15px 10px;
            background: #f7fafc;
            color: #4a5568;
            font-weight: 600;
        }

        .table td {
            padding: 12px 10px;
            border-bottom: 1px solid #e2e8f0;
            color: #2d3748;
        }

        .badge-warning {
            background: #feebc8;
            color: #744210;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.85em;
        }

        .amount {
            font-weight: 600;
            color: #ed8936;
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #a0aec0;
        }

        .text-muted {
            color: #718096;
            font-size: 0.9em;
            margin-top: 5px;
        }

        .stat-card-small {
            background: #f7fafc;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .stat-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            text-align: center;
        }

        .stat-number {
            font-size: 2em;
            font-weight: bold;
            color: #ed8936;
        }

        .stat-label {
            color: #718096;
            font-size: 0.9em;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 0;
                display: none;
            }
            
            .main-content {
                margin-left: 0;
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="petugas-wrapper">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h2>Tabungan Siswa</h2>
                <p>Petugas Panel</p>
            </div>
            <div class="sidebar-menu">
                <a href="dashboard.php">
                    <i class="fas fa-tachometer-alt"></i>
                    Dashboard
                </a>
                <a href="transaksi_setor.php">
                    <i class="fas fa-plus-circle"></i>
                    Transaksi Setor
                </a>
                <a href="transaksi_tarik.php" class="active">
                    <i class="fas fa-minus-circle"></i>
                    Transaksi Tarik
                </a>
                <a href="riwayat_transaksi.php">
                    <i class="fas fa-history"></i>
                    Riwayat Transaksi
                </a>
                <a href="cari_nasabah.php">
                    <i class="fas fa-search"></i>
                    Cari Nasabah
                </a>
                <a href="laporan_harian.php">
                    <i class="fas fa-file-alt"></i>
                    Laporan Harian
                </a>
            </div>
            <div class="sidebar-footer">
                <p>Login sebagai:</p>
                <strong><?php echo $_SESSION['nama_lengkap']; ?></strong>
                <p style="margin-top: 10px;">
                    <a href="../logout.php" style="color: #fc8181; text-decoration: none;">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </p>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="content-wrapper">
                <!-- Page Header -->
                <div class="page-header">
                    <div>
                        <h1>
                            <i class="fas fa-minus-circle"></i>
                            Transaksi Tarik Tunai
                        </h1>
                        <div class="breadcrumb">
                            <a href="dashboard.php">Dashboard</a> / 
                            <span>Transaksi Tarik</span>
                        </div>
                    </div>
                    <div class="date">
                        <i class="fas fa-calendar-alt"></i> 
                        <?php echo date('l, d F Y'); ?> | 
                        <i class="fas fa-clock"></i> 
                        <?php echo date('H:i'); ?> WIB
                    </div>
                </div>

                <!-- Alert Messages -->
                <?php if($success_message): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $success_message; ?>
                </div>
                <?php endif; ?>
                
                <?php if($error_message): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error_message; ?>
                </div>
                <?php endif; ?>

                <!-- Grid 2 Kolom -->
                <div class="grid-2">
                    <!-- Form Tarik -->
                    <div class="card">
                        <div class="card-header">
                            <h2>
                                <i class="fas fa-edit"></i>
                                Form Tarik Tunai
                            </h2>
                            <span class="badge">Petugas: <?php echo $_SESSION['nama_lengkap']; ?></span>
                        </div>
                        <div class="form-container">
                            <form method="POST" id="formTarik" onsubmit="return validateForm()">
                                <div class="form-group">
                                    <label>
                                        <i class="fas fa-user-graduate"></i>
                                        Pilih Nasabah <span style="color: #f56565;">*</span>
                                    </label>
                                    <select name="nasabah_id" id="nasabah_id" class="form-control" required onchange="updateInfoNasabah()">
                                        <option value="">-- Pilih Nasabah --</option>
                                        <?php 
                                        mysqli_data_seek($nasabah, 0);
                                        while($row = mysqli_fetch_assoc($nasabah)): 
                                        ?>
                                        <option value="<?php echo $row['id']; ?>" 
                                                data-nama="<?php echo $row['nama_lengkap']; ?>"
                                                data-saldo="<?php echo $row['total_saldo']; ?>"
                                                data-kelas="<?php echo $row['kelas']; ?>"
                                                <?php echo ($selected_nasabah && $selected_nasabah['id'] == $row['id']) ? 'selected' : ''; ?>>
                                            <?php echo $row['nama_lengkap']; ?> (<?php echo $row['kelas'] ?: 'Tanpa Kelas'; ?>) - 
                                            Saldo: Rp <?php echo number_format($row['total_saldo'], 0, ',', '.'); ?>
                                        </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>

                                <div id="infoNasabah" class="info-saldo" style="<?php echo $selected_nasabah ? 'display:block' : 'display:none'; ?>">
                                    <h3>
                                        <i class="fas fa-info-circle"></i>
                                        Informasi Nasabah
                                    </h3>
                                    <div style="display: grid; gap: 10px;">
                                        <div>
                                            <span style="color: #718096;">Nama:</span>
                                            <strong id="infoNama"><?php echo $selected_nasabah['nama_lengkap'] ?? ''; ?></strong>
                                        </div>
                                        <div>
                                            <span style="color: #718096;">Kelas:</span>
                                            <strong id="infoKelas"><?php echo $selected_nasabah['kelas'] ?? '-'; ?></strong>
                                        </div>
                                        <div>
                                            <span style="color: #718096;">Saldo Tersedia:</span>
                                            <div class="saldo-nominal">
                                                Rp <span id="infoSaldo"><?php echo number_format($selected_nasabah['total_saldo'] ?? 0, 0, ',', '.'); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>
                                        <i class="fas fa-money-bill-wave"></i>
                                        Jumlah Penarikan <span style="color: #f56565;">*</span>
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-prepend">Rp</span>
                                        <input type="text" id="jumlah" name="jumlah" class="form-control format-rupiah" 
                                               placeholder="0" required autocomplete="off">
                                    </div>
                                    <div class="text-muted">
                                        <i class="fas fa-info-circle"></i>
                                        Minimal tarik Rp 5.000, maksimal sesuai saldo
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>
                                        <i class="fas fa-sticky-note"></i>
                                        Keterangan (Opsional)
                                    </label>
                                    <textarea name="keterangan" class="form-control" 
                                              placeholder="Contoh: Penarikan untuk keperluan sekolah, dll."><?php echo isset($_POST['keterangan']) ? htmlspecialchars($_POST['keterangan']) : ''; ?></textarea>
                                </div>

                                <button type="submit" name="simpan_tarik" class="btn btn-warning btn-block">
                                    <i class="fas fa-save"></i>
                                    Proses Penarikan
                                </button>

                                <div style="margin-top: 20px; padding: 15px; background: #fff5f0; border-radius: 10px; border: 1px solid #fed7d0;">
                                    <i class="fas fa-exclamation-triangle" style="color: #ed8936;"></i>
                                    <small style="color: #c05621;">
                                        <strong>Perhatian:</strong> Pastikan saldo nasabah mencukupi sebelum melakukan penarikan.
                                    </small>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Informasi & Panduan -->
                    <div class="card">
                        <div class="card-header">
                            <h2>
                                <i class="fas fa-info-circle"></i>
                                Informasi & Panduan
                            </h2>
                        </div>
                        <div>
                            <?php
                            $stat_tarik = mysqli_fetch_assoc(mysqli_query($conn, "
                                SELECT COUNT(*) as jumlah, COALESCE(SUM(jumlah), 0) as total 
                                FROM tabungan 
                                WHERE DATE(tanggal) = '$hari_ini' AND jenis_transaksi = 'tarik'
                            "));
                            ?>
                            <div class="stat-card-small">
                                <h3 style="margin-bottom: 15px;">Statistik Tarik Hari Ini</h3>
                                <div class="stat-grid">
                                    <div>
                                        <div class="stat-number"><?php echo $stat_tarik['jumlah'] ?: 0; ?></div>
                                        <div class="stat-label">Transaksi Tarik</div>
                                    </div>
                                    <div>
                                        <div class="stat-number">Rp <?php echo number_format($stat_tarik['total'] ?: 0, 0, ',', '.'); ?></div>
                                        <div class="stat-label">Total Penarikan</div>
                                    </div>
                                </div>
                            </div>

                            <h3 style="color: #2d3748; margin: 20px 0 15px; font-size: 1.1em;">
                                <i class="fas fa-list"></i>
                                Panduan Transaksi
                            </h3>
                            <ul style="list-style: none; padding: 0;">
                                <li style="margin-bottom: 12px; display: flex; align-items: center; gap: 10px;">
                                    <span style="background: #ed8936; color: white; width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.9em;">1</span>
                                    <span>Pilih nasabah yang akan melakukan penarikan</span>
                                </li>
                                <li style="margin-bottom: 12px; display: flex; align-items: center; gap: 10px;">
                                    <span style="background: #ed8936; color: white; width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.9em;">2</span>
                                    <span>Cek saldo nasabah (harus mencukupi)</span>
                                </li>
                                <li style="margin-bottom: 12px; display: flex; align-items: center; gap: 10px;">
                                    <span style="background: #ed8936; color: white; width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.9em;">3</span>
                                    <span>Masukkan jumlah penarikan (minimal Rp 5.000)</span>
                                </li>
                                <li style="margin-bottom: 12px; display: flex; align-items: center; gap: 10px;">
                                    <span style="background: #ed8936; color: white; width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.9em;">4</span>
                                    <span>Klik "Proses Penarikan" untuk menyimpan</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Riwayat Tarik Hari Ini -->
                <div class="card" style="margin-top: 25px;">
                    <div class="card-header">
                        <h2>
                            <i class="fas fa-history"></i>
                            Riwayat Penarikan Hari Ini
                        </h2>
                        <a href="riwayat_transaksi.php?jenis=tarik" style="color: #667eea; text-decoration: none;">
                            Lihat Semua <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Waktu</th>
                                    <th>Nasabah</th>
                                    <th>Kelas</th>
                                    <th>Jumlah</th>
                                    <th>Keterangan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if($riwayat_hari_ini && mysqli_num_rows($riwayat_hari_ini) > 0): ?>
                                    <?php while($row = mysqli_fetch_assoc($riwayat_hari_ini)): ?>
                                    <tr>
                                        <td><?php echo date('H:i', strtotime($row['tanggal'])); ?></td>
                                        <td>
                                            <strong><?php echo $row['nama_lengkap']; ?></strong><br>
                                            <small><?php echo $row['username']; ?></small>
                                        </td>
                                        <td><?php echo $row['kelas'] ?: '-'; ?></td>
                                        <td class="amount">Rp <?php echo number_format($row['jumlah'], 0, ',', '.'); ?></td>
                                        <td><?php echo $row['keterangan'] ?: '-'; ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="empty-state">
                                            <i class="fas fa-inbox"></i>
                                            <p>Belum ada transaksi penarikan hari ini</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    document.querySelectorAll('.format-rupiah').forEach(input => {
        input.addEventListener('keyup', function(e) {
            let value = this.value.replace(/\D/g, '');
            this.value = value.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        });
    });

    function updateInfoNasabah() {
        const select = document.getElementById('nasabah_id');
        const selected = select.options[select.selectedIndex];
        
        if(selected.value) {
            const nama = selected.getAttribute('data-nama');
            const kelas = selected.getAttribute('data-kelas');
            const saldo = selected.getAttribute('data-saldo');
            
            document.getElementById('infoNama').innerHTML = nama;
            document.getElementById('infoKelas').innerHTML = kelas || '-';
            document.getElementById('infoSaldo').innerHTML = new Intl.NumberFormat('id-ID').format(saldo);
            document.getElementById('infoNasabah').style.display = 'block';
        } else {
            document.getElementById('infoNasabah').style.display = 'none';
        }
    }

    function validateForm() {
        const nasabah = document.getElementById('nasabah_id').value;
        const jumlah = document.getElementById('jumlah').value.replace(/\./g, '');
        const saldo = document.getElementById('infoSaldo').innerHTML.replace(/\./g, '');
        
        if(!nasabah) {
            alert('Silakan pilih nasabah terlebih dahulu!');
            return false;
        }
        
        if(!jumlah || parseInt(jumlah) < 5000) {
            alert('Jumlah penarikan minimal Rp 5.000!');
            return false;
        }
        
        if(parseInt(jumlah) > parseInt(saldo)) {
            alert('Saldo tidak mencukupi!');
            return false;
        }
        
        return confirm('Pastikan data sudah benar. Lanjutkan transaksi penarikan?');
    }

    window.onload = function() {
        if(document.getElementById('nasabah_id').value) {
            updateInfoNasabah();
        }
    }
    </script>
</body>
</html>
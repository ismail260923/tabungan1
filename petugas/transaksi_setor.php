<?php
include '../config/database.php';

// Cek login dan role petugas
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'petugas') {
    header("Location: ../login.php");
    exit();
}

$success_message = '';
$error_message = '';

// Proses transaksi setor
if(isset($_POST['simpan_setor'])) {
    $nasabah_id = $_POST['nasabah_id'];
    $jumlah = str_replace('.', '', $_POST['jumlah']);
    $keterangan = mysqli_real_escape_string($conn, $_POST['keterangan']);
    $petugas_id = $_SESSION['user_id'];
    
    // Validasi jumlah minimal
    if($jumlah < 5000) {
        $error_message = "Minimal setoran adalah Rp 5.000!";
    } else {
        // Mulai transaksi
        mysqli_begin_transaction($conn);
        
        try {
            // Simpan ke tabel tabungan
            $query_tabungan = "INSERT INTO tabungan (user_id, jenis_transaksi, jumlah, keterangan) 
                               VALUES ('$nasabah_id', 'setor', '$jumlah', '$keterangan')";
            if(!mysqli_query($conn, $query_tabungan)) {
                throw new Exception("Gagal menyimpan transaksi");
            }
            
            // Update saldo
            $query_saldo = "UPDATE saldo SET total_saldo = total_saldo + $jumlah WHERE user_id = '$nasabah_id'";
            if(!mysqli_query($conn, $query_saldo)) {
                throw new Exception("Gagal mengupdate saldo");
            }
            
            mysqli_commit($conn);
            $success_message = "Transaksi setor tunai berhasil!";
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $error_message = "Transaksi gagal: " . $e->getMessage();
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

// Ambil riwayat transaksi hari ini
$hari_ini = date('Y-m-d');
$riwayat_hari_ini = mysqli_query($conn, "
    SELECT t.*, u.nama_lengkap, u.username 
    FROM tabungan t 
    JOIN users u ON t.user_id = u.id 
    WHERE DATE(t.tanggal) = '$hari_ini' AND t.jenis_transaksi = 'setor'
    ORDER BY t.tanggal DESC 
    LIMIT 10
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaksi Setor - Tabungan Siswa</title>
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

        /* Layout dengan sidebar fixed */
        .petugas-wrapper {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar styling */
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
            background: #667eea;
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

        .sidebar-footer p {
            color: #a0aec0;
            font-size: 0.9em;
        }

        .sidebar-footer strong {
            color: white;
            display: block;
            margin-top: 5px;
        }

        /* Main content dengan margin kiri */
        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 30px;
            min-height: 100vh;
        }

        /* Content wrapper untuk padding */
        .content-wrapper {
            max-width: 1400px;
            margin: 0 auto;
        }

        /* Header */
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

        .breadcrumb a {
            color: #667eea;
            text-decoration: none;
        }

        .breadcrumb a:hover {
            text-decoration: underline;
        }

        /* Grid layout */
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

        /* Cards */
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
            color: #667eea;
        }

        .badge {
            background: #48bb78;
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.9em;
        }

        /* Form styling */
        .form-container {
            padding: 10px 0;
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
            color: #667eea;
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
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        select.form-control {
            cursor: pointer;
            background: white;
        }

        textarea.form-control {
            resize: vertical;
            min-height: 100px;
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

        .input-group .form-control:focus {
            box-shadow: none;
        }

        .info-saldo {
            background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 25px;
            border: 1px solid #e2e8f0;
        }

        .info-saldo h3 {
            color: #2d3748;
            margin-bottom: 10px;
            font-size: 1.1em;
        }

        .saldo-nominal {
            font-size: 2em;
            font-weight: bold;
            color: #48bb78;
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

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background: #5a67d8;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-success {
            background: #48bb78;
            color: white;
        }

        .btn-success:hover {
            background: #38a169;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(72, 187, 120, 0.4);
        }

        .btn-secondary {
            background: #a0aec0;
            color: white;
        }

        .btn-secondary:hover {
            background: #718096;
            transform: translateY(-2px);
        }

        .btn-block {
            width: 100%;
        }

        /* Alert messages */
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

        .alert i {
            font-size: 1.2em;
        }

        /* Table styling */
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
            font-size: 0.95em;
        }

        .table td {
            padding: 12px 10px;
            border-bottom: 1px solid #e2e8f0;
            color: #2d3748;
        }

        .table tr:hover td {
            background: #f7fafc;
        }

        .badge-success {
            background: #c6f6d5;
            color: #22543d;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.85em;
        }

        .amount {
            font-weight: 600;
            color: #48bb78;
        }

        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #a0aec0;
        }

        .empty-state i {
            font-size: 3em;
            margin-bottom: 15px;
        }

        /* Info kecil */
        .text-muted {
            color: #718096;
            font-size: 0.9em;
            margin-top: 5px;
        }

        .text-info {
            color: #4299e1;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                width: 0;
                display: none;
            }
            
            .main-content {
                margin-left: 0;
                padding: 15px;
            }
            
            .page-header {
                flex-direction: column;
                text-align: center;
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
                <a href="transaksi_setor.php" class="active">
                    <i class="fas fa-plus-circle"></i>
                    Transaksi Setor
                </a>
                <a href="transaksi_tarik.php">
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
                            <i class="fas fa-plus-circle"></i>
                            Transaksi Setor Tunai
                        </h1>
                        <div class="breadcrumb">
                            <a href="dashboard.php">Dashboard</a> / 
                            <span>Transaksi Setor</span>
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
                    <!-- Form Setor -->
                    <div class="card">
                        <div class="card-header">
                            <h2>
                                <i class="fas fa-edit"></i>
                                Form Setor Tunai
                            </h2>
                            <span class="badge">Petugas: <?php echo $_SESSION['nama_lengkap']; ?></span>
                        </div>
                        <div class="form-container">
                            <form method="POST" id="formSetor" onsubmit="return validateForm()">
                                <!-- Pilih Nasabah -->
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

                                <!-- Info Nasabah (akan tampil setelah pilih nasabah) -->
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
                                            <span style="color: #718096;">Saldo Saat Ini:</span>
                                            <div class="saldo-nominal">
                                                Rp <span id="infoSaldo"><?php echo number_format($selected_nasabah['total_saldo'] ?? 0, 0, ',', '.'); ?></span>
                                                <small>(sebelum setor)</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Jumlah Setoran -->
                                <div class="form-group">
                                    <label>
                                        <i class="fas fa-money-bill-wave"></i>
                                        Jumlah Setoran <span style="color: #f56565;">*</span>
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-prepend">Rp</span>
                                        <input type="text" id="jumlah" name="jumlah" class="form-control format-rupiah" 
                                               placeholder="0" required autocomplete="off">
                                    </div>
                                    <div class="text-muted">
                                        <i class="fas fa-info-circle"></i>
                                        Minimal setor Rp 5.000
                                    </div>
                                </div>

                                <!-- Keterangan -->
                                <div class="form-group">
                                    <label>
                                        <i class="fas fa-sticky-note"></i>
                                        Keterangan (Opsional)
                                    </label>
                                    <textarea name="keterangan" class="form-control" 
                                              placeholder="Contoh: Setoran tabungan, Uang saku, Hadiah, dll."><?php echo isset($_POST['keterangan']) ? htmlspecialchars($_POST['keterangan']) : ''; ?></textarea>
                                </div>

                                <!-- Tombol Submit -->
                                <button type="submit" name="simpan_setor" class="btn btn-success btn-block">
                                    <i class="fas fa-save"></i>
                                    Proses Setoran
                                </button>

                                <!-- Info Tambahan -->
                                <div style="margin-top: 20px; padding: 15px; background: #f0f9ff; border-radius: 10px; border: 1px solid #b6e0fe;">
                                    <i class="fas fa-lightbulb text-info"></i>
                                    <small class="text-info">
                                        Pastikan data nasabah dan jumlah setoran sudah benar sebelum menyimpan.
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
                        <div style="padding: 10px 0;">
                            <!-- Statistik Cepat -->
                            <div style="background: #f7fafc; border-radius: 15px; padding: 20px; margin-bottom: 20px;">
                                <h3 style="color: #2d3748; margin-bottom: 15px; font-size: 1.1em;">
                                    <i class="fas fa-chart-simple"></i>
                                    Statistik Hari Ini
                                </h3>
                                <?php
                                $stat_setor = mysqli_fetch_assoc(mysqli_query($conn, "
                                    SELECT COUNT(*) as jumlah, COALESCE(SUM(jumlah), 0) as total 
                                    FROM tabungan 
                                    WHERE DATE(tanggal) = '$hari_ini' AND jenis_transaksi = 'setor'
                                "));
                                ?>
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                                    <div style="text-align: center;">
                                        <div style="font-size: 2em; color: #48bb78; font-weight: bold;">
                                            <?php echo $stat_setor['jumlah'] ?: 0; ?>
                                        </div>
                                        <div style="color: #718096;">Transaksi Setor</div>
                                    </div>
                                    <div style="text-align: center;">
                                        <div style="font-size: 1.5em; color: #667eea; font-weight: bold;">
                                            Rp <?php echo number_format($stat_setor['total'] ?: 0, 0, ',', '.'); ?>
                                        </div>
                                        <div style="color: #718096;">Total Setoran</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Panduan -->
                            <h3 style="color: #2d3748; margin-bottom: 15px; font-size: 1.1em;">
                                <i class="fas fa-list"></i>
                                Panduan Transaksi
                            </h3>
                            <ul style="list-style: none; padding: 0;">
                                <li style="margin-bottom: 12px; display: flex; align-items: center; gap: 10px;">
                                    <span style="background: #48bb78; color: white; width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.9em;">1</span>
                                    <span>Pilih nasabah yang akan melakukan setoran</span>
                                </li>
                                <li style="margin-bottom: 12px; display: flex; align-items: center; gap: 10px;">
                                    <span style="background: #48bb78; color: white; width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.9em;">2</span>
                                    <span>Masukkan jumlah setoran (minimal Rp 5.000)</span>
                                </li>
                                <li style="margin-bottom: 12px; display: flex; align-items: center; gap: 10px;">
                                    <span style="background: #48bb78; color: white; width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.9em;">3</span>
                                    <span>Tambah keterangan jika diperlukan</span>
                                </li>
                                <li style="margin-bottom: 12px; display: flex; align-items: center; gap: 10px;">
                                    <span style="background: #48bb78; color: white; width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.9em;">4</span>
                                    <span>Klik "Proses Setoran" untuk menyimpan</span>
                                </li>
                            </ul>

                            <!-- Info Penting -->
                            <div style="margin-top: 20px; padding: 15px; background: #fffff0; border-radius: 10px; border: 1px solid #faf089;">
                                <i class="fas fa-exclamation-triangle" style="color: #ed8936;"></i>
                                <small style="color: #744210;">
                                    <strong>Perhatian:</strong> Setoran yang sudah diproses tidak dapat dibatalkan. Pastikan data yang dimasukkan sudah benar.
                                </small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Riwayat Setor Hari Ini -->
                <div class="card" style="margin-top: 25px;">
                    <div class="card-header">
                        <h2>
                            <i class="fas fa-history"></i>
                            Riwayat Setor Hari Ini
                        </h2>
                        <a href="riwayat_transaksi.php" style="color: #667eea; text-decoration: none;">
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
                                            <p>Belum ada transaksi setor hari ini</p>
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
    // Format Rupiah
    document.querySelectorAll('.format-rupiah').forEach(input => {
        input.addEventListener('keyup', function(e) {
            let value = this.value.replace(/\D/g, '');
            this.value = value.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        });
    });

    // Update info nasabah saat memilih
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

    // Validasi form
    function validateForm() {
        const nasabah = document.getElementById('nasabah_id').value;
        const jumlah = document.getElementById('jumlah').value.replace(/\./g, '');
        
        if(!nasabah) {
            alert('Silakan pilih nasabah terlebih dahulu!');
            return false;
        }
        
        if(!jumlah || parseInt(jumlah) < 5000) {
            alert('Jumlah setoran minimal Rp 5.000!');
            return false;
        }
        
        return confirm('Pastikan data sudah benar. Lanjutkan transaksi?');
    }

    // Trigger change jika ada selected nasabah dari URL
    window.onload = function() {
        if(document.getElementById('nasabah_id').value) {
            updateInfoNasabah();
        }
    }
    </script>
</body>
</html>
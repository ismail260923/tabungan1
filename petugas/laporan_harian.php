<?php
include '../config/database.php';

// Cek login dan role petugas
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'petugas') {
    header("Location: ../login.php");
    exit();
}

// Tanggal yang dipilih
$tanggal = isset($_GET['tanggal']) ? $_GET['tanggal'] : date('Y-m-d');

// Statistik harian
$statistik = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT 
        COUNT(*) as total_transaksi,
        COALESCE(SUM(CASE WHEN jenis_transaksi = 'setor' THEN jumlah ELSE 0 END), 0) as total_setor,
        COALESCE(SUM(CASE WHEN jenis_transaksi = 'tarik' THEN jumlah ELSE 0 END), 0) as total_tarik,
        COUNT(CASE WHEN jenis_transaksi = 'setor' THEN 1 END) as jumlah_setor,
        COUNT(CASE WHEN jenis_transaksi = 'tarik' THEN 1 END) as jumlah_tarik
    FROM tabungan 
    WHERE DATE(tanggal) = '$tanggal'
"));

// Detail transaksi
$transaksi = mysqli_query($conn, "
    SELECT t.*, u.nama_lengkap, u.username, u.kelas 
    FROM tabungan t 
    JOIN users u ON t.user_id = u.id 
    WHERE DATE(t.tanggal) = '$tanggal' 
    ORDER BY t.tanggal DESC
");

// 10 nasabah aktif hari ini
$nasabah_aktif = mysqli_query($conn, "
    SELECT u.nama_lengkap, u.username, u.kelas, 
           COUNT(t.id) as jumlah_transaksi,
           SUM(CASE WHEN t.jenis_transaksi = 'setor' THEN t.jumlah ELSE 0 END) as total_setor,
           SUM(CASE WHEN t.jenis_transaksi = 'tarik' THEN t.jumlah ELSE 0 END) as total_tarik
    FROM users u
    JOIN tabungan t ON u.id = t.user_id
    WHERE DATE(t.tanggal) = '$tanggal'
    GROUP BY u.id
    ORDER BY jumlah_transaksi DESC
    LIMIT 10
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Harian - Tabungan Siswa</title>
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
            background: #48bb78;
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

        .date-picker {
            background: white;
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 20px;
        }

        .date-form {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .date-input {
            padding: 12px 20px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 1em;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 20px;
            transition: all 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }

        .stat-icon {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2em;
        }

        .stat-icon.total {
            background: #e6f0ff;
            color: #4299e1;
        }

        .stat-icon.setor {
            background: #c6f6d5;
            color: #48bb78;
        }

        .stat-icon.tarik {
            background: #feebc8;
            color: #ed8936;
        }

        .stat-content h3 {
            font-size: 2em;
            color: #2d3748;
            margin-bottom: 5px;
        }

        .stat-content p {
            color: #718096;
            margin-bottom: 5px;
        }

        .stat-detail {
            font-size: 0.9em;
            color: #a0aec0;
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

        .btn-group {
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 10px;
            font-size: 0.95em;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        .btn-primary {
            background: #4299e1;
            color: white;
        }

        .btn-primary:hover {
            background: #3182ce;
        }

        .btn-success {
            background: #48bb78;
            color: white;
        }

        .btn-success:hover {
            background: #38a169;
        }

        .btn-warning {
            background: #ed8936;
            color: white;
        }

        .btn-warning:hover {
            background: #dd6b20;
        }

        .table-responsive {
            overflow-x: auto;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th {
            background: #f7fafc;
            padding: 15px 10px;
            text-align: left;
            color: #4a5568;
            font-weight: 600;
        }

        .table td {
            padding: 12px 10px;
            border-bottom: 1px solid #e2e8f0;
        }

        .badge-success {
            background: #c6f6d5;
            color: #22543d;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.85em;
        }

        .badge-warning {
            background: #feebc8;
            color: #744210;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.85em;
        }

        .amount-setor {
            color: #48bb78;
            font-weight: 600;
        }

        .amount-tarik {
            color: #ed8936;
            font-weight: 600;
        }

        .signature-area {
            margin-top: 40px;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 30px;
            text-align: center;
        }

        .signature-box {
            padding: 20px;
        }

        .signature-line {
            margin-top: 50px;
            border-top: 1px dashed #a0aec0;
            padding-top: 10px;
        }

        .print-only {
            display: none;
        }

        @media print {
            .sidebar, .page-header .btn-group, .date-picker .btn {
                display: none;
            }
            
            .main-content {
                margin-left: 0;
                padding: 0;
            }
            
            .print-only {
                display: block;
            }
            
            .card {
                box-shadow: none;
                border: 1px solid #e2e8f0;
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                display: none;
            }
            
            .main-content {
                margin-left: 0;
                padding: 15px;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .signature-area {
                grid-template-columns: 1fr;
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
                <a href="laporan_harian.php" class="active">
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
                            <i class="fas fa-file-alt"></i>
                            Laporan Harian
                        </h1>
                        <div class="breadcrumb">
                            <a href="dashboard.php">Dashboard</a> / 
                            <span>Laporan Harian</span>
                        </div>
                    </div>
                    <div class="btn-group">
                        <button onclick="window.print()" class="btn btn-primary">
                            <i class="fas fa-print"></i> Cetak
                        </button>
                        <a href="export_laporan_pdf.php?tanggal=<?php echo $tanggal; ?>" class="btn btn-success">
                            <i class="fas fa-file-pdf"></i> Export PDF
                        </a>
                    </div>
                </div>

                <!-- Date Picker -->
                <div class="date-picker">
                    <form method="GET" class="date-form">
                        <i class="fas fa-calendar-alt" style="color: #48bb78;"></i>
                        <input type="date" name="tanggal" class="date-input" value="<?php echo $tanggal; ?>">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Tampilkan
                        </button>
                    </form>
                </div>

                <!-- Stats -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon total">
                            <i class="fas fa-exchange-alt"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo $statistik['total_transaksi'] ?: 0; ?></h3>
                            <p>Total Transaksi</p>
                            <div class="stat-detail">
                                Setor: <?php echo $statistik['jumlah_setor'] ?: 0; ?> | 
                                Tarik: <?php echo $statistik['jumlah_tarik'] ?: 0; ?>
                            </div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon setor">
                            <i class="fas fa-arrow-up"></i>
                        </div>
                        <div class="stat-content">
                            <h3>Rp <?php echo number_format($statistik['total_setor'], 0, ',', '.'); ?></h3>
                            <p>Total Setoran</p>
                            <div class="stat-detail">
                                <?php echo $statistik['jumlah_setor'] ?: 0; ?> transaksi
                            </div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon tarik">
                            <i class="fas fa-arrow-down"></i>
                        </div>
                        <div class="stat-content">
                            <h3>Rp <?php echo number_format($statistik['total_tarik'], 0, ',', '.'); ?></h3>
                            <p>Total Penarikan</p>
                            <div class="stat-detail">
                                <?php echo $statistik['jumlah_tarik'] ?: 0; ?> transaksi
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Grid 2 Kolom -->
                <div class="grid-2">
                    <!-- Detail Transaksi -->
                    <div class="card">
                        <div class="card-header">
                            <h2>
                                <i class="fas fa-list"></i>
                                Detail Transaksi
                            </h2>
                        </div>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Waktu</th>
                                        <th>Nasabah</th>
                                        <th>Jenis</th>
                                        <th>Jumlah</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if($transaksi && mysqli_num_rows($transaksi) > 0): ?>
                                        <?php while($row = mysqli_fetch_assoc($transaksi)): ?>
                                        <tr>
                                            <td><?php echo date('H:i', strtotime($row['tanggal'])); ?></td>
                                            <td>
                                                <?php echo $row['nama_lengkap']; ?><br>
                                                <small><?php echo $row['kelas']; ?></small>
                                            </td>
                                            <td>
                                                <span class="<?php echo $row['jenis_transaksi'] == 'setor' ? 'badge-success' : 'badge-warning'; ?>">
                                                    <?php echo ucfirst($row['jenis_transaksi']); ?>
                                                </span>
                                            </td>
                                            <td class="<?php echo $row['jenis_transaksi'] == 'setor' ? 'amount-setor' : 'amount-tarik'; ?>">
                                                Rp <?php echo number_format($row['jumlah'], 0, ',', '.'); ?>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" style="text-align: center; padding: 40px; color: #a0aec0;">
                                                <i class="fas fa-inbox fa-2x"></i>
                                                <p>Tidak ada transaksi pada tanggal ini</p>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Nasabah Aktif -->
                    <div class="card">
                        <div class="card-header">
                            <h2>
                                <i class="fas fa-users"></i>
                                Nasabah Aktif
                            </h2>
                        </div>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Nama</th>
                                        <th>Kelas</th>
                                        <th>Transaksi</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if($nasabah_aktif && mysqli_num_rows($nasabah_aktif) > 0): ?>
                                        <?php 
                                        $no = 1;
                                        while($row = mysqli_fetch_assoc($nasabah_aktif)): 
                                        ?>
                                        <tr>
                                            <td><?php echo $no++; ?></td>
                                            <td><?php echo $row['nama_lengkap']; ?></td>
                                            <td><?php echo $row['kelas'] ?: '-'; ?></td>
                                            <td><?php echo $row['jumlah_transaksi']; ?>x</td>
                                            <td>
                                                <small>
                                                    Setor: Rp <?php echo number_format($row['total_setor'], 0, ',', '.'); ?><br>
                                                    Tarik: Rp <?php echo number_format($row['total_tarik'], 0, ',', '.'); ?>
                                                </small>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" style="text-align: center; padding: 40px; color: #a0aec0;">
                                                <i class="fas fa-users-slash"></i>
                                                <p>Belum ada nasabah bertransaksi</p>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Tanda Tangan (untuk cetak) -->
                <div class="signature-area print-only">
                    <div class="signature-box">
                        <p>Petugas,</p>
                        <div class="signature-line"></div>
                        <p><strong><?php echo $_SESSION['nama_lengkap']; ?></strong></p>
                    </div>
                    <div class="signature-box">
                        <p>Mengetahui,</p>
                        <div class="signature-line"></div>
                        <p><strong>Kepala Sekolah</strong></p>
                    </div>
                    <div class="signature-box">
                        <p>Bendahara,</p>
                        <div class="signature-line"></div>
                        <p><strong>Bendahara</strong></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
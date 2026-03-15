<?php
// nasabah/mutasi_rekening.php
include '../config/database.php';

// Cek login dan role nasabah
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'nasabah') {
    header("Location: " . BASE_URL . "login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Ambil data user
$user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id = $user_id"));

// Ambil saldo
$saldo = mysqli_fetch_assoc(mysqli_query($conn, "SELECT total_saldo FROM saldo WHERE user_id = $user_id"));
$total_saldo = $saldo['total_saldo'] ?? 0;

// Filter tanggal
$tanggal_awal = isset($_GET['tanggal_awal']) ? $_GET['tanggal_awal'] : date('Y-m-01');
$tanggal_akhir = isset($_GET['tanggal_akhir']) ? $_GET['tanggal_akhir'] : date('Y-m-d');

// Query mutasi
$query = "SELECT * FROM tabungan 
          WHERE user_id = $user_id 
          AND DATE(tanggal) BETWEEN '$tanggal_awal' AND '$tanggal_akhir'
          ORDER BY tanggal DESC";
$mutasi = mysqli_query($conn, $query);

// Hitung saldo awal periode
$saldo_awal = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COALESCE(SUM(CASE WHEN jenis_transaksi = 'setor' THEN jumlah ELSE -jumlah END), 0) as total
    FROM tabungan 
    WHERE user_id = $user_id 
    AND DATE(tanggal) < '$tanggal_awal'
"));
$saldo_awal_periode = $saldo_awal['total'];

// Hitung total mutasi periode
$total_setor = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COALESCE(SUM(jumlah), 0) as total
    FROM tabungan 
    WHERE user_id = $user_id 
    AND DATE(tanggal) BETWEEN '$tanggal_awal' AND '$tanggal_akhir'
    AND jenis_transaksi = 'setor'
"))['total'];

$total_tarik = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COALESCE(SUM(jumlah), 0) as total
    FROM tabungan 
    WHERE user_id = $user_id 
    AND DATE(tanggal) BETWEEN '$tanggal_awal' AND '$tanggal_akhir'
    AND jenis_transaksi = 'tarik'
"))['total'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mutasi Rekening - Tabungan Siswa</title>
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
            border: 3px solid #9f7aea;
        }

        .user-welcome .name {
            font-size: 1.2em;
            font-weight: 600;
            margin-bottom: 5px;
            color: white;
        }

        .user-welcome .role {
            color: #9f7aea;
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
            background: #9f7aea;
            color: white;
            border-left: 4px solid #9f7aea;
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
            color: #9f7aea;
        }

        .breadcrumb {
            color: #718096;
            font-size: 0.95em;
        }

        .info-card {
            background: linear-gradient(135deg, #9f7aea 0%, #805ad5 100%);
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 30px;
            color: white;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        .info-item {
            text-align: center;
        }

        .info-item .label {
            font-size: 0.9em;
            opacity: 0.9;
            margin-bottom: 5px;
        }

        .info-item .value {
            font-size: 1.5em;
            font-weight: bold;
        }

        .card {
            background: white;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            margin-bottom: 30px;
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

        .filter-form {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: flex-end;
        }

        .form-group {
            min-width: 150px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #4a5568;
            font-weight: 600;
            font-size: 0.9em;
        }

        .form-control {
            width: 100%;
            padding: 10px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.95em;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 0.95em;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            text-decoration: none;
        }

        .btn-primary {
            background: #9f7aea;
            color: white;
        }

        .btn-primary:hover {
            background: #805ad5;
        }

        .btn-success {
            background: #48bb78;
            color: white;
        }

        .btn-success:hover {
            background: #38a169;
        }

        .btn-secondary {
            background: #a0aec0;
            color: white;
        }

        .btn-secondary:hover {
            background: #718096;
        }

        .table-responsive {
            overflow-x: auto;
        }

        .mutasi-table {
            width: 100%;
            border-collapse: collapse;
        }

        .mutasi-table th {
            background: #f7fafc;
            padding: 15px 10px;
            text-align: left;
            color: #4a5568;
            font-weight: 600;
        }

        .mutasi-table td {
            padding: 12px 10px;
            border-bottom: 1px solid #e2e8f0;
        }

        .text-setor {
            color: #48bb78;
            font-weight: 600;
        }

        .text-tarik {
            color: #ed8936;
            font-weight: 600;
        }

        .saldo-row {
            background: #f0f9ff;
            font-weight: 600;
        }

        .saldo-row td {
            border-top: 2px solid #4299e1;
        }

        .print-only {
            display: none;
        }

        @media print {
            .sidebar, .sidebar-footer, .page-header .btn, .filter-form .btn {
                display: none;
            }
            .main-content {
                margin-left: 0;
                padding: 0;
            }
            .print-only {
                display: block;
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
            .filter-form {
                flex-direction: column;
            }
            .info-card {
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
                    <?php echo strtoupper(substr($user['nama_lengkap'], 0, 1)); ?>
                </div>
                <div class="name"><?php echo htmlspecialchars($user['nama_lengkap']); ?></div>
                <div class="role">Nasabah</div>
            </div>
            <div class="sidebar-menu">
                <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="setor_tunai.php"><i class="fas fa-plus-circle"></i> Setor Tunai</a>
                <a href="tarik_tunai.php"><i class="fas fa-minus-circle"></i> Tarik Tunai</a>
                <a href="history_transaksi.php"><i class="fas fa-history"></i> History Transaksi</a>
                <a href="mutasi_rekening.php" class="active"><i class="fas fa-file-alt"></i> Mutasi Rekening</a>
                <a href="profil.php"><i class="fas fa-user"></i> Profil Saya</a>
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
                        <h1><i class="fas fa-file-alt"></i> Mutasi Rekening</h1>
                        <div class="breadcrumb">
                            <a href="dashboard.php">Dashboard</a> / Mutasi Rekening
                        </div>
                    </div>
                    <div>
                        <button onclick="window.print()" class="btn btn-primary">
                            <i class="fas fa-print"></i> Cetak
                        </button>
                    </div>
                </div>

                <!-- Info Rekening -->
                <div class="info-card">
                    <div class="info-item">
                        <div class="label">Nama Nasabah</div>
                        <div class="value"><?php echo htmlspecialchars($user['nama_lengkap']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="label">Nomor Rekening</div>
                        <div class="value"><?php echo str_pad($user['id'], 6, '0', STR_PAD_LEFT); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="label">Saldo Saat Ini</div>
                        <div class="value">Rp <?php echo number_format($total_saldo, 0, ',', '.'); ?></div>
                    </div>
                </div>

                <!-- Filter Form -->
                <div class="card">
                    <div class="card-header">
                        <h2><i class="fas fa-filter"></i> Periode Mutasi</h2>
                    </div>
                    <form method="GET" class="filter-form">
                        <div class="form-group">
                            <label>Tanggal Awal</label>
                            <input type="date" name="tanggal_awal" class="form-control" value="<?php echo $tanggal_awal; ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Tanggal Akhir</label>
                            <input type="date" name="tanggal_akhir" class="form-control" value="<?php echo $tanggal_akhir; ?>" required>
                        </div>
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Tampilkan
                            </button>
                            <a href="mutasi_rekening.php" class="btn btn-secondary">
                                <i class="fas fa-sync"></i> Reset
                            </a>
                        </div>
                    </form>
                </div>

                <!-- Ringkasan Mutasi -->
                <div class="card">
                    <div class="card-header">
                        <h2><i class="fas fa-chart-pie"></i> Ringkasan Mutasi</h2>
                    </div>
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; text-align: center;">
                        <div>
                            <p style="color: #718096; margin-bottom: 5px;">Saldo Awal</p>
                            <h3 style="color: #2d3748;">Rp <?php echo number_format($saldo_awal_periode, 0, ',', '.'); ?></h3>
                        </div>
                        <div>
                            <p style="color: #48bb78; margin-bottom: 5px;">Total Setor</p>
                            <h3 style="color: #48bb78;">Rp <?php echo number_format($total_setor, 0, ',', '.'); ?></h3>
                        </div>
                        <div>
                            <p style="color: #ed8936; margin-bottom: 5px;">Total Tarik</p>
                            <h3 style="color: #ed8936;">Rp <?php echo number_format($total_tarik, 0, ',', '.'); ?></h3>
                        </div>
                    </div>
                </div>

                <!-- Tabel Mutasi -->
                <div class="card">
                    <div class="card-header">
                        <h2><i class="fas fa-list"></i> Detail Mutasi</h2>
                        <p>Periode: <?php echo date('d/m/Y', strtotime($tanggal_awal)); ?> - <?php echo date('d/m/Y', strtotime($tanggal_akhir)); ?></p>
                    </div>
                    <div class="table-responsive">
                        <table class="mutasi-table">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Tanggal</th>
                                    <th>Keterangan</th>
                                    <th>Debit (Setor)</th>
                                    <th>Kredit (Tarik)</th>
                                    <th>Saldo</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $no = 1;
                                $running_saldo = $saldo_awal_periode;
                                if(mysqli_num_rows($mutasi) > 0):
                                    mysqli_data_seek($mutasi, 0);
                                    while($row = mysqli_fetch_assoc($mutasi)):
                                        if($row['jenis_transaksi'] == 'setor') {
                                            $running_saldo += $row['jumlah'];
                                            $debit = $row['jumlah'];
                                            $kredit = 0;
                                        } else {
                                            $running_saldo -= $row['jumlah'];
                                            $debit = 0;
                                            $kredit = $row['jumlah'];
                                        }
                                ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($row['tanggal'])); ?></td>
                                    <td><?php echo htmlspecialchars($row['keterangan'] ?: '-'); ?></td>
                                    <td class="text-setor"><?php echo $debit ? 'Rp ' . number_format($debit, 0, ',', '.') : '-'; ?></td>
                                    <td class="text-tarik"><?php echo $kredit ? 'Rp ' . number_format($kredit, 0, ',', '.') : '-'; ?></td>
                                    <td><strong>Rp <?php echo number_format($running_saldo, 0, ',', '.'); ?></strong></td>
                                </tr>
                                <?php 
                                    endwhile;
                                ?>
                                <tr class="saldo-row">
                                    <td colspan="5" style="text-align: right;"><strong>Saldo Akhir:</strong></td>
                                    <td><strong>Rp <?php echo number_format($running_saldo, 0, ',', '.'); ?></strong></td>
                                </tr>
                                <?php else: ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 40px; color: #a0aec0;">
                                        <i class="fas fa-inbox fa-3x"></i>
                                        <p>Tidak ada transaksi pada periode ini</p>
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
</body>
</html>
<?php
// admin/dashboard.php
include '../config/database.php';

// Cek login dan role admin
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

// Statistik Utama
$total_users = 0;
$total_nasabah = 0;
$total_petugas = 0;
$total_transaksi = 0;
$total_saldo = 0;

// Query dengan pengecekan error
$result = mysqli_query($conn, "SELECT COUNT(*) as total FROM users");
if($result) {
    $total_users = mysqli_fetch_assoc($result)['total'];
}

$result = mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE role='nasabah'");
if($result) {
    $total_nasabah = mysqli_fetch_assoc($result)['total'];
}

$result = mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE role='petugas'");
if($result) {
    $total_petugas = mysqli_fetch_assoc($result)['total'];
}

$result = mysqli_query($conn, "SELECT COUNT(*) as total FROM tabungan");
if($result) {
    $total_transaksi = mysqli_fetch_assoc($result)['total'];
}

$result = mysqli_query($conn, "SELECT COALESCE(SUM(total_saldo), 0) as total FROM saldo");
if($result) {
    $total_saldo = mysqli_fetch_assoc($result)['total'];
}

// Statistik Hari Ini
$hari_ini = date('Y-m-d');
$transaksi_hari_ini = ['jumlah' => 0, 'nominal' => 0];

$query_hari_ini = "SELECT COUNT(*) as jumlah, COALESCE(SUM(jumlah), 0) as nominal 
                   FROM tabungan 
                   WHERE DATE(tanggal) = '$hari_ini'";
$result_hari_ini = mysqli_query($conn, $query_hari_ini);
if($result_hari_ini) {
    $transaksi_hari_ini = mysqli_fetch_assoc($result_hari_ini);
}

// Statistik Bulan Ini
$bulan_ini = date('Y-m');
$transaksi_bulan_ini = ['jumlah' => 0, 'nominal' => 0];

$query_bulan_ini = "SELECT COUNT(*) as jumlah, COALESCE(SUM(jumlah), 0) as nominal 
                    FROM tabungan 
                    WHERE DATE_FORMAT(tanggal, '%Y-%m') = '$bulan_ini'";
$result_bulan_ini = mysqli_query($conn, $query_bulan_ini);
if($result_bulan_ini) {
    $transaksi_bulan_ini = mysqli_fetch_assoc($result_bulan_ini);
}

// 5 Transaksi Terbaru
$transaksi_terbaru = [];
$query_terbaru = "SELECT t.*, u.nama_lengkap, u.username, u.kelas 
                  FROM tabungan t 
                  JOIN users u ON t.user_id = u.id 
                  ORDER BY t.tanggal DESC 
                  LIMIT 5";
$result_terbaru = mysqli_query($conn, $query_terbaru);
if($result_terbaru) {
    $transaksi_terbaru = $result_terbaru;
}

// 5 Nasabah dengan Saldo Tertinggi
$top_nasabah = [];
$query_top = "SELECT u.nama_lengkap, u.username, u.kelas, COALESCE(s.total_saldo, 0) as total_saldo 
              FROM users u 
              LEFT JOIN saldo s ON u.id = s.user_id 
              WHERE u.role = 'nasabah' 
              ORDER BY s.total_saldo DESC 
              LIMIT 5";
$result_top = mysqli_query($conn, $query_top);
if($result_top) {
    $top_nasabah = $result_top;
}

// Data untuk Chart (7 hari terakhir)
$chart_labels = [];
$chart_setor = [];
$chart_tarik = [];

// Generate data untuk 7 hari terakhir
for($i = 6; $i >= 0; $i--) {
    $tanggal = date('Y-m-d', strtotime("-$i days"));
    $tanggal_label = date('d/m', strtotime($tanggal));
    $chart_labels[] = $tanggal_label;
    
    // Query SETOR per hari
    $query_setor = "SELECT COALESCE(SUM(jumlah), 0) as total 
                    FROM tabungan 
                    WHERE DATE(tanggal) = '$tanggal' AND jenis_transaksi = 'setor'";
    $result_setor = mysqli_query($conn, $query_setor);
    if($result_setor) {
        $data_setor = mysqli_fetch_assoc($result_setor);
        $chart_setor[] = (int)$data_setor['total'];
    } else {
        $chart_setor[] = 0;
    }
    
    // Query TARIK per hari
    $query_tarik = "SELECT COALESCE(SUM(jumlah), 0) as total 
                    FROM tabungan 
                    WHERE DATE(tanggal) = '$tanggal' AND jenis_transaksi = 'tarik'";
    $result_tarik = mysqli_query($conn, $query_tarik);
    if($result_tarik) {
        $data_tarik = mysqli_fetch_assoc($result_tarik);
        $chart_tarik[] = (int)$data_tarik['total'];
    } else {
        $chart_tarik[] = 0;
    }
}

// Aktivitas Petugas Hari Ini
$aktivitas_petugas = [];
$query_aktivitas = "SELECT u.nama_lengkap, 
                           COALESCE(COUNT(t.id), 0) as jumlah_transaksi, 
                           COALESCE(SUM(t.jumlah), 0) as total_nominal
                    FROM users u
                    LEFT JOIN tabungan t ON u.id = t.user_id AND DATE(t.tanggal) = '$hari_ini'
                    WHERE u.role = 'petugas'
                    GROUP BY u.id
                    ORDER BY jumlah_transaksi DESC";
$result_aktivitas = mysqli_query($conn, $query_aktivitas);
if($result_aktivitas) {
    $aktivitas_petugas = $result_aktivitas;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Tabungan Siswa</title>
    <link rel="stylesheet" href="../css/style.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* [CSS SAMA SEPERTI SEBELUMNYA] */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .dashboard-wrapper {
            max-width: 1400px;
            margin: 0 auto;
        }

        /* Header */
        .dashboard-header {
            background: white;
            border-radius: 20px;
            padding: 25px 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .header-left h1 {
            color: #2d3748;
            font-size: 2em;
            margin-bottom: 5px;
        }

        .header-left .date {
            color: #718096;
            font-size: 1em;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
        }

        .welcome-badge {
            background: #48bb78;
            color: white;
            padding: 10px 20px;
            border-radius: 50px;
            font-size: 0.95em;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 10px 20px;
            background: #f7fafc;
            border-radius: 50px;
        }

        .user-avatar {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 1.3em;
        }

        .user-info {
            text-align: right;
        }

        .user-info .name {
            font-weight: bold;
            color: #2d3748;
        }

        .user-info .role {
            color: #718096;
            font-size: 0.9em;
        }

        .logout-btn {
            background: #f56565;
            color: white;
            padding: 10px 20px;
            border-radius: 10px;
            text-decoration: none;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .logout-btn:hover {
            background: #e53e3e;
            transform: translateY(-2px);
        }

        /* Stats Grid */
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
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.15);
        }

        .stat-icon {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5em;
            margin-right: 20px;
        }

        .stat-icon.primary {
            background: #e6f0ff;
            color: #4299e1;
        }

        .stat-icon.success {
            background: #c6f6d5;
            color: #48bb78;
        }

        .stat-icon.warning {
            background: #feebc8;
            color: #ed8936;
        }

        .stat-icon.info {
            background: #e6f7ff;
            color: #00b5d8;
        }

        .stat-icon.purple {
            background: #e9d8fd;
            color: #9f7aea;
        }

        .stat-detail {
            flex: 1;
        }

        .stat-detail h3 {
            font-size: 2em;
            color: #2d3748;
            margin-bottom: 5px;
        }

        .stat-detail p {
            color: #718096;
            margin-bottom: 5px;
        }

        .amount {
            font-weight: 600;
            color: #2d3748;
            font-size: 1.1em;
        }

        /* Chart Card */
        .chart-card {
            background: white;
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .chart-header h2 {
            color: #2d3748;
            font-size: 1.4em;
        }

        .chart-legend {
            display: flex;
            gap: 20px;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .legend-color {
            width: 12px;
            height: 12px;
            border-radius: 3px;
        }

        .legend-color.setor {
            background: #48bb78;
        }

        .legend-color.tarik {
            background: #ed8936;
        }

        .chart-container {
            position: relative;
            width: 100%;
            height: 350px;
            margin: 0 auto;
        }

        #transaksiChart {
            width: 100% !important;
            height: 100% !important;
            max-height: 350px;
        }

        /* Content Grid */
        .content-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
            margin-bottom: 30px;
        }

        /* Table Cards */
        .table-card {
            background: white;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .card-header h2 {
            color: #2d3748;
            font-size: 1.3em;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .view-all {
            color: #667eea;
            text-decoration: none;
            font-size: 0.95em;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .view-all:hover {
            color: #5a67d8;
        }

        .table-responsive {
            overflow-x: auto;
        }

        .admin-table {
            width: 100%;
            border-collapse: collapse;
        }

        .admin-table th {
            text-align: left;
            padding: 15px 10px;
            background: #f7fafc;
            color: #4a5568;
            font-weight: 600;
            font-size: 0.95em;
        }

        .admin-table td {
            padding: 15px 10px;
            border-bottom: 1px solid #e2e8f0;
            color: #2d3748;
        }

        .admin-table tr:hover td {
            background: #f7fafc;
        }

        .badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 500;
        }

        .badge-success {
            background: #c6f6d5;
            color: #22543d;
        }

        .badge-warning {
            background: #feebc8;
            color: #744210;
        }

        .badge-info {
            background: #bee3f8;
            color: #2c5282;
        }

        /* Info Grid */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .info-card {
            background: white;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .info-card h3 {
            color: #2d3748;
            margin-bottom: 20px;
            font-size: 1.2em;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .activity-list {
            list-style: none;
        }

        .activity-item {
            padding: 15px 0;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-info {
            flex: 1;
        }

        .activity-name {
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 3px;
        }

        .activity-meta {
            font-size: 0.9em;
            color: #718096;
        }

        .activity-stats {
            text-align: right;
        }

        .activity-count {
            font-weight: 600;
            color: #48bb78;
        }

        .quick-menu {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }

        .quick-item {
            background: #f7fafc;
            border-radius: 15px;
            padding: 20px;
            text-decoration: none;
            color: #2d3748;
            transition: all 0.3s;
            text-align: center;
        }

        .quick-item:hover {
            background: #667eea;
            color: white;
            transform: translateY(-5px);
        }

        .quick-icon {
            font-size: 2em;
            margin-bottom: 10px;
        }

        .quick-item span {
            font-size: 0.95em;
            font-weight: 500;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #a0aec0;
        }

        .empty-state i {
            font-size: 3em;
            margin-bottom: 15px;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
            
            .chart-container {
                height: 300px;
            }
        }

        @media (max-width: 768px) {
            .dashboard-header {
                flex-direction: column;
                text-align: center;
            }

            .header-right {
                justify-content: center;
                width: 100%;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .chart-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .quick-menu {
                grid-template-columns: 1fr;
            }
            
            .chart-container {
                height: 250px;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-wrapper">
        <!-- Header -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1>Dashboard Admin</h1>
                <div class="date">
                    <i class="fas fa-calendar-alt"></i> 
                    <?php echo date('l, d F Y'); ?> | 
                    <i class="fas fa-clock"></i> 
                    <?php echo date('H:i'); ?> WIB
                </div>
            </div>
            <div class="header-right">
                <div class="welcome-badge">
                    <i class="fas fa-crown"></i>
                    Administrator
                </div>
                <div class="user-profile">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($_SESSION['nama_lengkap'], 0, 1)); ?>
                    </div>
                    <div class="user-info">
                        <div class="name"><?php echo $_SESSION['nama_lengkap']; ?></div>
                        <div class="role">Super Admin</div>
                    </div>
                </div>
                <a href="../logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </a>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon primary">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-detail">
                    <h3><?php echo $total_users; ?></h3>
                    <p>Total Users</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon success">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <div class="stat-detail">
                    <h3><?php echo $total_nasabah; ?></h3>
                    <p>Total Nasabah</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon warning">
                    <i class="fas fa-user-tie"></i>
                </div>
                <div class="stat-detail">
                    <h3><?php echo $total_petugas; ?></h3>
                    <p>Total Petugas</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon info">
                    <i class="fas fa-exchange-alt"></i>
                </div>
                <div class="stat-detail">
                    <h3><?php echo $total_transaksi; ?></h3>
                    <p>Total Transaksi</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon purple">
                    <i class="fas fa-wallet"></i>
                </div>
                <div class="stat-detail">
                    <h3>Rp <?php echo number_format($total_saldo, 0, ',', '.'); ?></h3>
                    <p>Total Saldo</p>
                </div>
            </div>
        </div>

        <!-- Statistik Hari Ini & Bulan Ini -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon success">
                    <i class="fas fa-calendar-day"></i>
                </div>
                <div class="stat-detail">
                    <h3><?php echo $transaksi_hari_ini['jumlah'] ?: 0; ?></h3>
                    <p>Transaksi Hari Ini</p>
                    <div class="amount">
                        Rp <?php echo number_format($transaksi_hari_ini['nominal'] ?: 0, 0, ',', '.'); ?>
                    </div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon warning">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <div class="stat-detail">
                    <h3><?php echo $transaksi_bulan_ini['jumlah'] ?: 0; ?></h3>
                    <p>Transaksi Bulan Ini</p>
                    <div class="amount">
                        Rp <?php echo number_format($transaksi_bulan_ini['nominal'] ?: 0, 0, ',', '.'); ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Chart -->
        <div class="chart-card">
            <div class="chart-header">
                <h2><i class="fas fa-chart-line"></i> Grafik Transaksi 7 Hari Terakhir</h2>
                <div class="chart-legend">
                    <div class="legend-item">
                        <span class="legend-color setor"></span>
                        <span>Setor</span>
                    </div>
                    <div class="legend-item">
                        <span class="legend-color tarik"></span>
                        <span>Tarik</span>
                    </div>
                </div>
            </div>
            <div class="chart-container">
                <canvas id="transaksiChart"></canvas>
            </div>
        </div>

        <!-- Content Grid -->
        <div class="content-grid">
            <!-- Transaksi Terbaru -->
            <div class="table-card">
                <div class="card-header">
                    <h2>
                        <i class="fas fa-history"></i>
                        Transaksi Terbaru
                    </h2>
                    <a href="transaksi.php" class="view-all">
                        Lihat Semua <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Waktu</th>
                                <th>Nasabah</th>
                                <th>Jenis</th>
                                <th>Jumlah</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($transaksi_terbaru && mysqli_num_rows($transaksi_terbaru) > 0): ?>
                                <?php while($row = mysqli_fetch_assoc($transaksi_terbaru)): ?>
                                <tr>
                                    <td><?php echo date('H:i', strtotime($row['tanggal'])); ?><br>
                                        <small><?php echo date('d/m', strtotime($row['tanggal'])); ?></small>
                                    </td>
                                    <td>
                                        <?php echo $row['nama_lengkap']; ?><br>
                                        <small><?php echo $row['kelas'] ?: '-'; ?></small>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $row['jenis_transaksi'] == 'setor' ? 'badge-success' : 'badge-warning'; ?>">
                                            <?php echo ucfirst($row['jenis_transaksi']); ?>
                                        </span>
                                    </td>
                                    <td class="amount">Rp <?php echo number_format($row['jumlah'], 0, ',', '.'); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="empty-state">
                                        <i class="fas fa-inbox"></i>
                                        <p>Belum ada transaksi</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Top Nasabah -->
            <div class="table-card">
                <div class="card-header">
                    <h2>
                        <i class="fas fa-trophy"></i>
                        Top 5 Nasabah
                    </h2>
                    <a href="kelola_nasabah.php" class="view-all">
                        Lihat Semua <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Peringkat</th>
                                <th>Nama</th>
                                <th>Kelas</th>
                                <th>Saldo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = 1;
                            if($top_nasabah && mysqli_num_rows($top_nasabah) > 0): 
                                while($row = mysqli_fetch_assoc($top_nasabah)): 
                            ?>
                            <tr>
                                <td>
                                    <span class="badge <?php 
                                        echo $no == 1 ? 'badge-success' : ($no == 2 ? 'badge-info' : 'badge-warning'); 
                                    ?>">
                                        #<?php echo $no++; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php echo $row['nama_lengkap']; ?><br>
                                    <small><?php echo $row['username']; ?></small>
                                </td>
                                <td><?php echo $row['kelas'] ?: '-'; ?></td>
                                <td class="amount">Rp <?php echo number_format($row['total_saldo'], 0, ',', '.'); ?></td>
                            </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="empty-state">
                                        <i class="fas fa-users"></i>
                                        <p>Belum ada nasabah</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Info Grid -->
        <div class="info-grid">
            <!-- Aktivitas Petugas -->
            <div class="info-card">
                <h3>
                    <i class="fas fa-user-clock"></i>
                    Aktivitas Petugas Hari Ini
                </h3>
                <ul class="activity-list">
                    <?php if($aktivitas_petugas && mysqli_num_rows($aktivitas_petugas) > 0): ?>
                        <?php while($row = mysqli_fetch_assoc($aktivitas_petugas)): ?>
                        <li class="activity-item">
                            <div class="activity-info">
                                <div class="activity-name"><?php echo $row['nama_lengkap']; ?></div>
                                <div class="activity-meta">
                                    <?php echo $row['jumlah_transaksi'] ?: 0; ?> transaksi
                                </div>
                            </div>
                            <div class="activity-stats">
                                <div class="activity-count">
                                    Rp <?php echo number_format($row['total_nominal'] ?: 0, 0, ',', '.'); ?>
                                </div>
                            </div>
                        </li>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <li class="empty-state">
                            <i class="fas fa-user-slash"></i>
                            <p>Belum ada aktivitas petugas</p>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- Menu Cepat -->
            <div class="info-card">
                <h3>
                    <i class="fas fa-rocket"></i>
                    Menu Cepat
                </h3>
                <div class="quick-menu">
                    <a href="kelola_petugas.php" class="quick-item">
                        <div class="quick-icon">
                            <i class="fas fa-user-tie"></i>
                        </div>
                        <span>Kelola Petugas</span>
                    </a>
                    <a href="kelola_nasabah.php" class="quick-item">
                        <div class="quick-icon">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                        <span>Kelola Nasabah</span>
                    </a>
                    <a href="transaksi.php" class="quick-item">
                        <div class="quick-icon">
                            <i class="fas fa-exchange-alt"></i>
                        </div>
                        <span>Semua Transaksi</span>
                    </a>
                    <a href="laporan.php" class="quick-item">
                        <div class="quick-icon">
                            <i class="fas fa-file-pdf"></i>
                        </div>
                        <span>Laporan</span>
                    </a>
                    <a href="pengaturan.php" class="quick-item">
                        <div class="quick-icon">
                            <i class="fas fa-cog"></i>
                        </div>
                        <span>Pengaturan</span>
                    </a>
                    <a href="../logout.php" class="quick-item">
                        <div class="quick-icon">
                            <i class="fas fa-sign-out-alt"></i>
                        </div>
                        <span>Logout</span>
                    </a>
                </div>
            </div>

            <!-- Informasi Sistem -->
            <div class="info-card">
                <h3>
                    <i class="fas fa-info-circle"></i>
                    Informasi Sistem
                </h3>
                <ul class="activity-list">
                    <li class="activity-item">
                        <div class="activity-info">
                            <div class="activity-name">Versi Aplikasi</div>
                            <div class="activity-meta">Tabungan Siswa v2.0</div>
                        </div>
                    </li>
                    <li class="activity-item">
                        <div class="activity-info">
                            <div class="activity-name">PHP Version</div>
                            <div class="activity-meta"><?php echo phpversion(); ?></div>
                        </div>
                    </li>
                    <li class="activity-item">
                        <div class="activity-info">
                            <div class="activity-name">Database</div>
                            <div class="activity-meta">MySQL</div>
                        </div>
                    </li>
                    <li class="activity-item">
                        <div class="activity-info">
                            <div class="activity-name">Last Update</div>
                            <div class="activity-meta"><?php echo date('d F Y H:i'); ?></div>
                        </div>
                    </li>
                    <li class="activity-item">
                        <div class="activity-info">
                            <div class="activity-name">Total Data</div>
                            <div class="activity-meta"><?php echo $total_transaksi; ?> Transaksi</div>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Data dari PHP
        const labels = <?php echo json_encode($chart_labels); ?>;
        const setorData = <?php echo json_encode($chart_setor); ?>;
        const tarikData = <?php echo json_encode($chart_tarik); ?>;
        
        // Cek apakah canvas ada
        const canvas = document.getElementById('transaksiChart');
        if (!canvas) {
            console.error('Canvas tidak ditemukan!');
            return;
        }
        
        // Hapus chart lama jika ada
        if (window.myChart) {
            window.myChart.destroy();
        }
        
        // Buat chart baru
        const ctx = canvas.getContext('2d');
        window.myChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Setor',
                        data: setorData,
                        borderColor: '#48bb78',
                        backgroundColor: 'rgba(72, 187, 120, 0.1)',
                        borderWidth: 3,
                        tension: 0.4,
                        fill: true,
                        pointBackgroundColor: '#48bb78',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 6
                    },
                    {
                        label: 'Tarik',
                        data: tarikData,
                        borderColor: '#ed8936',
                        backgroundColor: 'rgba(237, 137, 54, 0.1)',
                        borderWidth: 3,
                        tension: 0.4,
                        fill: true,
                        pointBackgroundColor: '#ed8936',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 6
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                let value = context.raw || 0;
                                return label + ': Rp ' + value.toLocaleString('id-ID');
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        },
                        ticks: {
                            callback: function(value) {
                                return 'Rp ' + value.toLocaleString('id-ID');
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                },
                interaction: {
                    mode: 'nearest',
                    axis: 'x',
                    intersect: false
                }
            }
        });
    });

    // Auto refresh data setiap 60 detik
    setInterval(function() {
        location.reload();
    }, 60000);
    </script>
</body>
</html>
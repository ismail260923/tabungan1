<?php
include '../config/database.php';

// Cek login dan role nasabah
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'nasabah') {
    header("Location: " . BASE_URL . "login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Ambil data nasabah
$query_user = "SELECT * FROM users WHERE id = $user_id";
$result_user = mysqli_query($conn, $query_user);
$user = mysqli_fetch_assoc($result_user);

// Ambil saldo
$query_saldo = "SELECT total_saldo FROM saldo WHERE user_id = $user_id";
$result_saldo = mysqli_query($conn, $query_saldo);
$saldo = mysqli_fetch_assoc($result_saldo);
$total_saldo = $saldo['total_saldo'] ?? 0;

// Statistik tabungan
$total_setor = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) as jumlah, COALESCE(SUM(jumlah), 0) as total 
    FROM tabungan 
    WHERE user_id = $user_id AND jenis_transaksi = 'setor'
"));

$total_tarik = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) as jumlah, COALESCE(SUM(jumlah), 0) as total 
    FROM tabungan 
    WHERE user_id = $user_id AND jenis_transaksi = 'tarik'
"));

// Transaksi hari ini
$hari_ini = date('Y-m-d');
$transaksi_hari_ini = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) as jumlah, COALESCE(SUM(jumlah), 0) as total 
    FROM tabungan 
    WHERE user_id = $user_id AND DATE(tanggal) = '$hari_ini'
"));

// Ambil history transaksi (10 terbaru)
$query_transaksi = "SELECT * FROM tabungan WHERE user_id = $user_id ORDER BY tanggal DESC LIMIT 10";
$result_transaksi = mysqli_query($conn, $query_transaksi);

// Data untuk chart (7 hari terakhir)
$chart_labels = [];
$chart_setor = [];
$chart_tarik = [];

for($i = 6; $i >= 0; $i--) {
    $tanggal = date('Y-m-d', strtotime("-$i days"));
    $chart_labels[] = date('d/m', strtotime($tanggal));
    
    // Setor per hari
    $setor = mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT COALESCE(SUM(jumlah), 0) as total 
        FROM tabungan 
        WHERE user_id = $user_id AND DATE(tanggal) = '$tanggal' AND jenis_transaksi = 'setor'
    "));
    $chart_setor[] = (int)$setor['total'];
    
    // Tarik per hari
    $tarik = mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT COALESCE(SUM(jumlah), 0) as total 
        FROM tabungan 
        WHERE user_id = $user_id AND DATE(tanggal) = '$tanggal' AND jenis_transaksi = 'tarik'
    "));
    $chart_tarik[] = (int)$tarik['total'];
}

// Info tambahan
$anggota_sejak = date('d F Y', strtotime($user['created_at']));
$total_transaksi = $total_setor['jumlah'] + $total_tarik['jumlah'];

// Mutasi terakhir
$mutasi_terakhir = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT tanggal FROM tabungan WHERE user_id = $user_id ORDER BY tanggal DESC LIMIT 1
"));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Nasabah - Tabungan Siswa</title>
    <link rel="stylesheet" href="../css/style.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <!-- Font Awesome -->
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
        .nasabah-wrapper {
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

        .user-welcome .kelas {
            color: #a0aec0;
            font-size: 0.9em;
            margin-top: 5px;
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

        .sidebar-footer p {
            color: #a0aec0;
            font-size: 0.9em;
        }

        .sidebar-footer strong {
            color: white;
            display: block;
            margin-top: 5px;
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

        /* Main content dengan margin kiri */
        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 30px;
            min-height: 100vh;
        }

        /* Content wrapper */
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

        .date-info {
            display: flex;
            gap: 20px;
            color: #718096;
        }

        .date-info i {
            color: #48bb78;
        }

        /* Saldo Card */
        .saldo-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .saldo-info p {
            font-size: 1.1em;
            opacity: 0.9;
            margin-bottom: 5px;
        }

        .saldo-info h2 {
            font-size: 3em;
            margin-bottom: 5px;
        }

        .saldo-info small {
            opacity: 0.8;
        }

        .saldo-actions {
            display: flex;
            gap: 15px;
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
            text-decoration: none;
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

        .btn-warning {
            background: #ed8936;
            color: white;
        }

        .btn-warning:hover {
            background: #dd6b20;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(237, 137, 54, 0.4);
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background: #5a67d8;
            transform: translateY(-2px);
        }

        .btn-outline {
            background: transparent;
            border: 2px solid white;
            color: white;
        }

        .btn-outline:hover {
            background: white;
            color: #667eea;
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

        .stat-icon.setor {
            background: #c6f6d5;
            color: #48bb78;
        }

        .stat-icon.tarik {
            background: #feebc8;
            color: #ed8936;
        }

        .stat-icon.hariini {
            background: #e6f0ff;
            color: #4299e1;
        }

        .stat-content {
            flex: 1;
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

        /* Chart Card */
        .chart-card {
            background: white;
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
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
            font-size: 1.3em;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .chart-header h2 i {
            color: #48bb78;
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
            height: 300px;
        }

        /* Content Grid */
        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 25px;
            margin-bottom: 30px;
        }

        @media (max-width: 1024px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Card */
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

        .card-header h2 i {
            color: #48bb78;
        }

        .view-all {
            color: #667eea;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .view-all:hover {
            color: #5a67d8;
        }

        /* Transaction List */
        .transaction-list {
            list-style: none;
        }

        .transaction-item {
            display: flex;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #e2e8f0;
        }

        .transaction-item:last-child {
            border-bottom: none;
        }

        .transaction-icon {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-size: 1.2em;
        }

        .transaction-icon.setor {
            background: #c6f6d5;
            color: #48bb78;
        }

        .transaction-icon.tarik {
            background: #feebc8;
            color: #ed8936;
        }

        .transaction-detail {
            flex: 1;
        }

        .transaction-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }

        .transaction-type {
            font-weight: 600;
        }

        .transaction-type.setor {
            color: #48bb78;
        }

        .transaction-type.tarik {
            color: #ed8936;
        }

        .transaction-amount {
            font-weight: 600;
        }

        .transaction-amount.setor {
            color: #48bb78;
        }

        .transaction-amount.tarik {
            color: #ed8936;
        }

        .transaction-footer {
            display: flex;
            justify-content: space-between;
            font-size: 0.9em;
            color: #a0aec0;
        }

        .transaction-date {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .transaction-desc {
            color: #718096;
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        /* Info Cards */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }

        .info-card {
            background: white;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        .info-card h3 {
            color: #2d3748;
            margin-bottom: 15px;
            font-size: 1.1em;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .info-card h3 i {
            color: #48bb78;
        }

        .info-list {
            list-style: none;
        }

        .info-list li {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 0;
            border-bottom: 1px solid #e2e8f0;
            color: #4a5568;
        }

        .info-list li:last-child {
            border-bottom: none;
        }

        .info-list i {
            color: #48bb78;
            width: 20px;
        }

        .badge {
            padding: 4px 10px;
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

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #a0aec0;
        }

        .empty-state i {
            font-size: 3em;
            margin-bottom: 15px;
        }

        .empty-state p {
            margin-bottom: 20px;
        }

        /* Quick Actions */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-top: 20px;
        }

        .quick-action-item {
            background: #f7fafc;
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            text-decoration: none;
            color: #2d3748;
            transition: all 0.3s;
        }

        .quick-action-item:hover {
            background: #48bb78;
            color: white;
            transform: translateY(-5px);
        }

        .quick-action-item i {
            font-size: 2em;
            margin-bottom: 10px;
            color: #48bb78;
        }

        .quick-action-item:hover i {
            color: white;
        }

        .quick-action-item span {
            display: block;
            font-weight: 500;
        }

        /* Progress Bar */
        .progress-container {
            margin: 15px 0;
        }

        .progress-label {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            color: #4a5568;
        }

        .progress-bar {
            width: 100%;
            height: 10px;
            background: #e2e8f0;
            border-radius: 5px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #48bb78, #667eea);
            border-radius: 5px;
            transition: width 0.3s;
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
            
            .saldo-card {
                flex-direction: column;
                text-align: center;
            }
            
            .saldo-actions {
                width: 100%;
                justify-content: center;
            }
            
            .date-info {
                flex-direction: column;
                gap: 10px;
            }
            
            .quick-actions {
                grid-template-columns: 1fr;
            }
            
            .transaction-footer {
                flex-direction: column;
                gap: 5px;
            }
            
            .transaction-desc {
                max-width: 100%;
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
                <div class="role">
                    <i class="fas fa-user-graduate"></i> Nasabah
                </div>
                <div class="kelas">
                    <i class="fas fa-school"></i> <?php echo htmlspecialchars($user['kelas'] ?: 'Kelas belum diisi'); ?>
                </div>
            </div>

            <div class="sidebar-menu">
                <a href="dashboard.php" class="active">
                    <i class="fas fa-tachometer-alt"></i>
                    Dashboard
                </a>
                <a href="setor_tunai.php">
                    <i class="fas fa-plus-circle"></i>
                    Setor Tunai
                </a>
                <a href="tarik_tunai.php">
                    <i class="fas fa-minus-circle"></i>
                    Tarik Tunai
                </a>
                <a href="history_transaksi.php">
                    <i class="fas fa-history"></i>
                    History Transaksi
                </a>
                <a href="mutasi_rekening.php">
                    <i class="fas fa-file-alt"></i>
                    Mutasi Rekening
                </a>
                <a href="profil.php">
                    <i class="fas fa-user"></i>
                    Profil Saya
                </a>
            </div>

            <div class="sidebar-footer">
                <p>Login sebagai:</p>
                <strong><?php echo htmlspecialchars($user['nama_lengkap']); ?></strong>
                <a href="../logout.php" class="logout-link">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="content-wrapper">
                <!-- Page Header -->
                <div class="page-header">
                    <div>
                        <h1>
                            <i class="fas fa-tachometer-alt"></i>
                            Dashboard Nasabah
                        </h1>
                        <div class="breadcrumb">
                            <span>Dashboard</span>
                        </div>
                    </div>
                    <div class="date-info">
                        <div>
                            <i class="fas fa-calendar-alt"></i> 
                            <?php echo date('l, d F Y'); ?>
                        </div>
                        <div>
                            <i class="fas fa-clock"></i> 
                            <?php echo date('H:i'); ?> WIB
                        </div>
                    </div>
                </div>

                <!-- Saldo Card -->
                <div class="saldo-card">
                    <div class="saldo-info">
                        <p><i class="fas fa-wallet"></i> Total Saldo Anda</p>
                        <h2>Rp <?php echo number_format($total_saldo, 0, ',', '.'); ?></h2>
                        <small>
                            <i class="fas fa-info-circle"></i> 
                            Terakhir diperbarui: <?php echo $mutasi_terakhir ? date('d/m/Y H:i', strtotime($mutasi_terakhir['tanggal'])) : 'Belum ada transaksi'; ?>
                        </small>
                    </div>
                    <div class="saldo-actions">
                        <a href="setor_tunai.php" class="btn btn-success">
                            <i class="fas fa-plus-circle"></i> Setor
                        </a>
                        <a href="tarik_tunai.php" class="btn btn-warning">
                            <i class="fas fa-minus-circle"></i> Tarik
                        </a>
                        <a href="mutasi_rekening.php" class="btn btn-outline">
                            <i class="fas fa-file-alt"></i> Mutasi
                        </a>
                    </div>
                </div>

                <!-- Stats Grid -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon setor">
                            <i class="fas fa-arrow-up"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo $total_setor['jumlah']; ?>x</h3>
                            <p>Total Setor</p>
                            <div class="stat-detail">
                                Rp <?php echo number_format($total_setor['total'], 0, ',', '.'); ?>
                            </div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon tarik">
                            <i class="fas fa-arrow-down"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo $total_tarik['jumlah']; ?>x</h3>
                            <p>Total Tarik</p>
                            <div class="stat-detail">
                                Rp <?php echo number_format($total_tarik['total'], 0, ',', '.'); ?>
                            </div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon hariini">
                            <i class="fas fa-calendar-day"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo $transaksi_hari_ini['jumlah']; ?>x</h3>
                            <p>Transaksi Hari Ini</p>
                            <div class="stat-detail">
                                Rp <?php echo number_format($transaksi_hari_ini['total'], 0, ',', '.'); ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Chart -->
                <div class="chart-card">
                    <div class="chart-header">
                        <h2>
                            <i class="fas fa-chart-line"></i>
                            Grafik Transaksi 7 Hari Terakhir
                        </h2>
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
                    <!-- History Transaksi -->
                    <div class="card">
                        <div class="card-header">
                            <h2>
                                <i class="fas fa-history"></i>
                                Transaksi Terbaru
                            </h2>
                            <a href="history_transaksi.php" class="view-all">
                                Lihat Semua <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                        <div>
                            <?php if(mysqli_num_rows($result_transaksi) > 0): ?>
                                <ul class="transaction-list">
                                    <?php while($transaksi = mysqli_fetch_assoc($result_transaksi)): ?>
                                    <li class="transaction-item">
                                        <div class="transaction-icon <?php echo $transaksi['jenis_transaksi']; ?>">
                                            <i class="fas <?php echo $transaksi['jenis_transaksi'] == 'setor' ? 'fa-arrow-up' : 'fa-arrow-down'; ?>"></i>
                                        </div>
                                        <div class="transaction-detail">
                                            <div class="transaction-header">
                                                <span class="transaction-type <?php echo $transaksi['jenis_transaksi']; ?>">
                                                    <?php echo ucfirst($transaksi['jenis_transaksi']); ?>
                                                </span>
                                                <span class="transaction-amount <?php echo $transaksi['jenis_transaksi']; ?>">
                                                    Rp <?php echo number_format($transaksi['jumlah'], 0, ',', '.'); ?>
                                                </span>
                                            </div>
                                            <div class="transaction-footer">
                                                <span class="transaction-date">
                                                    <i class="fas fa-clock"></i> 
                                                    <?php echo date('d/m/Y H:i', strtotime($transaksi['tanggal'])); ?>
                                                </span>
                                                <span class="transaction-desc">
                                                    <?php echo htmlspecialchars($transaksi['keterangan'] ?: '-'); ?>
                                                </span>
                                            </div>
                                        </div>
                                    </li>
                                    <?php endwhile; ?>
                                </ul>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="fas fa-inbox"></i>
                                    <p>Belum ada transaksi</p>
                                    <a href="setor_tunai.php" class="btn btn-success">
                                        <i class="fas fa-plus-circle"></i> Mulai Menabung
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Quick Actions & Info -->
                    <div>
                        <!-- Quick Actions -->
                        <div class="card" style="margin-bottom: 25px;">
                            <div class="card-header">
                                <h2>
                                    <i class="fas fa-rocket"></i>
                                    Aksi Cepat
                                </h2>
                            </div>
                            <div class="quick-actions">
                                <a href="setor_tunai.php" class="quick-action-item">
                                    <i class="fas fa-plus-circle"></i>
                                    <span>Setor Tunai</span>
                                </a>
                                <a href="tarik_tunai.php" class="quick-action-item">
                                    <i class="fas fa-minus-circle"></i>
                                    <span>Tarik Tunai</span>
                                </a>
                                <a href="history_transaksi.php" class="quick-action-item">
                                    <i class="fas fa-history"></i>
                                    <span>History</span>
                                </a>
                                <a href="mutasi_rekening.php" class="quick-action-item">
                                    <i class="fas fa-file-alt"></i>
                                    <span>Mutasi</span>
                                </a>
                            </div>
                        </div>

                        <!-- Progress Tabungan -->
                        <div class="card" style="margin-bottom: 25px;">
                            <div class="card-header">
                                <h2>
                                    <i class="fas fa-chart-pie"></i>
                                    Progress Tabungan
                                </h2>
                            </div>
                            <div>
                                <?php
                                $target_nabung = 1000000; // Target Rp 1.000.000
                                $persentase = min(100, round(($total_saldo / $target_nabung) * 100));
                                ?>
                                <div class="progress-container">
                                    <div class="progress-label">
                                        <span>Target Rp 1.000.000</span>
                                        <span><?php echo $persentase; ?>%</span>
                                    </div>
                                    <div class="progress-bar">
                                        <div class="progress-fill" style="width: <?php echo $persentase; ?>%;"></div>
                                    </div>
                                </div>
                                <p style="color: #718096; font-size: 0.9em; margin-top: 10px;">
                                    <i class="fas fa-info-circle"></i>
                                    Anda telah mencapai <?php echo $persentase; ?>% dari target
                                </p>
                            </div>
                        </div>

                        <!-- Informasi Penting -->
                        <div class="card">
                            <div class="card-header">
                                <h2>
                                    <i class="fas fa-info-circle"></i>
                                    Informasi Penting
                                </h2>
                            </div>
                            <ul class="info-list">
                                <li>
                                    <i class="fas fa-coins"></i>
                                    Minimal setor: Rp 5.000
                                </li>
                                <li>
                                    <i class="fas fa-hand-holding-usd"></i>
                                    Maksimal tarik: Rp 500.000
                                </li>
                                <li>
                                    <i class="fas fa-clock"></i>
                                    Jam layanan: 07.00 - 15.00 WIB
                                </li>
                                <li>
                                    <i class="fas fa-phone"></i>
                                    Call center: 1500-123
                                </li>
                                <li>
                                    <i class="fas fa-calendar-check"></i>
                                    Anggota sejak: <?php echo $anggota_sejak; ?>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Info Grid Tambahan -->
                <div class="info-grid">
                    <div class="info-card">
                        <h3>
                            <i class="fas fa-shield-alt"></i>
                            Keamanan
                        </h3>
                        <ul class="info-list">
                            <li>
                                <i class="fas fa-check-circle" style="color: #48bb78;"></i>
                                Data terenkripsi dengan aman
                            </li>
                            <li>
                                <i class="fas fa-check-circle" style="color: #48bb78;"></i>
                                Transaksi tercatat real-time
                            </li>
                            <li>
                                <i class="fas fa-check-circle" style="color: #48bb78;"></i>
                                Dilindungi sistem keamanan
                            </li>
                        </ul>
                    </div>

                    <div class="info-card">
                        <h3>
                            <i class="fas fa-star"></i>
                            Tips Menabung
                        </h3>
                        <ul class="info-list">
                            <li>
                                <i class="fas fa-lightbulb" style="color: #ed8936;"></i>
                                Sisihkan 20% uang jajan
                            </li>
                            <li>
                                <i class="fas fa-lightbulb" style="color: #ed8936;"></i>
                                Catat setiap pemasukan
                            </li>
                            <li>
                                <i class="fas fa-lightbulb" style="color: #ed8936;"></i>
                                Hindari penarikan berlebihan
                            </li>
                        </ul>
                    </div>

                    <div class="info-card">
                        <h3>
                            <i class="fas fa-trophy"></i>
                            Prestasi
                        </h3>
                        <ul class="info-list">
                            <li>
                                <i class="fas fa-medal" style="color: #fbbf24;"></i>
                                Rajin menabung
                            </li>
                            <li>
                                <i class="fas fa-medal" style="color: #fbbf24;"></i>
                                <?php echo $total_transaksi; ?> kali transaksi
                            </li>
                            <li>
                                <i class="fas fa-medal" style="color: #fbbf24;"></i>
                                Saldo Rp <?php echo number_format($total_saldo, 0, ',', '.'); ?>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Data dari PHP
        const labels = <?php echo json_encode($chart_labels); ?>;
        const setorData = <?php echo json_encode($chart_setor); ?>;
        const tarikData = <?php echo json_encode($chart_tarik); ?>;
        
        // Buat chart
        const ctx = document.getElementById('transaksiChart').getContext('2d');
        new Chart(ctx, {
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
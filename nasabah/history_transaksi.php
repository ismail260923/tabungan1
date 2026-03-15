<?php
// nasabah/history_transaksi.php
include '../config/database.php';

// Cek login dan role nasabah
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'nasabah') {
    header("Location: " . BASE_URL . "login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Filter
$bulan = isset($_GET['bulan']) ? $_GET['bulan'] : date('m');
$tahun = isset($_GET['tahun']) ? $_GET['tahun'] : date('Y');
$jenis = isset($_GET['jenis']) ? $_GET['jenis'] : '';

// Query transaksi
$where = "WHERE user_id = $user_id";
if($jenis) {
    $where .= " AND jenis_transaksi = '$jenis'";
}
if($bulan && $tahun) {
    $where .= " AND MONTH(tanggal) = $bulan AND YEAR(tanggal) = $tahun";
}

$query = "SELECT * FROM tabungan $where ORDER BY tanggal DESC";
$transaksi = mysqli_query($conn, $query);

// Statistik
$statistik = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT 
        COUNT(*) as total_transaksi,
        COALESCE(SUM(CASE WHEN jenis_transaksi = 'setor' THEN jumlah ELSE 0 END), 0) as total_setor,
        COALESCE(SUM(CASE WHEN jenis_transaksi = 'tarik' THEN jumlah ELSE 0 END), 0) as total_tarik,
        COUNT(CASE WHEN jenis_transaksi = 'setor' THEN 1 END) as jumlah_setor,
        COUNT(CASE WHEN jenis_transaksi = 'tarik' THEN 1 END) as jumlah_tarik
    FROM tabungan 
    WHERE user_id = $user_id
"));

// Ambil data user
$user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id = $user_id"));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>History Transaksi - Tabungan Siswa</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
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
            border: 3px solid #4299e1;
        }

        .user-welcome .name {
            font-size: 1.2em;
            font-weight: 600;
            margin-bottom: 5px;
            color: white;
        }

        .user-welcome .role {
            color: #4299e1;
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
            background: #4299e1;
            color: white;
            border-left: 4px solid #4299e1;
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
            color: #4299e1;
        }

        .breadcrumb {
            color: #718096;
            font-size: 0.95em;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5em;
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
            font-size: 1.5em;
            color: #2d3748;
        }

        .stat-content p {
            color: #718096;
            font-size: 0.9em;
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
            font-size: 0.9em;
            font-weight: 600;
        }

        .form-control {
            width: 100%;
            padding: 10px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.95em;
        }

        .form-control:focus {
            outline: none;
            border-color: #4299e1;
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
            background: #4299e1;
            color: white;
        }

        .btn-primary:hover {
            background: #3182ce;
        }

        .btn-secondary {
            background: #a0aec0;
            color: white;
        }

        .btn-secondary:hover {
            background: #718096;
        }

        .btn-success {
            background: #48bb78;
            color: white;
        }

        .btn-success:hover {
            background: #38a169;
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

        .table tr:hover {
            background: #f7fafc;
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

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #a0aec0;
        }

        .empty-state i {
            font-size: 3em;
            margin-bottom: 15px;
        }

        .print-only {
            display: none;
        }

        @media print {
            .sidebar, .sidebar-footer, .page-header .btn, .filter-form, .card-header .btn {
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
            .form-group {
                width: 100%;
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
                <a href="history_transaksi.php" class="active"><i class="fas fa-history"></i> History Transaksi</a>
                <a href="mutasi_rekening.php"><i class="fas fa-file-alt"></i> Mutasi Rekening</a>
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
                        <h1><i class="fas fa-history"></i> History Transaksi</h1>
                        <div class="breadcrumb">
                            <a href="dashboard.php">Dashboard</a> / History Transaksi
                        </div>
                    </div>
                    <div class="date-info">
                        <i class="fas fa-calendar-alt"></i> <?php echo date('d F Y'); ?>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon total">
                            <i class="fas fa-exchange-alt"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo $statistik['total_transaksi']; ?></h3>
                            <p>Total Transaksi</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon setor">
                            <i class="fas fa-arrow-up"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo $statistik['jumlah_setor']; ?>x</h3>
                            <p>Total Setor</p>
                            <small>Rp <?php echo number_format($statistik['total_setor'], 0, ',', '.'); ?></small>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon tarik">
                            <i class="fas fa-arrow-down"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo $statistik['jumlah_tarik']; ?>x</h3>
                            <p>Total Tarik</p>
                            <small>Rp <?php echo number_format($statistik['total_tarik'], 0, ',', '.'); ?></small>
                        </div>
                    </div>
                </div>

                <!-- Filter Card -->
                <div class="card">
                    <div class="card-header">
                        <h2><i class="fas fa-filter"></i> Filter Transaksi</h2>
                        <div>
                            <button onclick="window.print()" class="btn btn-primary">
                                <i class="fas fa-print"></i> Cetak
                            </button>
                            <a href="?export=excel&<?php echo $_SERVER['QUERY_STRING']; ?>" class="btn btn-success">
                                <i class="fas fa-file-excel"></i> Export Excel
                            </a>
                        </div>
                    </div>
                    <form method="GET" class="filter-form">
                        <div class="form-group">
                            <label><i class="fas fa-calendar"></i> Bulan</label>
                            <select name="bulan" class="form-control">
                                <option value="">Semua Bulan</option>
                                <option value="01" <?php echo $bulan == '01' ? 'selected' : ''; ?>>Januari</option>
                                <option value="02" <?php echo $bulan == '02' ? 'selected' : ''; ?>>Februari</option>
                                <option value="03" <?php echo $bulan == '03' ? 'selected' : ''; ?>>Maret</option>
                                <option value="04" <?php echo $bulan == '04' ? 'selected' : ''; ?>>April</option>
                                <option value="05" <?php echo $bulan == '05' ? 'selected' : ''; ?>>Mei</option>
                                <option value="06" <?php echo $bulan == '06' ? 'selected' : ''; ?>>Juni</option>
                                <option value="07" <?php echo $bulan == '07' ? 'selected' : ''; ?>>Juli</option>
                                <option value="08" <?php echo $bulan == '08' ? 'selected' : ''; ?>>Agustus</option>
                                <option value="09" <?php echo $bulan == '09' ? 'selected' : ''; ?>>September</option>
                                <option value="10" <?php echo $bulan == '10' ? 'selected' : ''; ?>>Oktober</option>
                                <option value="11" <?php echo $bulan == '11' ? 'selected' : ''; ?>>November</option>
                                <option value="12" <?php echo $bulan == '12' ? 'selected' : ''; ?>>Desember</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-calendar-alt"></i> Tahun</label>
                            <select name="tahun" class="form-control">
                                <?php for($y = date('Y'); $y >= 2020; $y--): ?>
                                <option value="<?php echo $y; ?>" <?php echo $tahun == $y ? 'selected' : ''; ?>><?php echo $y; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-tag"></i> Jenis</label>
                            <select name="jenis" class="form-control">
                                <option value="">Semua</option>
                                <option value="setor" <?php echo $jenis == 'setor' ? 'selected' : ''; ?>>Setor</option>
                                <option value="tarik" <?php echo $jenis == 'tarik' ? 'selected' : ''; ?>>Tarik</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Filter
                            </button>
                            <a href="history_transaksi.php" class="btn btn-secondary">
                                <i class="fas fa-sync"></i> Reset
                            </a>
                        </div>
                    </form>
                </div>

                <!-- Tabel Transaksi -->
                <div class="card">
                    <div class="card-header">
                        <h2><i class="fas fa-list"></i> Daftar Transaksi</h2>
                    </div>
                    <div class="table-responsive">
                        <table id="transaksiTable" class="table display">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Tanggal</th>
                                    <th>Waktu</th>
                                    <th>Jenis</th>
                                    <th>Jumlah</th>
                                    <th>Keterangan</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $no = 1;
                                if(mysqli_num_rows($transaksi) > 0):
                                    while($row = mysqli_fetch_assoc($transaksi)):
                                ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($row['tanggal'])); ?></td>
                                    <td><?php echo date('H:i', strtotime($row['tanggal'])); ?></td>
                                    <td>
                                        <span class="badge <?php echo $row['jenis_transaksi'] == 'setor' ? 'badge-success' : 'badge-warning'; ?>">
                                            <?php echo ucfirst($row['jenis_transaksi']); ?>
                                        </span>
                                    </td>
                                    <td class="<?php echo $row['jenis_transaksi'] == 'setor' ? 'amount-setor' : 'amount-tarik'; ?>">
                                        Rp <?php echo number_format($row['jumlah'], 0, ',', '.'); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['keterangan'] ?: '-'); ?></td>
                                    <td>
                                        <span class="badge badge-success">Selesai</span>
                                    </td>
                                </tr>
                                <?php 
                                    endwhile;
                                else:
                                ?>
                                <tr>
                                    <td colspan="7" class="empty-state">
                                        <i class="fas fa-inbox"></i>
                                        <p>Tidak ada data transaksi</p>
                                        <a href="setor_tunai.php" class="btn btn-success">
                                            <i class="fas fa-plus-circle"></i> Mulai Transaksi
                                        </a>
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
    $(document).ready(function() {
        $('#transaksiTable').DataTable({
            "pageLength": 25,
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.13.4/i18n/id.json"
            },
            "order": [[1, "desc"], [2, "desc"]]
        });
    });
    </script>
</body>
</html>
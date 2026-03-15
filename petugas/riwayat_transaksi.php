<?php
include '../config/database.php';

// Cek login dan role petugas
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'petugas') {
    header("Location: ../login.php");
    exit();
}

// Filter
$tanggal_awal = isset($_GET['tanggal_awal']) ? $_GET['tanggal_awal'] : date('Y-m-01');
$tanggal_akhir = isset($_GET['tanggal_akhir']) ? $_GET['tanggal_akhir'] : date('Y-m-d');
$jenis = isset($_GET['jenis']) ? $_GET['jenis'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Query transaksi
$where = "WHERE DATE(t.tanggal) BETWEEN '$tanggal_awal' AND '$tanggal_akhir'";
if($jenis) {
    $where .= " AND t.jenis_transaksi = '$jenis'";
}
if($search) {
    $where .= " AND (u.nama_lengkap LIKE '%$search%' OR u.username LIKE '%$search%')";
}

$query = "SELECT t.*, u.nama_lengkap, u.username, u.kelas 
          FROM tabungan t 
          JOIN users u ON t.user_id = u.id 
          $where 
          ORDER BY t.tanggal DESC";

$transaksi = mysqli_query($conn, $query);

// Hitung total
$total_setor = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COALESCE(COUNT(*), 0) as jumlah, COALESCE(SUM(jumlah), 0) as total 
    FROM tabungan t 
    JOIN users u ON t.user_id = u.id 
    $where AND t.jenis_transaksi = 'setor'
"));

$total_tarik = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COALESCE(COUNT(*), 0) as jumlah, COALESCE(SUM(jumlah), 0) as total 
    FROM tabungan t 
    JOIN users u ON t.user_id = u.id 
    $where AND t.jenis_transaksi = 'tarik'
"));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Transaksi - Tabungan Siswa</title>
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
            background: #4299e1;
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
            color: #4299e1;
        }

        .breadcrumb {
            color: #718096;
            font-size: 0.95em;
        }

        .breadcrumb a {
            color: #667eea;
            text-decoration: none;
        }

        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }

        .stat-box {
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
            margin-bottom: 5px;
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

        .btn-secondary {
            background: #a0aec0;
            color: white;
        }

        .btn-secondary:hover {
            background: #718096;
        }

        .table-responsive {
            overflow-x: auto;
            margin-top: 20px;
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

        .empty-state {
            text-align: center;
            padding: 50px;
            color: #a0aec0;
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
                <a href="riwayat_transaksi.php" class="active">
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
                            <i class="fas fa-history"></i>
                            Riwayat Transaksi
                        </h1>
                        <div class="breadcrumb">
                            <a href="dashboard.php">Dashboard</a> / 
                            <span>Riwayat Transaksi</span>
                        </div>
                    </div>
                    <div class="date">
                        <i class="fas fa-calendar-alt"></i> 
                        <?php echo date('l, d F Y'); ?>
                    </div>
                </div>

                <!-- Stats -->
                <div class="stats-row">
                    <div class="stat-box">
                        <div class="stat-icon setor">
                            <i class="fas fa-arrow-up"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo $total_setor['jumlah']; ?>x</h3>
                            <p>Total Setor</p>
                            <small>Rp <?php echo number_format($total_setor['total'], 0, ',', '.'); ?></small>
                        </div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-icon tarik">
                            <i class="fas fa-arrow-down"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo $total_tarik['jumlah']; ?>x</h3>
                            <p>Total Tarik</p>
                            <small>Rp <?php echo number_format($total_tarik['total'], 0, ',', '.'); ?></small>
                        </div>
                    </div>
                </div>

                <!-- Filter Card -->
                <div class="card">
                    <div class="card-header">
                        <h2>
                            <i class="fas fa-filter"></i>
                            Filter Transaksi
                        </h2>
                    </div>
                    <form method="GET" class="filter-form">
                        <div class="form-group">
                            <label>Tanggal Awal</label>
                            <input type="date" name="tanggal_awal" class="form-control" value="<?php echo $tanggal_awal; ?>">
                        </div>
                        <div class="form-group">
                            <label>Tanggal Akhir</label>
                            <input type="date" name="tanggal_akhir" class="form-control" value="<?php echo $tanggal_akhir; ?>">
                        </div>
                        <div class="form-group">
                            <label>Jenis Transaksi</label>
                            <select name="jenis" class="form-control">
                                <option value="">Semua</option>
                                <option value="setor" <?php echo $jenis == 'setor' ? 'selected' : ''; ?>>Setor</option>
                                <option value="tarik" <?php echo $jenis == 'tarik' ? 'selected' : ''; ?>>Tarik</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Cari</label>
                            <input type="text" name="search" class="form-control" placeholder="Nama/Username" value="<?php echo $search; ?>">
                        </div>
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Filter
                            </button>
                            <a href="riwayat_transaksi.php" class="btn btn-secondary">
                                <i class="fas fa-sync"></i> Reset
                            </a>
                        </div>
                    </form>
                </div>

                <!-- Tabel Transaksi -->
<div class="card">
    <div class="card-header">
        <h2>
            <i class="fas fa-list"></i>
            Daftar Transaksi
        </h2>
        <button onclick="exportExcel()" class="btn btn-success">
            <i class="fas fa-file-excel"></i> Export Excel
        </button>
    </div>
    <div class="table-responsive">
        <table id="transaksiTable" class="table">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Tanggal</th>
                    <th>Waktu</th>
                    <th>Nasabah</th>
                    <th>Kelas</th>
                    <th>Jenis</th>
                    <th>Jumlah</th>
                    <th>Keterangan</th>
                    <th>Petugas</th>  <!-- ← PERHATIKAN: Ada 9 kolom di header -->
                </tr>
            </thead>
            <tbody>
                <?php 
                $no = 1;
                if($transaksi && mysqli_num_rows($transaksi) > 0): 
                    while($row = mysqli_fetch_assoc($transaksi)): 
                ?>
                <tr>
                    <td><?php echo $no++; ?></td>
                    <td><?php echo date('d/m/Y', strtotime($row['tanggal'])); ?></td>
                    <td><?php echo date('H:i', strtotime($row['tanggal'])); ?></td>
                    <td>
                        <strong><?php echo $row['nama_lengkap']; ?></strong><br>
                        <small><?php echo $row['username']; ?></small>
                    </td>
                    <td><?php echo $row['kelas'] ?: '-'; ?></td>
                    <td>
                        <span class="<?php echo $row['jenis_transaksi'] == 'setor' ? 'badge-success' : 'badge-warning'; ?>">
                            <?php echo ucfirst($row['jenis_transaksi']); ?>
                        </span>
                    </td>
                    <td class="<?php echo $row['jenis_transaksi'] == 'setor' ? 'amount-setor' : 'amount-tarik'; ?>">
                        Rp <?php echo number_format($row['jumlah'], 0, ',', '.'); ?>
                    </td>
                    <td><?php echo $row['keterangan'] ?: '-'; ?></td>
                    <td>Petugas</td>  <!-- ← PERHATIKAN: Ada 9 kolom di body -->
                </tr>
                <?php 
                    endwhile;
                else: 
                ?>
                <tr>
                    <td colspan="9" class="empty-state">  <!-- ← colspan="9" sudah benar -->
                        <i class="fas fa-inbox fa-3x"></i>
                        <p>Tidak ada data transaksi</p>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
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

    function exportExcel() {
        let params = new URLSearchParams(window.location.search).toString();
        window.location.href = 'export_transaksi.php?' + params;
    }
    </script>
</body>
</html>
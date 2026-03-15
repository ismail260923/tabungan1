<?php
include '../config/database.php';

// Cek login dan role petugas
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'petugas') {
    header("Location: ../login.php");
    exit();
}

$search_result = null;
$keyword = isset($_GET['keyword']) ? $_GET['keyword'] : '';

if($keyword) {
    $search = mysqli_real_escape_string($conn, $keyword);
    $query = "SELECT u.*, s.total_saldo,
              (SELECT COUNT(*) FROM tabungan WHERE user_id = u.id) as total_transaksi,
              (SELECT MAX(tanggal) FROM tabungan WHERE user_id = u.id) as transaksi_terakhir
              FROM users u 
              LEFT JOIN saldo s ON u.id = s.user_id 
              WHERE u.role = 'nasabah' 
              AND (u.nama_lengkap LIKE '%$search%' 
                   OR u.username LIKE '%$search%' 
                   OR u.nis LIKE '%$search%'
                   OR u.kelas LIKE '%$search%')
              ORDER BY u.nama_lengkap";
    $search_result = mysqli_query($conn, $query);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cari Nasabah - Tabungan Siswa</title>
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
            background: #9f7aea;
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
            color: #9f7aea;
        }

        .breadcrumb {
            color: #718096;
            font-size: 0.95em;
        }

        .breadcrumb a {
            color: #667eea;
            text-decoration: none;
        }

        .search-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        .search-box {
            display: flex;
            gap: 15px;
            max-width: 600px;
            margin: 0 auto;
        }

        .search-input {
            flex: 1;
            padding: 15px 20px;
            border: 2px solid #e2e8f0;
            border-radius: 15px;
            font-size: 1.1em;
            transition: all 0.3s;
        }

        .search-input:focus {
            outline: none;
            border-color: #9f7aea;
            box-shadow: 0 0 0 3px rgba(159, 122, 234, 0.1);
        }

        .search-btn {
            padding: 15px 30px;
            background: #9f7aea;
            color: white;
            border: none;
            border-radius: 15px;
            font-size: 1.1em;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .search-btn:hover {
            background: #805ad5;
            transform: translateY(-2px);
        }

        .result-stats {
            text-align: center;
            margin-top: 15px;
            color: #718096;
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
            padding: 15px 10px;
            border-bottom: 1px solid #e2e8f0;
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

        .btn-small {
            padding: 6px 12px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 0.9em;
            margin: 0 2px;
            display: inline-block;
            transition: all 0.3s;
        }

        .btn-small:hover {
            transform: translateY(-2px);
        }

        .btn-info {
            background: #4299e1;
            color: white;
        }

        .btn-success {
            background: #48bb78;
            color: white;
        }

        .btn-warning {
            background: #ed8936;
            color: white;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #a0aec0;
        }

        .empty-state i {
            font-size: 4em;
            margin-bottom: 20px;
        }

        .highlight {
            background: #faf089;
            padding: 2px 5px;
            border-radius: 3px;
            font-weight: 600;
        }

        @media (max-width: 768px) {
            .sidebar {
                display: none;
            }
            
            .main-content {
                margin-left: 0;
                padding: 15px;
            }
            
            .search-box {
                flex-direction: column;
            }
            
            .search-btn {
                justify-content: center;
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
                <a href="cari_nasabah.php" class="active">
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
                            <i class="fas fa-search"></i>
                            Cari Nasabah
                        </h1>
                        <div class="breadcrumb">
                            <a href="dashboard.php">Dashboard</a> / 
                            <span>Cari Nasabah</span>
                        </div>
                    </div>
                    <div class="date">
                        <i class="fas fa-calendar-alt"></i> 
                        <?php echo date('l, d F Y'); ?>
                    </div>
                </div>

                <!-- Search Card -->
                <div class="search-card">
                    <form method="GET" action="">
                        <div class="search-box">
                            <input type="text" name="keyword" class="search-input" 
                                   placeholder="Cari berdasarkan nama, username, NIS, atau kelas..." 
                                   value="<?php echo htmlspecialchars($keyword); ?>" autofocus>
                            <button type="submit" class="search-btn">
                                <i class="fas fa-search"></i>
                                Cari
                            </button>
                        </div>
                    </form>
                    <?php if($keyword): ?>
                    <div class="result-stats">
                        <i class="fas fa-database"></i> 
                        Ditemukan <?php echo $search_result ? mysqli_num_rows($search_result) : 0; ?> data untuk keyword "<?php echo htmlspecialchars($keyword); ?>"
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Hasil Pencarian -->
                <?php if($keyword): ?>
                <div class="card">
                    <div class="card-header">
                        <h2>
                            <i class="fas fa-users"></i>
                            Hasil Pencarian
                        </h2>
                    </div>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>NIS</th>
                                    <th>Nama Lengkap</th>
                                    <th>Username</th>
                                    <th>Kelas</th>
                                    <th>Saldo</th>
                                    <th>Total Transaksi</th>
                                    <th>Transaksi Terakhir</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                if($search_result && mysqli_num_rows($search_result) > 0):
                                    $no = 1;
                                    while($row = mysqli_fetch_assoc($search_result)):
                                ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td><?php echo $row['nis'] ?: '-'; ?></td>
                                    <td>
                                        <strong><?php 
                                            // Highlight keyword
                                            $nama = $row['nama_lengkap'];
                                            if($keyword) {
                                                $nama = preg_replace('/(' . preg_quote($keyword, '/') . ')/i', '<span class="highlight">$1</span>', $nama);
                                            }
                                            echo $nama;
                                        ?></strong>
                                    </td>
                                    <td><?php echo $row['username']; ?></td>
                                    <td><?php echo $row['kelas'] ?: '-'; ?></td>
                                    <td>
                                        <span class="badge-success">
                                            Rp <?php echo number_format($row['total_saldo'] ?: 0, 0, ',', '.'); ?>
                                        </span>
                                    </td>
                                    <td><?php echo $row['total_transaksi']; ?>x</td>
                                    <td>
                                        <?php 
                                        if($row['transaksi_terakhir']) {
                                            echo date('d/m/Y H:i', strtotime($row['transaksi_terakhir']));
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <a href="detail_nasabah.php?id=<?php echo $row['id']; ?>" class="btn-small btn-info" title="Detail">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="transaksi_setor.php?id=<?php echo $row['id']; ?>" class="btn-small btn-success" title="Setor">
                                            <i class="fas fa-plus-circle"></i>
                                        </a>
                                        <a href="transaksi_tarik.php?id=<?php echo $row['id']; ?>" class="btn-small btn-warning" title="Tarik">
                                            <i class="fas fa-minus-circle"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php 
                                    endwhile;
                                else:
                                ?>
                                <tr>
                                    <td colspan="9" class="empty-state">
                                        <i class="fas fa-search"></i>
                                        <h3>Tidak ada data ditemukan</h3>
                                        <p>Coba gunakan kata kunci lain atau periksa kembali pencarian Anda</p>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php else: ?>
                <!-- Default view when no search -->
                <div class="card">
                    <div class="empty-state">
                        <i class="fas fa-search"></i>
                        <h3>Mulai Pencarian</h3>
                        <p>Masukkan kata kunci untuk mencari data nasabah</p>
                        <p style="margin-top: 20px; color: #9f7aea;">
                            <i class="fas fa-info-circle"></i>
                            Anda dapat mencari berdasarkan: Nama, Username, NIS, atau Kelas
                        </p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
    // Auto focus pada input search
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelector('.search-input').focus();
    });
    </script>
</body>
</html>
<?php
include '../config/database.php';

// Cek login dan role petugas
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'petugas') {
    header("Location: ../login.php");
    exit();
}

// Ambil daftar nasabah
$nasabah = mysqli_query($conn, "SELECT u.*, s.total_saldo 
                                 FROM users u 
                                 LEFT JOIN saldo s ON u.id = s.user_id 
                                 WHERE u.role = 'nasabah' 
                                 ORDER BY u.nama_lengkap");

// Ambil transaksi hari ini
$hari_ini = date('Y-m-d');
$transaksi_hari_ini = mysqli_query($conn, "
    SELECT COUNT(*) as total, SUM(jumlah) as total_nominal 
    FROM tabungan 
    WHERE DATE(tanggal) = '$hari_ini'
");
$stat_hari_ini = mysqli_fetch_assoc($transaksi_hari_ini);

// Ambil notifikasi
$notifikasi = mysqli_query($conn, "
    SELECT t.*, u.nama_lengkap 
    FROM tabungan t 
    JOIN users u ON t.user_id = u.id 
    WHERE DATE(t.tanggal) = '$hari_ini' 
    ORDER BY t.tanggal DESC 
    LIMIT 5
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Petugas - Tabungan Siswa</title>
    <link rel="stylesheet" href="../css/style.css">
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
            padding: 20px;
        }

        .dashboard-wrapper {
            max-width: 1400px;
            margin: 0 auto;
        }

        /* Header dengan profil */
        .dashboard-header {
            background: white;
            border-radius: 20px;
            padding: 25px 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
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
        }

        .notif-badge {
            background: #f56565;
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.9em;
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
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 1.2em;
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
        }

        .logout-btn:hover {
            background: #e53e3e;
            transform: translateY(-2px);
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
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

        .stat-icon.setor {
            background: #c6f6d5;
            color: #22543d;
        }

        .stat-icon.tarik {
            background: #feebc8;
            color: #744210;
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

        .stat-detail small {
            color: #a0aec0;
            font-size: 0.9em;
        }

        /* Quick Actions */
        .quick-actions {
            background: white;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .quick-actions h2 {
            color: #2d3748;
            margin-bottom: 25px;
            font-size: 1.5em;
        }

        .action-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .action-card {
            background: #f7fafc;
            border-radius: 15px;
            padding: 25px;
            text-decoration: none;
            color: #2d3748;
            transition: all 0.3s;
            border: 2px solid transparent;
            text-align: center;
        }

        .action-card:hover {
            transform: translateY(-5px);
            border-color: #667eea;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.2);
        }

        .action-icon {
            font-size: 3em;
            margin-bottom: 15px;
        }

        .action-card h3 {
            margin-bottom: 10px;
            font-size: 1.3em;
        }

        .action-card p {
            color: #718096;
            font-size: 0.9em;
        }

        /* Content Grid */
        .content-grid {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 25px;
        }

        /* Daftar Nasabah */
        .nasabah-card {
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
        }

        .card-header h2 {
            color: #2d3748;
            font-size: 1.4em;
        }

        .search-box {
            display: flex;
            gap: 10px;
        }

        .search-box input {
            padding: 10px 15px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            width: 250px;
            font-size: 0.95em;
            transition: all 0.3s;
        }

        .search-box input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .search-box button {
            padding: 10px 20px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .search-box button:hover {
            background: #5a67d8;
        }

        .table-container {
            overflow-x: auto;
        }

        .nasabah-table {
            width: 100%;
            border-collapse: collapse;
        }

        .nasabah-table th {
            background: #f7fafc;
            color: #4a5568;
            font-weight: 600;
            padding: 15px 10px;
            text-align: left;
            font-size: 0.95em;
        }

        .nasabah-table td {
            padding: 15px 10px;
            border-bottom: 1px solid #e2e8f0;
            color: #2d3748;
        }

        .nasabah-table tr:hover {
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

        .action-group {
            display: flex;
            gap: 5px;
        }

        .btn-small {
            padding: 6px 12px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 0.85em;
            transition: all 0.3s;
            display: inline-block;
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

        /* Side Panel */
        .side-panel {
            display: flex;
            flex-direction: column;
            gap: 25px;
        }

        .notif-card, .info-card {
            background: white;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .notif-card h3, .info-card h3 {
            color: #2d3748;
            margin-bottom: 20px;
            font-size: 1.2em;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .notif-list {
            list-style: none;
        }

        .notif-item {
            padding: 15px 0;
            border-bottom: 1px solid #e2e8f0;
        }

        .notif-item:last-child {
            border-bottom: none;
        }

        .notif-item .transaksi-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }

        .notif-item .nama {
            font-weight: 600;
            color: #2d3748;
        }

        .notif-item .nominal {
            color: #48bb78;
            font-weight: 600;
        }

        .notif-item .waktu {
            color: #a0aec0;
            font-size: 0.85em;
        }

        .info-list {
            list-style: none;
        }

        .info-list li {
            padding: 10px 0;
            border-bottom: 1px solid #e2e8f0;
            color: #4a5568;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .info-list li:last-child {
            border-bottom: none;
        }

        .info-list i {
            color: #667eea;
            font-size: 1.2em;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #a0aec0;
        }

        .empty-state p {
            margin-bottom: 15px;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .dashboard-header {
                flex-direction: column;
                gap: 20px;
                text-align: center;
            }

            .header-right {
                flex-direction: column;
                width: 100%;
            }

            .user-profile {
                width: 100%;
                justify-content: center;
            }

            .search-box {
                flex-direction: column;
            }

            .search-box input {
                width: 100%;
            }

            .action-buttons {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-wrapper">
        <!-- Header dengan Profil -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1>Dashboard Petugas</h1>
                <div class="date"><?php echo date('l, d F Y'); ?></div>
            </div>
            <div class="header-right">
                <div class="notif-badge">
                    🔔 <?php echo mysqli_num_rows($notifikasi); ?> Notifikasi
                </div>
                <div class="user-profile">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($_SESSION['nama_lengkap'], 0, 1)); ?>
                    </div>
                    <div class="user-info">
                        <div class="name"><?php echo $_SESSION['nama_lengkap']; ?></div>
                        <div class="role">Petugas</div>
                    </div>
                </div>
                <a href="../logout.php" class="logout-btn">🚪 Logout</a>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon setor">💰</div>
                <div class="stat-detail">
                    <h3><?php echo $stat_hari_ini['total'] ?: 0; ?></h3>
                    <p>Transaksi Hari Ini</p>
                    <small><?php echo $stat_hari_ini['total'] ? 'Ada transaksi baru' : 'Belum ada transaksi'; ?></small>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon tarik">💳</div>
                <div class="stat-detail">
                    <h3>Rp <?php echo number_format($stat_hari_ini['total_nominal'] ?: 0, 0, ',', '.'); ?></h3>
                    <p>Total Nominal Hari Ini</p>
                    <small>Setor + Tarik</small>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <h2>Aksi Cepat</h2>
            <div class="action-buttons">
                <a href="transaksi_setor.php" class="action-card">
                    <div class="action-icon">💰</div>
                    <h3>Setor Tunai</h3>
                    <p>Lakukan penyetoran tabungan siswa</p>
                </a>
                <a href="transaksi_tarik.php" class="action-card">
                    <div class="action-icon">💳</div>
                    <h3>Tarik Tunai</h3>
                    <p>Lakukan penarikan tabungan siswa</p>
                </a>
                <a href="riwayat_transaksi.php" class="action-card">
                    <div class="action-icon">📋</div>
                    <h3>Riwayat Transaksi</h3>
                    <p>Lihat semua riwayat transaksi</p>
                </a>
                <a href="laporan_harian.php" class="action-card">
                    <div class="action-icon">📊</div>
                    <h3>Laporan Harian</h3>
                    <p>Cetak laporan transaksi hari ini</p>
                </a>
            </div>
        </div>

        <!-- Content Grid -->
        <div class="content-grid">
            <!-- Daftar Nasabah -->
            <div class="nasabah-card">
                <div class="card-header">
                    <h2>📋 Daftar Nasabah</h2>
                    <div class="search-box">
                        <input type="text" id="searchInput" placeholder="Cari nama atau username...">
                        <button onclick="searchNasabah()">Cari</button>
                    </div>
                </div>
                <div class="table-container">
                    <table class="nasabah-table" id="nasabahTable">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama Lengkap</th>
                                <th>Username</th>
                                <th>Kelas</th>
                                <th>Saldo</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = 1;
                            if(mysqli_num_rows($nasabah) > 0):
                                while($row = mysqli_fetch_assoc($nasabah)): 
                            ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td><?php echo htmlspecialchars($row['nama_lengkap']); ?></td>
                                <td><?php echo htmlspecialchars($row['username']); ?></td>
                                <td><?php echo htmlspecialchars($row['kelas'] ?: '-'); ?></td>
                                <td>
                                    <span class="badge <?php echo $row['total_saldo'] > 0 ? 'badge-success' : 'badge-warning'; ?>">
                                        Rp <?php echo number_format($row['total_saldo'] ?: 0, 0, ',', '.'); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-group">
                                        <a href="detail_nasabah.php?id=<?php echo $row['id']; ?>" class="btn-small btn-info" title="Detail">👁️</a>
                                        <a href="transaksi_setor.php?id=<?php echo $row['id']; ?>" class="btn-small btn-success" title="Setor">💰</a>
                                        <a href="transaksi_tarik.php?id=<?php echo $row['id']; ?>" class="btn-small btn-warning" title="Tarik">💳</a>
                                    </div>
                                </td>
                            </tr>
                            <?php 
                                endwhile;
                            else:
                            ?>
                            <tr>
                                <td colspan="6" class="empty-state">
                                    <p>Belum ada data nasabah</p>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Side Panel -->
            <div class="side-panel">
                <!-- Notifikasi -->
                <div class="notif-card">
                    <h3>
                        <span>🔔</span>
                        Notifikasi Hari Ini
                    </h3>
                    <?php if(mysqli_num_rows($notifikasi) > 0): ?>
                    <ul class="notif-list">
                        <?php while($notif = mysqli_fetch_assoc($notifikasi)): ?>
                        <li class="notif-item">
                            <div class="transaksi-info">
                                <span class="nama"><?php echo htmlspecialchars($notif['nama_lengkap']); ?></span>
                                <span class="nominal">Rp <?php echo number_format($notif['jumlah'], 0, ',', '.'); ?></span>
                            </div>
                            <div class="transaksi-info">
                                <span class="waktu"><?php echo date('H:i', strtotime($notif['tanggal'])); ?></span>
                                <span class="badge <?php echo $notif['jenis_transaksi'] == 'setor' ? 'badge-success' : 'badge-warning'; ?>">
                                    <?php echo ucfirst($notif['jenis_transaksi']); ?>
                                </span>
                            </div>
                        </li>
                        <?php endwhile; ?>
                    </ul>
                    <?php else: ?>
                    <div class="empty-state">
                        <p>Belum ada notifikasi</p>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Informasi -->
                <div class="info-card">
                    <h3>
                        <span>ℹ️</span>
                        Informasi
                    </h3>
                    <ul class="info-list">
                        <li>
                            <i>💰</i>
                            Minimal setor: Rp 5.000
                        </li>
                        <li>
                            <i>💳</i>
                            Maksimal tarik: Rp 500.000
                        </li>
                        <li>
                            <i>⏰</i>
                            Jam layanan: 07.00 - 15.00
                        </li>
                        <li>
                            <i>📞</i>
                            Call center: 1500-123
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Fungsi pencarian
    function searchNasabah() {
        let input = document.getElementById('searchInput').value.toLowerCase();
        let table = document.getElementById('nasabahTable');
        let rows = table.getElementsByTagName('tr');
        
        for(let i = 1; i < rows.length; i++) {
            let nama = rows[i].getElementsByTagName('td')[1]?.textContent.toLowerCase();
            let username = rows[i].getElementsByTagName('td')[2]?.textContent.toLowerCase();
            
            if(nama && (nama.includes(input) || username.includes(input))) {
                rows[i].style.display = '';
            } else if(rows[i]) {
                rows[i].style.display = 'none';
            }
        }
    }

    // Enter key untuk pencarian
    document.getElementById('searchInput').addEventListener('keyup', function(e) {
        if(e.key === 'Enter') {
            searchNasabah();
        }
    });

    // Auto refresh notifikasi setiap 30 detik
    setInterval(function() {
        location.reload();
    }, 30000);
    </script>
</body>
</html>
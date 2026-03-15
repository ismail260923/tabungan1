<?php
// admin/laporan.php
include '../config/database.php';

// Cek login dan role admin
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

// Ambil parameter
$bulan = isset($_GET['bulan']) ? $_GET['bulan'] : date('m');
$tahun = isset($_GET['tahun']) ? $_GET['tahun'] : date('Y');
$laporan_jenis = isset($_GET['laporan_jenis']) ? $_GET['laporan_jenis'] : 'harian';

// Data untuk chart
$chart_labels = [];
$chart_setor = [];
$chart_tarik = [];

if($laporan_jenis == 'harian') {
    // Data per hari dalam bulan ini
    $days = cal_days_in_month(CAL_GREGORIAN, $bulan, $tahun);
    for($i = 1; $i <= $days; $i++) {
        $tanggal = sprintf("%04d-%02d-%02d", $tahun, $bulan, $i);
        $chart_labels[] = $i;
        
        // Setor
        $setor = mysqli_fetch_assoc(mysqli_query($conn, "
            SELECT COALESCE(SUM(jumlah), 0) as total 
            FROM tabungan 
            WHERE DATE(tanggal) = '$tanggal' AND jenis_transaksi = 'setor'
        "));
        $chart_setor[] = $setor['total'];
        
        // Tarik
        $tarik = mysqli_fetch_assoc(mysqli_query($conn, "
            SELECT COALESCE(SUM(jumlah), 0) as total 
            FROM tabungan 
            WHERE DATE(tanggal) = '$tanggal' AND jenis_transaksi = 'tarik'
        "));
        $chart_tarik[] = $tarik['total'];
    }
} elseif($laporan_jenis == 'bulanan') {
    // Data per bulan dalam tahun ini
    $bulan_names = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    for($i = 1; $i <= 12; $i++) {
        $chart_labels[] = $bulan_names[$i-1];
        
        // Setor
        $setor = mysqli_fetch_assoc(mysqli_query($conn, "
            SELECT COALESCE(SUM(jumlah), 0) as total 
            FROM tabungan 
            WHERE MONTH(tanggal) = $i AND YEAR(tanggal) = $tahun AND jenis_transaksi = 'setor'
        "));
        $chart_setor[] = $setor['total'];
        
        // Tarik
        $tarik = mysqli_fetch_assoc(mysqli_query($conn, "
            SELECT COALESCE(SUM(jumlah), 0) as total 
            FROM tabungan 
            WHERE MONTH(tanggal) = $i AND YEAR(tanggal) = $tahun AND jenis_transaksi = 'tarik'
        "));
        $chart_tarik[] = $tarik['total'];
    }
}

// Statistik umum
$total_nasabah = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE role='nasabah'"))['total'];
$total_petugas = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE role='petugas'"))['total'];
$total_transaksi = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM tabungan"))['total'];
$total_saldo = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(total_saldo) as total FROM saldo"))['total'];

// 10 Nasabah dengan saldo tertinggi
$top_nasabah = mysqli_query($conn, "
    SELECT u.nama_lengkap, u.username, u.kelas, s.total_saldo 
    FROM users u 
    JOIN saldo s ON u.id = s.user_id 
    WHERE u.role = 'nasabah' 
    ORDER BY s.total_saldo DESC 
    LIMIT 10
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan - Tabungan Siswa</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h2>Tabungan Siswa</h2>
                <p>Admin Panel</p>
            </div>
            <div class="sidebar-menu">
                <a href="dashboard.php">📊 Dashboard</a>
                <a href="kelola_petugas.php">👥 Kelola Petugas</a>
                <a href="kelola_nasabah.php">👤 Kelola Nasabah</a>
                <a href="transaksi.php">💰 Semua Transaksi</a>
                <a href="laporan.php" class="active">📈 Laporan</a>
                <a href="pengaturan.php">⚙️ Pengaturan</a>
                <a href="../logout.php">🚪 Logout</a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <h1>Laporan Tabungan Siswa</h1>
                <div class="date"><?php echo date('d F Y'); ?></div>
            </div>

            <!-- Filter Laporan -->
            <div class="card">
                <div class="card-header">
                    <h2>Filter Laporan</h2>
                </div>
                <div class="card-body">
                    <form method="GET" class="filter-form">
                        <div class="filter-grid">
                            <div class="form-group">
                                <label>Jenis Laporan</label>
                                <select name="laporan_jenis" id="laporan_jenis">
                                    <option value="harian" <?php echo $laporan_jenis == 'harian' ? 'selected' : ''; ?>>Harian</option>
                                    <option value="bulanan" <?php echo $laporan_jenis == 'bulanan' ? 'selected' : ''; ?>>Bulanan</option>
                                    <option value="tahunan" <?php echo $laporan_jenis == 'tahunan' ? 'selected' : ''; ?>>Tahunan</option>
                                </select>
                            </div>
                            <div class="form-group" id="bulan_group" style="<?php echo $laporan_jenis == 'tahunan' ? 'display:none;' : ''; ?>">
                                <label>Bulan</label>
                                <select name="bulan">
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
                                <label>Tahun</label>
                                <select name="tahun">
                                    <?php for($y = date('Y'); $y >= 2020; $y--): ?>
                                    <option value="<?php echo $y; ?>" <?php echo $tahun == $y ? 'selected' : ''; ?>><?php echo $y; ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <button type="submit" class="btn btn-primary">Tampilkan</button>
                                <button type="button" class="btn btn-success" onclick="exportLaporan()">📥 Export PDF</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Statistik Umum -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">👤</div>
                    <div class="stat-detail">
                        <h3><?php echo $total_nasabah; ?></h3>
                        <p>Total Nasabah</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">👥</div>
                    <div class="stat-detail">
                        <h3><?php echo $total_petugas; ?></h3>
                        <p>Total Petugas</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">📊</div>
                    <div class="stat-detail">
                        <h3><?php echo $total_transaksi; ?></h3>
                        <p>Total Transaksi</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">💰</div>
                    <div class="stat-detail">
                        <h3>Rp <?php echo number_format($total_saldo, 0, ',', '.'); ?></h3>
                        <p>Total Saldo</p>
                    </div>
                </div>
            </div>

            <!-- Grafik Transaksi -->
            <div class="card">
                <div class="card-header">
                    <h2>Grafik Transaksi <?php echo $laporan_jenis == 'harian' ? 'Harian' : 'Bulanan'; ?></h2>
                </div>
                <div class="card-body">
                    <canvas id="transaksiChart" style="width:100%; max-height:400px;"></canvas>
                </div>
            </div>

            <!-- Top Nasabah -->
            <div class="card">
                <div class="card-header">
                    <h2>10 Nasabah dengan Saldo Tertinggi</h2>
                </div>
                <div class="card-body">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama</th>
                                <th>Username</th>
                                <th>Kelas</th>
                                <th>Saldo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = 1;
                            while($row = mysqli_fetch_assoc($top_nasabah)): 
                            ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td><?php echo $row['nama_lengkap']; ?></td>
                                <td><?php echo $row['username']; ?></td>
                                <td><?php echo $row['kelas'] ?: '-'; ?></td>
                                <td>Rp <?php echo number_format($row['total_saldo'], 0, ',', '.'); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Toggle bulan based on laporan jenis
    $('#laporan_jenis').change(function() {
        if($(this).val() == 'tahunan') {
            $('#bulan_group').hide();
        } else {
            $('#bulan_group').show();
        }
    });

    // Chart
    const ctx = document.getElementById('transaksiChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($chart_labels); ?>,
            datasets: [{
                label: 'Setor',
                data: <?php echo json_encode($chart_setor); ?>,
                borderColor: 'rgb(72, 187, 120)',
                backgroundColor: 'rgba(72, 187, 120, 0.1)',
                tension: 0.1
            }, {
                label: 'Tarik',
                data: <?php echo json_encode($chart_tarik); ?>,
                borderColor: 'rgb(237, 137, 54)',
                backgroundColor: 'rgba(237, 137, 54, 0.1)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return 'Rp ' + value.toLocaleString('id-ID');
                        }
                    }
                }
            }
        }
    });

    function exportLaporan() {
        let params = new URLSearchParams(window.location.search).toString();
        window.location.href = 'export_laporan.php?' + params;
    }
    </script>
</body>
</html>
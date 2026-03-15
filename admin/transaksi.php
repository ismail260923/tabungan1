<?php
// admin/transaksi.php
include '../config/database.php';

// Cek login dan role admin
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
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
    SELECT COALESCE(SUM(jumlah), 0) as total 
    FROM tabungan t 
    JOIN users u ON t.user_id = u.id 
    $where AND t.jenis_transaksi = 'setor'
"))['total'];

$total_tarik = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COALESCE(SUM(jumlah), 0) as total 
    FROM tabungan t 
    JOIN users u ON t.user_id = u.id 
    $where AND t.jenis_transaksi = 'tarik'
"))['total'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Semua Transaksi - Tabungan Siswa</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- DataTables -->
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
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
                <a href="transaksi.php" class="active">💰 Semua Transaksi</a>
                <a href="laporan.php">📈 Laporan</a>
                <a href="pengaturan.php">⚙️ Pengaturan</a>
                <a href="../logout.php">🚪 Logout</a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <h1>Semua Transaksi</h1>
                <div class="date"><?php echo date('d F Y'); ?></div>
            </div>

            <!-- Ringkasan -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">💰</div>
                    <div class="stat-detail">
                        <h3>Rp <?php echo number_format($total_setor, 0, ',', '.'); ?></h3>
                        <p>Total Setoran</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">💳</div>
                    <div class="stat-detail">
                        <h3>Rp <?php echo number_format($total_tarik, 0, ',', '.'); ?></h3>
                        <p>Total Penarikan</p>
                    </div>
                </div>
            </div>

            <!-- Filter Form -->
            <div class="card">
                <div class="card-header">
                    <h2>Filter Transaksi</h2>
                </div>
                <div class="card-body">
                    <form method="GET" class="filter-form">
                        <div class="filter-grid">
                            <div class="form-group">
                                <label>Tanggal Awal</label>
                                <input type="date" name="tanggal_awal" value="<?php echo $tanggal_awal; ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Tanggal Akhir</label>
                                <input type="date" name="tanggal_akhir" value="<?php echo $tanggal_akhir; ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Jenis Transaksi</label>
                                <select name="jenis">
                                    <option value="">Semua</option>
                                    <option value="setor" <?php echo $jenis == 'setor' ? 'selected' : ''; ?>>Setor</option>
                                    <option value="tarik" <?php echo $jenis == 'tarik' ? 'selected' : ''; ?>>Tarik</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Cari Nasabah</label>
                                <input type="text" name="search" placeholder="Nama / Username" value="<?php echo $search; ?>">
                            </div>
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <button type="submit" class="btn btn-primary">Filter</button>
                                <a href="transaksi.php" class="btn btn-secondary">Reset</a>
                                <button type="button" class="btn btn-success" onclick="exportExcel()">📥 Export Excel</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tabel Transaksi -->
            <div class="card">
                <div class="card-header">
                    <h2>Daftar Transaksi</h2>
                </div>
                <div class="card-body">
                    <table id="transaksiTable" class="display">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Tanggal</th>
                                <th>Nasabah</th>
                                <th>Kelas</th>
                                <th>Jenis</th>
                                <th>Jumlah</th>
                                <th>Keterangan</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = 1;
                            while($row = mysqli_fetch_assoc($transaksi)): 
                            ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($row['tanggal'])); ?></td>
                                <td>
                                    <?php echo $row['nama_lengkap']; ?><br>
                                    <small><?php echo $row['username']; ?></small>
                                </td>
                                <td><?php echo $row['kelas'] ?: '-'; ?></td>
                                <td>
                                    <span class="badge <?php echo $row['jenis_transaksi'] == 'setor' ? 'badge-success' : 'badge-warning'; ?>">
                                        <?php echo ucfirst($row['jenis_transaksi']); ?>
                                    </span>
                                </td>
                                <td>Rp <?php echo number_format($row['jumlah'], 0, ',', '.'); ?></td>
                                <td><?php echo $row['keterangan'] ?: '-'; ?></td>
                                <td>
                                    <button class="btn-small btn-info" onclick="viewDetail(<?php echo $row['id']; ?>)">Detail</button>
                                    <button class="btn-small btn-danger" onclick="hapusTransaksi(<?php echo $row['id']; ?>)">Hapus</button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
    $(document).ready(function() {
        $('#transaksiTable').DataTable({
            "pageLength": 25,
            "order": [[1, "desc"]],
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.13.4/i18n/id.json"
            }
        });
    });

    function viewDetail(id) {
        window.location.href = 'detail_transaksi.php?id=' + id;
    }

    function hapusTransaksi(id) {
        Swal.fire({
            title: 'Hapus Transaksi?',
            text: 'Data transaksi akan dihapus permanen!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'hapus_transaksi.php?id=' + id;
            }
        });
    }

    function exportExcel() {
        let params = new URLSearchParams(window.location.search).toString();
        window.location.href = 'export_transaksi.php?' + params;
    }
    </script>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</body>
</html>
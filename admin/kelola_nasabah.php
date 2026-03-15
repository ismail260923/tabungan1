<?php
// admin/kelola_nasabah.php
include '../config/database.php';

// Cek login dan role admin
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

// Inisialisasi variabel
$success_message = '';
$error_message = '';

// ============================================
// PROSES TAMBAH NASABAH
// ============================================
if(isset($_POST['tambah'])) {
    $nama_lengkap = mysqli_real_escape_string($conn, $_POST['nama_lengkap']);
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $no_telepon = mysqli_real_escape_string($conn, $_POST['no_telepon']);
    $kelas = mysqli_real_escape_string($conn, $_POST['kelas']);
    $nis = mysqli_real_escape_string($conn, $_POST['nis']);
    $alamat = mysqli_real_escape_string($conn, $_POST['alamat']);
    $password_default = 'nasabah123';
    $password_hash = password_hash($password_default, PASSWORD_DEFAULT);
    
    // Validasi username
    $check_username = mysqli_query($conn, "SELECT id FROM users WHERE username = '$username'");
    if(mysqli_num_rows($check_username) > 0) {
        $error_message = "Username sudah digunakan!";
    }
    // Validasi email
    elseif(mysqli_num_rows(mysqli_query($conn, "SELECT id FROM users WHERE email = '$email'")) > 0) {
        $error_message = "Email sudah digunakan!";
    }
    // Validasi NIS
    elseif(mysqli_num_rows(mysqli_query($conn, "SELECT id FROM users WHERE nis = '$nis' AND nis != ''")) > 0) {
        $error_message = "NIS sudah terdaftar!";
    }
    else {
        // Insert ke tabel users
        $query = "INSERT INTO users (nama_lengkap, username, password, email, no_telepon, nis, kelas, alamat, role) 
                  VALUES ('$nama_lengkap', '$username', '$password_hash', '$email', '$no_telepon', '$nis', '$kelas', '$alamat', 'nasabah')";
        
        if(mysqli_query($conn, $query)) {
            $nasabah_id = mysqli_insert_id($conn);
            
            // Buat saldo awal
            $saldo_awal = str_replace('.', '', $_POST['saldo_awal']);
            $saldo_awal = $saldo_awal ?: 0;
            
            mysqli_query($conn, "INSERT INTO saldo (user_id, total_saldo) VALUES ($nasabah_id, $saldo_awal)");
            
            $success_message = "Nasabah berhasil ditambahkan! Password default: <strong>$password_default</strong>";
        } else {
            $error_message = "Gagal menambahkan nasabah: " . mysqli_error($conn);
        }
    }
}

// ============================================
// PROSES EDIT NASABAH
// ============================================
if(isset($_POST['edit'])) {
    $id = $_POST['id'];
    $nama_lengkap = mysqli_real_escape_string($conn, $_POST['nama_lengkap']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $no_telepon = mysqli_real_escape_string($conn, $_POST['no_telepon']);
    $kelas = mysqli_real_escape_string($conn, $_POST['kelas']);
    $nis = mysqli_real_escape_string($conn, $_POST['nis']);
    $alamat = mysqli_real_escape_string($conn, $_POST['alamat']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    
    // Cek email sudah digunakan nasabah lain
    $check_email = mysqli_query($conn, "SELECT id FROM users WHERE email = '$email' AND id != $id");
    if(mysqli_num_rows($check_email) > 0) {
        $error_message = "Email sudah digunakan nasabah lain!";
    } else {
        $query = "UPDATE users SET 
                  nama_lengkap = '$nama_lengkap',
                  email = '$email',
                  no_telepon = '$no_telepon',
                  nis = '$nis',
                  kelas = '$kelas',
                  alamat = '$alamat',
                  status = '$status'
                  WHERE id = $id AND role = 'nasabah'";
        
        if(mysqli_query($conn, $query)) {
            $success_message = "Data nasabah berhasil diupdate!";
        } else {
            $error_message = "Gagal mengupdate nasabah: " . mysqli_error($conn);
        }
    }
}

// ============================================
// PROSES HAPUS NASABAH
// ============================================
if(isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    
    // Hapus data terkait (transaksi dan saldo)
    mysqli_query($conn, "DELETE FROM tabungan WHERE user_id = $id");
    mysqli_query($conn, "DELETE FROM saldo WHERE user_id = $id");
    
    // Hapus user
    $query = "DELETE FROM users WHERE id = $id AND role = 'nasabah'";
    if(mysqli_query($conn, $query)) {
        $success_message = "Nasabah berhasil dihapus!";
    } else {
        $error_message = "Gagal menghapus nasabah: " . mysqli_error($conn);
    }
}

// ============================================
// PROSES RESET PASSWORD
// ============================================
if(isset($_GET['reset'])) {
    $id = $_GET['reset'];
    $password_default = 'nasabah123';
    $password_hash = password_hash($password_default, PASSWORD_DEFAULT);
    
    $query = "UPDATE users SET password = '$password_hash' WHERE id = $id AND role = 'nasabah'";
    if(mysqli_query($conn, $query)) {
        $success_message = "Password nasabah berhasil direset! Password baru: <strong>$password_default</strong>";
    } else {
        $error_message = "Gagal mereset password: " . mysqli_error($conn);
    }
}

// ============================================
// PROSES TAMBAH SALDO
// ============================================
if(isset($_POST['tambah_saldo'])) {
    $id = $_POST['id'];
    $jumlah_tambah = str_replace('.', '', $_POST['jumlah_tambah']);
    $keterangan = mysqli_real_escape_string($conn, $_POST['keterangan_tambah']);
    
    // Mulai transaksi
    mysqli_begin_transaction($conn);
    
    try {
        // Update saldo
        mysqli_query($conn, "UPDATE saldo SET total_saldo = total_saldo + $jumlah_tambah WHERE user_id = $id");
        
        // Catat transaksi
        mysqli_query($conn, "INSERT INTO tabungan (user_id, jenis_transaksi, jumlah, keterangan) 
                            VALUES ($id, 'setor', $jumlah_tambah, 'Tambah saldo oleh admin: $keterangan')");
        
        mysqli_commit($conn);
        $success_message = "Saldo berhasil ditambahkan!";
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $error_message = "Gagal menambah saldo: " . $e->getMessage();
    }
}

// ============================================
// PROSES KURANG SALDO
// ============================================
if(isset($_POST['kurang_saldo'])) {
    $id = $_POST['id'];
    $jumlah_kurang = str_replace('.', '', $_POST['jumlah_kurang']);
    $keterangan = mysqli_real_escape_string($conn, $_POST['keterangan_kurang']);
    
    // Cek saldo cukup
    $cek_saldo = mysqli_fetch_assoc(mysqli_query($conn, "SELECT total_saldo FROM saldo WHERE user_id = $id"));
    if($cek_saldo['total_saldo'] < $jumlah_kurang) {
        $error_message = "Saldo tidak mencukupi!";
    } else {
        // Mulai transaksi
        mysqli_begin_transaction($conn);
        
        try {
            // Update saldo
            mysqli_query($conn, "UPDATE saldo SET total_saldo = total_saldo - $jumlah_kurang WHERE user_id = $id");
            
            // Catat transaksi
            mysqli_query($conn, "INSERT INTO tabungan (user_id, jenis_transaksi, jumlah, keterangan) 
                                VALUES ($id, 'tarik', $jumlah_kurang, 'Pengurangan saldo oleh admin: $keterangan')");
            
            mysqli_commit($conn);
            $success_message = "Saldo berhasil dikurangi!";
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $error_message = "Gagal mengurangi saldo: " . $e->getMessage();
        }
    }
}

// ============================================
// AMBIL DATA NASABAH
// ============================================
$search = isset($_GET['search']) ? $_GET['search'] : '';
$kelas_filter = isset($_GET['kelas']) ? $_GET['kelas'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

$where = "WHERE u.role = 'nasabah'";
if($search) {
    $where .= " AND (u.nama_lengkap LIKE '%$search%' OR u.username LIKE '%$search%' OR u.nis LIKE '%$search%')";
}
if($kelas_filter) {
    $where .= " AND u.kelas = '$kelas_filter'";
}
if($status_filter) {
    $where .= " AND u.status = '$status_filter'";
}

$query = "SELECT u.*, s.total_saldo,
          (SELECT COUNT(*) FROM tabungan WHERE user_id = u.id) as total_transaksi,
          (SELECT MAX(tanggal) FROM tabungan WHERE user_id = u.id) as transaksi_terakhir
          FROM users u 
          LEFT JOIN saldo s ON u.id = s.user_id 
          $where 
          ORDER BY u.created_at DESC";

$nasabah = mysqli_query($conn, $query);

// Ambil daftar kelas untuk filter
$kelas_list = mysqli_query($conn, "SELECT DISTINCT kelas FROM users WHERE role = 'nasabah' AND kelas != '' ORDER BY kelas");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Nasabah - Tabungan Siswa</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- DataTables -->
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
                <a href="kelola_nasabah.php" class="active">👤 Kelola Nasabah</a>
                <a href="transaksi.php">💰 Semua Transaksi</a>
                <a href="laporan.php">📈 Laporan</a>
                <a href="pengaturan.php">⚙️ Pengaturan</a>
                <a href="../logout.php">🚪 Logout</a>
            </div>
            <div class="sidebar-footer">
                <p>Login sebagai: <strong><?php echo $_SESSION['nama_lengkap']; ?></strong></p>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <h1>Kelola Nasabah</h1>
                <div class="date"><?php echo date('d F Y'); ?></div>
            </div>

            <!-- Alert Messages -->
            <?php if($success_message): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <?php if($error_message): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <!-- Filter dan Search -->
            <div class="card">
                <div class="card-header">
                    <h2>Filter Data Nasabah</h2>
                </div>
                <div class="card-body">
                    <form method="GET" class="filter-form">
                        <div class="filter-grid">
                            <div class="form-group">
                                <label>Cari</label>
                                <input type="text" name="search" placeholder="Nama / Username / NIS" value="<?php echo $search; ?>">
                            </div>
                            <div class="form-group">
                                <label>Filter Kelas</label>
                                <select name="kelas">
                                    <option value="">Semua Kelas</option>
                                    <?php while($row = mysqli_fetch_assoc($kelas_list)): ?>
                                    <option value="<?php echo $row['kelas']; ?>" <?php echo $kelas_filter == $row['kelas'] ? 'selected' : ''; ?>>
                                        <?php echo $row['kelas']; ?>
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Filter Status</label>
                                <select name="status">
                                    <option value="">Semua Status</option>
                                    <option value="aktif" <?php echo $status_filter == 'aktif' ? 'selected' : ''; ?>>Aktif</option>
                                    <option value="nonaktif" <?php echo $status_filter == 'nonaktif' ? 'selected' : ''; ?>>Nonaktif</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <button type="submit" class="btn btn-primary">Filter</button>
                                <a href="kelola_nasabah.php" class="btn btn-secondary">Reset</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tombol Tambah -->
            <div class="card">
                <div class="card-header">
                    <h2>Daftar Nasabah</h2>
                    <button class="btn btn-success" onclick="openTambahModal()">➕ Tambah Nasabah Baru</button>
                </div>
                <div class="card-body">
                    <table id="nasabahTable" class="display">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>NIS</th>
                                <th>Nama Lengkap</th>
                                <th>Username</th>
                                <th>Kelas</th>
                                <th>Email</th>
                                <th>No. Telepon</th>
                                <th>Saldo</th>
                                <th>Total Transaksi</th>
                                <th>Transaksi Terakhir</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = 1;
                            while($row = mysqli_fetch_assoc($nasabah)): 
                            ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td><?php echo $row['nis'] ?: '-'; ?></td>
                                <td><?php echo $row['nama_lengkap']; ?></td>
                                <td><?php echo $row['username']; ?></td>
                                <td><?php echo $row['kelas'] ?: '-'; ?></td>
                                <td><?php echo $row['email']; ?></td>
                                <td><?php echo $row['no_telepon'] ?: '-'; ?></td>
                                <td>Rp <?php echo number_format($row['total_saldo'] ?: 0, 0, ',', '.'); ?></td>
                                <td><?php echo $row['total_transaksi']; ?>x</td>
                                <td><?php echo $row['transaksi_terakhir'] ? date('d/m/Y', strtotime($row['transaksi_terakhir'])) : '-'; ?></td>
                                <td>
                                    <span class="badge <?php echo $row['status'] == 'aktif' ? 'badge-success' : 'badge-danger'; ?>">
                                        <?php echo ucfirst($row['status'] ?: 'aktif'); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn-small btn-info" onclick="viewNasabah(<?php echo $row['id']; ?>)">👁️</button>
                                        <button class="btn-small btn-primary" onclick="editNasabah(<?php echo $row['id']; ?>)">✏️</button>
                                        <button class="btn-small btn-success" onclick="tambahSaldo(<?php echo $row['id']; ?>, '<?php echo $row['nama_lengkap']; ?>', <?php echo $row['total_saldo']; ?>)">➕</button>
                                        <button class="btn-small btn-warning" onclick="kurangSaldo(<?php echo $row['id']; ?>, '<?php echo $row['nama_lengkap']; ?>', <?php echo $row['total_saldo']; ?>)">➖</button>
                                        <button class="btn-small btn-danger" onclick="hapusNasabah(<?php echo $row['id']; ?>, '<?php echo $row['nama_lengkap']; ?>')">🗑️</button>
                                        <button class="btn-small btn-secondary" onclick="resetPassword(<?php echo $row['id']; ?>, '<?php echo $row['nama_lengkap']; ?>')">🔄</button>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Tambah Nasabah -->
    <div id="tambahModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Tambah Nasabah Baru</h2>
                <span class="close" onclick="closeTambahModal()">&times;</span>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="form-group">
                        <label>NIS <span class="text-danger">*</span></label>
                        <input type="text" name="nis" required placeholder="Nomor Induk Siswa">
                    </div>
                    <div class="form-group">
                        <label>Nama Lengkap <span class="text-danger">*</span></label>
                        <input type="text" name="nama_lengkap" required>
                    </div>
                    <div class="form-group">
                        <label>Username <span class="text-danger">*</span></label>
                        <input type="text" name="username" required>
                    </div>
                    <div class="form-group">
                        <label>Email <span class="text-danger">*</span></label>
                        <input type="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label>No. Telepon</label>
                        <input type="text" name="no_telepon">
                    </div>
                    <div class="form-group">
                        <label>Kelas</label>
                        <input type="text" name="kelas" placeholder="Contoh: X IPA 1">
                    </div>
                    <div class="form-group">
                        <label>Alamat</label>
                        <textarea name="alamat" rows="2"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Saldo Awal</label>
                        <input type="text" name="saldo_awal" class="format-rupiah" value="0">
                    </div>
                    <div class="form-group">
                        <p class="text-info">Password default: <strong>nasabah123</strong></p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeTambahModal()">Batal</button>
                    <button type="submit" name="tambah" class="btn btn-success">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Edit Nasabah -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Data Nasabah</h2>
                <span class="close" onclick="closeEditModal()">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-body">
                    <div class="form-group">
                        <label>NIS</label>
                        <input type="text" name="nis" id="edit_nis" required>
                    </div>
                    <div class="form-group">
                        <label>Nama Lengkap</label>
                        <input type="text" name="nama_lengkap" id="edit_nama" required>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" id="edit_email" required>
                    </div>
                    <div class="form-group">
                        <label>No. Telepon</label>
                        <input type="text" name="no_telepon" id="edit_telepon">
                    </div>
                    <div class="form-group">
                        <label>Kelas</label>
                        <input type="text" name="kelas" id="edit_kelas">
                    </div>
                    <div class="form-group">
                        <label>Alamat</label>
                        <textarea name="alamat" id="edit_alamat" rows="2"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" id="edit_status">
                            <option value="aktif">Aktif</option>
                            <option value="nonaktif">Nonaktif</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Batal</button>
                    <button type="submit" name="edit" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Tambah Saldo -->
    <div id="tambahSaldoModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Tambah Saldo Nasabah</h2>
                <span class="close" onclick="closeTambahSaldoModal()">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="id" id="saldo_id">
                <div class="modal-body">
                    <div class="info-saldo">
                        <h3 id="saldo_nama"></h3>
                        <p>Saldo Saat Ini: <strong id="saldo_sekarang">Rp 0</strong></p>
                    </div>
                    <div class="form-group">
                        <label>Jumlah Tambah</label>
                        <input type="text" name="jumlah_tambah" class="format-rupiah" required>
                    </div>
                    <div class="form-group">
                        <label>Keterangan</label>
                        <textarea name="keterangan_tambah" rows="2" placeholder="Contoh: Tambahan saldo dari admin"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeTambahSaldoModal()">Batal</button>
                    <button type="submit" name="tambah_saldo" class="btn btn-success">Tambah Saldo</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Kurang Saldo -->
    <div id="kurangSaldoModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Kurangi Saldo Nasabah</h2>
                <span class="close" onclick="closeKurangSaldoModal()">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="id" id="kurang_id">
                <div class="modal-body">
                    <div class="info-saldo">
                        <h3 id="kurang_nama"></h3>
                        <p>Saldo Saat Ini: <strong id="kurang_saldo">Rp 0</strong></p>
                    </div>
                    <div class="form-group">
                        <label>Jumlah Kurang</label>
                        <input type="text" name="jumlah_kurang" class="format-rupiah" required>
                    </div>
                    <div class="form-group">
                        <label>Keterangan</label>
                        <textarea name="keterangan_kurang" rows="2" placeholder="Contoh: Penarikan oleh admin"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeKurangSaldoModal()">Batal</button>
                    <button type="submit" name="kurang_saldo" class="btn btn-warning">Kurangi Saldo</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    // DataTable
    $(document).ready(function() {
        $('#nasabahTable').DataTable({
            "pageLength": 25,
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.13.4/i18n/id.json"
            }
        });
    });

    // Format Rupiah
    document.querySelectorAll('.format-rupiah').forEach(input => {
        input.addEventListener('keyup', function(e) {
            let value = this.value.replace(/\D/g, '');
            this.value = value.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        });
    });

    // Modal Functions
    function openTambahModal() {
        document.getElementById('tambahModal').style.display = 'block';
    }

    function closeTambahModal() {
        document.getElementById('tambahModal').style.display = 'none';
    }

    function closeEditModal() {
        document.getElementById('editModal').style.display = 'none';
    }

    function closeTambahSaldoModal() {
        document.getElementById('tambahSaldoModal').style.display = 'none';
    }

    function closeKurangSaldoModal() {
        document.getElementById('kurangSaldoModal').style.display = 'none';
    }

    // View Nasabah
    function viewNasabah(id) {
        window.location.href = 'detail_nasabah.php?id=' + id;
    }

    // Edit Nasabah
    function editNasabah(id) {
        $.ajax({
            url: 'get_nasabah.php',
            type: 'GET',
            data: {id: id},
            dataType: 'json',
            success: function(data) {
                document.getElementById('edit_id').value = data.id;
                document.getElementById('edit_nis').value = data.nis;
                document.getElementById('edit_nama').value = data.nama_lengkap;
                document.getElementById('edit_email').value = data.email;
                document.getElementById('edit_telepon').value = data.no_telepon;
                document.getElementById('edit_kelas').value = data.kelas;
                document.getElementById('edit_alamat').value = data.alamat;
                document.getElementById('edit_status').value = data.status || 'aktif';
                document.getElementById('editModal').style.display = 'block';
            }
        });
    }

    // Tambah Saldo
    function tambahSaldo(id, nama, saldo) {
        document.getElementById('saldo_id').value = id;
        document.getElementById('saldo_nama').innerHTML = 'Nasabah: ' + nama;
        document.getElementById('saldo_sekarang').innerHTML = 'Rp ' + new Intl.NumberFormat('id-ID').format(saldo);
        document.getElementById('tambahSaldoModal').style.display = 'block';
    }

    // Kurang Saldo
    function kurangSaldo(id, nama, saldo) {
        document.getElementById('kurang_id').value = id;
        document.getElementById('kurang_nama').innerHTML = 'Nasabah: ' + nama;
        document.getElementById('kurang_saldo').innerHTML = 'Rp ' + new Intl.NumberFormat('id-ID').format(saldo);
        document.getElementById('kurangSaldoModal').style.display = 'block';
    }

    // Hapus Nasabah
    function hapusNasabah(id, nama) {
        Swal.fire({
            title: 'Hapus Nasabah?',
            html: `Yakin ingin menghapus <strong>${nama}</strong>?<br>Semua data transaksi akan ikut terhapus!`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = '?hapus=' + id;
            }
        });
    }

    // Reset Password
    function resetPassword(id, nama) {
        Swal.fire({
            title: 'Reset Password?',
            html: `Reset password untuk <strong>${nama}</strong>?<br>Password akan menjadi <strong>nasabah123</strong>`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Ya, Reset!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = '?reset=' + id;
            }
        });
    }

    // Tutup modal jika klik di luar
    window.onclick = function(event) {
        if (event.target.classList.contains('modal')) {
            event.target.style.display = 'none';
        }
    }
    </script>

    <style>
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.5);
    }

    .modal-content {
        background-color: #fefefe;
        margin: 50px auto;
        padding: 20px;
        border-radius: 10px;
        width: 90%;
        max-width: 600px;
        max-height: 80vh;
        overflow-y: auto;
    }

    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid #e2e8f0;
        padding-bottom: 15px;
        margin-bottom: 20px;
    }

    .modal-header h2 {
        margin: 0;
        font-size: 1.5em;
    }

    .close {
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
    }

    .close:hover {
        color: #f56565;
    }

    .modal-footer {
        border-top: 1px solid #e2e8f0;
        padding-top: 20px;
        margin-top: 20px;
        text-align: right;
    }

    .filter-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        align-items: end;
    }

    .action-buttons {
        display: flex;
        gap: 5px;
        flex-wrap: wrap;
    }

    .info-saldo {
        background: #f7fafc;
        padding: 15px;
        border-radius: 5px;
        margin-bottom: 20px;
    }

    .info-saldo h3 {
        margin-bottom: 5px;
        color: #2d3748;
    }

    .text-danger {
        color: #f56565;
    }

    .text-info {
        color: #4299e1;
        font-style: italic;
    }
    </style>
</body>
</html>
<?php
include '../config/database.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

// Proses tambah petugas
if(isset($_POST['tambah_petugas'])) {
    $nama_lengkap = mysqli_real_escape_string($conn, $_POST['nama_lengkap']);
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $no_telepon = mysqli_real_escape_string($conn, $_POST['no_telepon']);
    $password = password_hash('petugas123', PASSWORD_DEFAULT); // Default password
    
    $query = "INSERT INTO users (nama_lengkap, username, password, email, no_telepon, role) 
              VALUES ('$nama_lengkap', '$username', '$password', '$email', '$no_telepon', 'petugas')";
    
    if(mysqli_query($conn, $query)) {
        $success = "Petugas berhasil ditambahkan! Password default: petugas123";
    } else {
        $error = "Gagal menambahkan petugas: " . mysqli_error($conn);
    }
}

// Proses hapus petugas
if(isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    mysqli_query($conn, "DELETE FROM users WHERE id = $id AND role = 'petugas'");
    header("Location: kelola_petugas.php");
    exit();
}

// Ambil data petugas
$petugas = mysqli_query($conn, "SELECT * FROM users WHERE role = 'petugas' ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Petugas - Tabungan Siswa</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
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
                <a href="kelola_petugas.php" class="active">👥 Kelola Petugas</a>
                <a href="kelola_nasabah.php">👤 Kelola Nasabah</a>
                <a href="transaksi.php">💰 Semua Transaksi</a>
                <a href="laporan.php">📈 Laporan</a>
                <a href="pengaturan.php">⚙️ Pengaturan</a>
                <a href="../logout.php">🚪 Logout</a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <h1>Kelola Petugas</h1>
            </div>

            <?php if(isset($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if(isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <!-- Form Tambah Petugas -->
            <div class="card">
                <div class="card-header">
                    <h2>Tambah Petugas Baru</h2>
                </div>
                <div class="card-body">
                    <form method="POST" class="form-horizontal">
                        <div class="form-group">
                            <label>Nama Lengkap</label>
                            <input type="text" name="nama_lengkap" required>
                        </div>
                        <div class="form-group">
                            <label>Username</label>
                            <input type="text" name="username" required>
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" required>
                        </div>
                        <div class="form-group">
                            <label>No. Telepon</label>
                            <input type="text" name="no_telepon">
                        </div>
                        <button type="submit" name="tambah_petugas" class="btn btn-primary">Tambah Petugas</button>
                    </form>
                </div>
            </div>

            <!-- Daftar Petugas -->
            <div class="card">
                <div class="card-header">
                    <h2>Daftar Petugas</h2>
                </div>
                <div class="card-body">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama Lengkap</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>No. Telepon</th>
                                <th>Tanggal Daftar</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = 1;
                            while($row = mysqli_fetch_assoc($petugas)): 
                            ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td><?php echo $row['nama_lengkap']; ?></td>
                                <td><?php echo $row['username']; ?></td>
                                <td><?php echo $row['email']; ?></td>
                                <td><?php echo $row['no_telepon'] ?: '-'; ?></td>
                                <td><?php echo date('d/m/Y', strtotime($row['created_at'])); ?></td>
                                <td>
                                    <a href="edit_petugas.php?id=<?php echo $row['id']; ?>" class="btn-small btn-warning">Edit</a>
                                    <a href="?hapus=<?php echo $row['id']; ?>" class="btn-small btn-danger" onclick="return confirm('Yakin ingin menghapus?')">Hapus</a>
                                    <a href="reset_password.php?id=<?php echo $row['id']; ?>" class="btn-small btn-info">Reset Password</a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
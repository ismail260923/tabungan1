<?php
// nasabah/setor_tunai.php
include '../config/database.php';

// Cek login dan role nasabah
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'nasabah') {
    header("Location: " . BASE_URL . "login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Ambil data nasabah
$query_user = mysqli_query($conn, "SELECT * FROM users WHERE id = $user_id");
$user = mysqli_fetch_assoc($query_user);

// Ambil saldo
$query_saldo = mysqli_query($conn, "SELECT total_saldo FROM saldo WHERE user_id = $user_id");
$saldo = mysqli_fetch_assoc($query_saldo);
$total_saldo = $saldo['total_saldo'] ?? 0;

// Proses setor
if(isset($_POST['simpan_setor'])) {
    $jumlah = str_replace('.', '', $_POST['jumlah']);
    $keterangan = mysqli_real_escape_string($conn, $_POST['keterangan']);
    
    // Validasi
    if($jumlah < 5000) {
        $error_message = "Minimal setoran adalah Rp 5.000!";
    } else {
        // Mulai transaksi
        mysqli_begin_transaction($conn);
        
        try {
            // Simpan ke tabungan
            $query_tabungan = "INSERT INTO tabungan (user_id, jenis_transaksi, jumlah, keterangan) 
                               VALUES ($user_id, 'setor', $jumlah, '$keterangan')";
            if(!mysqli_query($conn, $query_tabungan)) {
                throw new Exception("Gagal menyimpan transaksi");
            }
            
            // Update saldo
            $query_saldo = "UPDATE saldo SET total_saldo = total_saldo + $jumlah WHERE user_id = $user_id";
            if(!mysqli_query($conn, $query_saldo)) {
                throw new Exception("Gagal mengupdate saldo");
            }
            
            mysqli_commit($conn);
            $success_message = "Setoran berhasil! Rp " . number_format($jumlah, 0, ',', '.') . " telah ditambahkan ke saldo Anda.";
            
            // Update saldo terbaru
            $total_saldo += $jumlah;
            
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $error_message = "Transaksi gagal: " . $e->getMessage();
        }
    }
}

// Ambil riwayat setor hari ini
$hari_ini = date('Y-m-d');
$riwayat_today = mysqli_query($conn, "
    SELECT * FROM tabungan 
    WHERE user_id = $user_id 
    AND jenis_transaksi = 'setor' 
    AND DATE(tanggal) = '$hari_ini'
    ORDER BY tanggal DESC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setor Tunai - Tabungan Siswa</title>
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
            max-width: 1200px;
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
            color: #48bb78;
        }

        .breadcrumb {
            color: #718096;
            font-size: 0.95em;
        }

        .saldo-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 20px;
            padding: 25px;
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
        }

        .saldo-info h2 {
            font-size: 2.5em;
            margin-top: 5px;
        }

        .card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }

        .card-header h2 {
            color: #2d3748;
            font-size: 1.5em;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-header h2 i {
            color: #48bb78;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #4a5568;
            font-weight: 600;
        }

        .form-group label i {
            color: #48bb78;
            margin-right: 5px;
        }

        .input-group {
            display: flex;
            align-items: center;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            overflow: hidden;
            transition: all 0.3s;
        }

        .input-group:focus-within {
            border-color: #48bb78;
            box-shadow: 0 0 0 3px rgba(72, 187, 120, 0.1);
        }

        .input-group-prepend {
            background: #f7fafc;
            padding: 12px 20px;
            color: #4a5568;
            font-weight: 600;
            border-right: 2px solid #e2e8f0;
        }

        .input-group input, .input-group textarea {
            flex: 1;
            padding: 12px 20px;
            border: none;
            outline: none;
            font-size: 1em;
        }

        .input-group textarea {
            min-height: 100px;
            resize: vertical;
        }

        .btn {
            padding: 14px 30px;
            border: none;
            border-radius: 10px;
            font-size: 1.1em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }

        .btn-success {
            background: #48bb78;
            color: white;
            width: 100%;
            justify-content: center;
        }

        .btn-success:hover {
            background: #38a169;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(72, 187, 120, 0.4);
        }

        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background: #c6f6d5;
            color: #22543d;
            border: 1px solid #9ae6b4;
        }

        .alert-danger {
            background: #fed7d7;
            color: #742a2a;
            border: 1px solid #fc8181;
        }

        .info-box {
            background: #f7fafc;
            border-radius: 10px;
            padding: 15px;
            margin-top: 15px;
        }

        .info-box i {
            color: #48bb78;
            margin-right: 5px;
        }

        .info-box small {
            color: #718096;
        }

        .riwayat-list {
            list-style: none;
        }

        .riwayat-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #e2e8f0;
        }

        .riwayat-item:last-child {
            border-bottom: none;
        }

        .riwayat-tanggal {
            color: #718096;
            font-size: 0.9em;
        }

        .riwayat-jumlah {
            font-weight: 600;
            color: #48bb78;
        }

        .riwayat-keterangan {
            color: #a0aec0;
            font-size: 0.9em;
        }

        .text-muted {
            color: #718096;
            font-size: 0.9em;
            margin-top: 5px;
        }

        @media (max-width: 768px) {
            .sidebar {
                display: none;
            }
            .main-content {
                margin-left: 0;
                padding: 15px;
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
                <a href="setor_tunai.php" class="active"><i class="fas fa-plus-circle"></i> Setor Tunai</a>
                <a href="tarik_tunai.php"><i class="fas fa-minus-circle"></i> Tarik Tunai</a>
                <a href="history_transaksi.php"><i class="fas fa-history"></i> History Transaksi</a>
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
                <!-- Header -->
                <div class="page-header">
                    <div>
                        <h1><i class="fas fa-plus-circle"></i> Setor Tunai</h1>
                        <div class="breadcrumb">Dashboard / Setor Tunai</div>
                    </div>
                    <div><i class="fas fa-calendar-alt"></i> <?php echo date('d F Y'); ?></div>
                </div>

                <!-- Saldo Card -->
                <div class="saldo-card">
                    <div class="saldo-info">
                        <p><i class="fas fa-wallet"></i> Saldo Anda Saat Ini</p>
                        <h2>Rp <?php echo number_format($total_saldo, 0, ',', '.'); ?></h2>
                    </div>
                </div>

                <!-- Alert Messages -->
                <?php if($success_message): ?>
                    <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $success_message; ?></div>
                <?php endif; ?>
                <?php if($error_message): ?>
                    <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?></div>
                <?php endif; ?>

                <!-- Form Setor -->
                <div class="card">
                    <div class="card-header">
                        <h2><i class="fas fa-edit"></i> Form Setor Tunai</h2>
                    </div>
                    <form method="POST" onsubmit="return validateForm()">
                        <div class="form-group">
                            <label><i class="fas fa-money-bill-wave"></i> Jumlah Setoran</label>
                            <div class="input-group">
                                <span class="input-group-prepend">Rp</span>
                                <input type="text" id="jumlah" name="jumlah" class="format-rupiah" placeholder="0" required>
                            </div>
                            <div class="text-muted"><i class="fas fa-info-circle"></i> Minimal setor Rp 5.000</div>
                        </div>

                        <div class="form-group">
                            <label><i class="fas fa-sticky-note"></i> Keterangan (Opsional)</label>
                            <div class="input-group">
                                <textarea name="keterangan" placeholder="Contoh: Tabungan harian, Uang saku, Hadiah, dll."></textarea>
                            </div>
                        </div>

                        <button type="submit" name="simpan_setor" class="btn btn-success">
                            <i class="fas fa-save"></i> Proses Setoran
                        </button>

                        <div class="info-box">
                            <i class="fas fa-lightbulb"></i>
                            <small>Setoran yang sudah diproses tidak dapat dibatalkan. Pastikan jumlah yang dimasukkan sudah benar.</small>
                        </div>
                    </form>
                </div>

                <!-- Riwayat Setor Hari Ini -->
                <div class="card">
                    <div class="card-header">
                        <h2><i class="fas fa-history"></i> Riwayat Setor Hari Ini</h2>
                    </div>
                    <?php if(mysqli_num_rows($riwayat_today) > 0): ?>
                        <ul class="riwayat-list">
                            <?php while($row = mysqli_fetch_assoc($riwayat_today)): ?>
                            <li class="riwayat-item">
                                <div>
                                    <div class="riwayat-tanggal">
                                        <i class="fas fa-clock"></i> <?php echo date('H:i', strtotime($row['tanggal'])); ?>
                                    </div>
                                    <div class="riwayat-keterangan"><?php echo htmlspecialchars($row['keterangan'] ?: '-'); ?></div>
                                </div>
                                <div class="riwayat-jumlah">Rp <?php echo number_format($row['jumlah'], 0, ',', '.'); ?></div>
                            </li>
                            <?php endwhile; ?>
                        </ul>
                    <?php else: ?>
                        <p style="text-align: center; color: #a0aec0; padding: 20px;">
                            <i class="fas fa-inbox fa-2x"></i><br>
                            Belum ada setoran hari ini
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Format Rupiah
    document.querySelector('.format-rupiah').addEventListener('keyup', function(e) {
        let value = this.value.replace(/\D/g, '');
        this.value = value.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    });

    function validateForm() {
        let jumlah = document.getElementById('jumlah').value.replace(/\./g, '');
        if(!jumlah || parseInt(jumlah) < 5000) {
            alert('Jumlah setoran minimal Rp 5.000!');
            return false;
        }
        return confirm('Pastikan jumlah setoran sudah benar. Lanjutkan?');
    }
    </script>
</body>
</html>
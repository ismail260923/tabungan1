<?php
// nasabah/tarik_tunai.php
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

// Ambil pengaturan (jika ada)
$min_tarik = 5000;
$max_tarik = 500000;
if(file_exists('../config/pengaturan.php')) {
    include '../config/pengaturan.php';
    $min_tarik = defined('MIN_TARIK') ? MIN_TARIK : 5000;
    $max_tarik = defined('MAX_TARIK') ? MAX_TARIK : 500000;
}

// Proses tarik
if(isset($_POST['simpan_tarik'])) {
    $jumlah = str_replace('.', '', $_POST['jumlah']);
    $keterangan = mysqli_real_escape_string($conn, $_POST['keterangan']);
    
    // Validasi
    if(!is_numeric($jumlah) || $jumlah <= 0) {
        $error_message = "Jumlah penarikan tidak valid!";
    } elseif($jumlah < $min_tarik) {
        $error_message = "Minimal penarikan adalah Rp " . number_format($min_tarik, 0, ',', '.') . "!";
    } elseif($jumlah > $max_tarik) {
        $error_message = "Maksimal penarikan per transaksi adalah Rp " . number_format($max_tarik, 0, ',', '.') . "!";
    } elseif($jumlah > $total_saldo) {
        $error_message = "Saldo tidak mencukupi! Saldo Anda: Rp " . number_format($total_saldo, 0, ',', '.');
    } else {
        // Mulai transaksi
        mysqli_begin_transaction($conn);
        
        try {
            // Simpan ke tabungan
            $query_tabungan = "INSERT INTO tabungan (user_id, jenis_transaksi, jumlah, keterangan) 
                               VALUES ($user_id, 'tarik', $jumlah, '$keterangan')";
            if(!mysqli_query($conn, $query_tabungan)) {
                throw new Exception("Gagal menyimpan transaksi");
            }
            
            // Update saldo
            $query_saldo = "UPDATE saldo SET total_saldo = total_saldo - $jumlah WHERE user_id = $user_id";
            if(!mysqli_query($conn, $query_saldo)) {
                throw new Exception("Gagal mengupdate saldo");
            }
            
            mysqli_commit($conn);
            $success_message = "Penarikan berhasil! Rp " . number_format($jumlah, 0, ',', '.') . " telah ditarik dari saldo Anda.";
            
            // Update saldo terbaru
            $total_saldo -= $jumlah;
            
            // Reset form
            $_POST['jumlah'] = '';
            $_POST['keterangan'] = '';
            
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $error_message = "Transaksi gagal: " . $e->getMessage();
        }
    }
}

// Ambil riwayat tarik (10 terbaru)
$riwayat = mysqli_query($conn, "
    SELECT * FROM tabungan 
    WHERE user_id = $user_id 
    AND jenis_transaksi = 'tarik' 
    ORDER BY tanggal DESC 
    LIMIT 10
");

// Statistik penarikan
$statistik = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT 
        COUNT(*) as total_penarikan,
        COALESCE(SUM(jumlah), 0) as total_nominal,
        COALESCE(MAX(jumlah), 0) as max_penarikan,
        COALESCE(AVG(jumlah), 0) as rata_rata
    FROM tabungan 
    WHERE user_id = $user_id AND jenis_transaksi = 'tarik'
"));

// Cek apakah bisa tarik (saldo cukup)
$bisa_tarik = $total_saldo >= $min_tarik;
$maksimal_bisa_tarik = min($max_tarik, $total_saldo);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tarik Tunai - Tabungan Siswa</title>
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
            border: 3px solid #ed8936;
        }

        .user-welcome .name {
            font-size: 1.2em;
            font-weight: 600;
            margin-bottom: 5px;
            color: white;
        }

        .user-welcome .role {
            color: #ed8936;
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
            background: #ed8936;
            color: white;
            border-left: 4px solid #ed8936;
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
            color: #ed8936;
        }

        .breadcrumb {
            color: #718096;
            font-size: 0.95em;
        }

        .saldo-card {
            background: linear-gradient(135deg, #ed8936 0%, #dd6b20 100%);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(237, 137, 54, 0.3);
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
            font-size: 2.5em;
            margin-bottom: 5px;
        }

        .saldo-info small {
            opacity: 0.8;
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
            background: #feebc8;
            color: #ed8936;
        }

        .stat-icon.nominal {
            background: #feebc8;
            color: #ed8936;
        }

        .stat-icon.max {
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

        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
            margin-bottom: 30px;
        }

        @media (max-width: 1024px) {
            .grid-2 {
                grid-template-columns: 1fr;
            }
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

        .card-header h2 i {
            color: #ed8936;
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
            color: #ed8936;
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
            border-color: #ed8936;
            box-shadow: 0 0 0 3px rgba(237, 137, 54, 0.1);
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

        .info-box {
            background: #fff5f0;
            border-radius: 10px;
            padding: 15px;
            margin-top: 15px;
            border: 1px solid #fed7d0;
        }

        .info-box i {
            color: #ed8936;
            margin-right: 5px;
        }

        .info-box small {
            color: #c05621;
        }

        .warning-box {
            background: #fffff0;
            border: 1px solid #faf089;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            color: #744210;
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

        .btn-warning {
            background: #ed8936;
            color: white;
            width: 100%;
            justify-content: center;
        }

        .btn-warning:hover:not(:disabled) {
            background: #dd6b20;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(237, 137, 54, 0.4);
        }

        .btn-warning:disabled {
            background: #cbd5e0;
            cursor: not-allowed;
            opacity: 0.6;
        }

        .btn-outline-warning {
            background: transparent;
            border: 2px solid #ed8936;
            color: #ed8936;
        }

        .btn-outline-warning:hover {
            background: #ed8936;
            color: white;
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

        .alert-warning {
            background: #fffff0;
            color: #744210;
            border: 1px solid #faf089;
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
            color: #ed8936;
            font-size: 1.1em;
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

        .text-success {
            color: #48bb78;
        }

        .text-warning {
            color: #ed8936;
        }

        .quick-amounts {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin: 15px 0;
        }

        .quick-amount {
            padding: 8px 15px;
            background: #f7fafc;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 600;
            color: #4a5568;
        }

        .quick-amount:hover {
            background: #ed8936;
            border-color: #ed8936;
            color: white;
        }

        .sisa-saldo {
            font-size: 1.1em;
            padding: 10px;
            background: #f7fafc;
            border-radius: 8px;
            margin-top: 10px;
        }

        @media (max-width: 768px) {
            .sidebar {
                display: none;
            }
            .main-content {
                margin-left: 0;
                padding: 15px;
            }
            .stats-grid {
                grid-template-columns: 1fr;
            }
            .quick-amounts {
                justify-content: center;
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
                <a href="tarik_tunai.php" class="active"><i class="fas fa-minus-circle"></i> Tarik Tunai</a>
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
                        <h1><i class="fas fa-minus-circle"></i> Tarik Tunai</h1>
                        <div class="breadcrumb">
                            <a href="dashboard.php">Dashboard</a> / Tarik Tunai
                        </div>
                    </div>
                    <div>
                        <i class="fas fa-calendar-alt"></i> <?php echo date('d F Y'); ?>
                    </div>
                </div>

                <!-- Saldo Card -->
                <div class="saldo-card">
                    <div class="saldo-info">
                        <p><i class="fas fa-wallet"></i> Saldo Anda Saat Ini</p>
                        <h2>Rp <?php echo number_format($total_saldo, 0, ',', '.'); ?></h2>
                        <small>Terakhir diperbarui: <?php echo date('d/m/Y H:i'); ?></small>
                    </div>
                </div>

                <!-- Stats -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon total">
                            <i class="fas fa-exchange-alt"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo $statistik['total_penarikan']; ?>x</h3>
                            <p>Total Penarikan</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon nominal">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                        <div class="stat-content">
                            <h3>Rp <?php echo number_format($statistik['total_nominal'], 0, ',', '.'); ?></h3>
                            <p>Total Nominal</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon max">
                            <i class="fas fa-arrow-up"></i>
                        </div>
                        <div class="stat-content">
                            <h3>Rp <?php echo number_format($statistik['max_penarikan'], 0, ',', '.'); ?></h3>
                            <p>Penarikan Tertinggi</p>
                        </div>
                    </div>
                </div>

                <!-- Alert Messages -->
                <?php if($success_message): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> 
                        <?php echo $success_message; ?>
                    </div>
                <?php endif; ?>
                
                <?php if($error_message): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> 
                        <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>

                <!-- Peringatan jika saldo tidak cukup -->
                <?php if(!$bisa_tarik): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        Saldo Anda belum mencukupi untuk melakukan penarikan. Minimal penarikan Rp <?php echo number_format($min_tarik, 0, ',', '.'); ?>.
                        <br><a href="setor_tunai.php" class="btn-outline-warning" style="margin-top: 10px; display: inline-block; padding: 5px 15px; border-radius: 5px;">Setor Sekarang</a>
                    </div>
                <?php endif; ?>

                <!-- Grid 2 Kolom -->
                <div class="grid-2">
                    <!-- Form Tarik -->
                    <div class="card">
                        <div class="card-header">
                            <h2><i class="fas fa-edit"></i> Form Tarik Tunai</h2>
                        </div>
                        <form method="POST" onsubmit="return validateForm()">
                            <!-- Info batasan -->
                            <div class="info-box">
                                <i class="fas fa-info-circle"></i>
                                <small>
                                    Minimal penarikan: <strong>Rp <?php echo number_format($min_tarik, 0, ',', '.'); ?></strong><br>
                                    Maksimal penarikan: <strong>Rp <?php echo number_format($max_tarik, 0, ',', '.'); ?></strong><br>
                                    Maksimal bisa tarik: <strong>Rp <?php echo number_format($maksimal_bisa_tarik, 0, ',', '.'); ?></strong>
                                </small>
                            </div>

                            <!-- Quick amount buttons -->
                            <div class="quick-amounts">
                                <span class="quick-amount" onclick="setAmount(50000)">Rp 50.000</span>
                                <span class="quick-amount" onclick="setAmount(100000)">Rp 100.000</span>
                                <span class="quick-amount" onclick="setAmount(200000)">Rp 200.000</span>
                                <span class="quick-amount" onclick="setAmount(500000)">Rp 500.000</span>
                                <span class="quick-amount" onclick="setAmount(<?php echo $maksimal_bisa_tarik; ?>)">Maksimal</span>
                            </div>

                            <div class="form-group">
                                <label><i class="fas fa-money-bill-wave"></i> Jumlah Penarikan</label>
                                <div class="input-group">
                                    <span class="input-group-prepend">Rp</span>
                                    <input type="text" id="jumlah" name="jumlah" class="format-rupiah" 
                                           placeholder="0" value="<?php echo isset($_POST['jumlah']) ? $_POST['jumlah'] : ''; ?>" 
                                           required <?php echo !$bisa_tarik ? 'disabled' : ''; ?>>
                                </div>
                                <div class="sisa-saldo" id="sisaSaldo">
                                    Sisa saldo setelah tarik: <strong>Rp <?php echo number_format($total_saldo, 0, ',', '.'); ?></strong>
                                </div>
                            </div>

                            <div class="form-group">
                                <label><i class="fas fa-sticky-note"></i> Keterangan (Opsional)</label>
                                <div class="input-group">
                                    <textarea name="keterangan" placeholder="Contoh: Untuk keperluan sekolah, Jajan, Beli buku, dll."><?php echo isset($_POST['keterangan']) ? htmlspecialchars($_POST['keterangan']) : ''; ?></textarea>
                                </div>
                            </div>

                            <button type="submit" name="simpan_tarik" class="btn btn-warning" <?php echo !$bisa_tarik ? 'disabled' : ''; ?>>
                                <i class="fas fa-save"></i> Proses Penarikan
                            </button>

                            <div class="warning-box" style="margin-top: 20px;">
                                <i class="fas fa-exclamation-triangle"></i>
                                <strong>Perhatian:</strong> Pastikan jumlah penarikan sudah benar. Transaksi tidak dapat dibatalkan setelah diproses.
                            </div>
                        </form>
                    </div>

                    <!-- Riwayat Penarikan -->
                    <div class="card">
                        <div class="card-header">
                            <h2><i class="fas fa-history"></i> Riwayat Penarikan Terbaru</h2>
                            <a href="history_transaksi.php?jenis=tarik" class="view-all" style="color: #ed8936;">
                                Lihat Semua <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                        <?php if(mysqli_num_rows($riwayat) > 0): ?>
                            <ul class="riwayat-list">
                                <?php while($row = mysqli_fetch_assoc($riwayat)): ?>
                                <li class="riwayat-item">
                                    <div>
                                        <div class="riwayat-tanggal">
                                            <i class="fas fa-clock"></i> <?php echo date('d/m/Y H:i', strtotime($row['tanggal'])); ?>
                                        </div>
                                        <div class="riwayat-keterangan">
                                            <?php echo htmlspecialchars($row['keterangan'] ?: '-'); ?>
                                        </div>
                                    </div>
                                    <div class="riwayat-jumlah">
                                        - Rp <?php echo number_format($row['jumlah'], 0, ',', '.'); ?>
                                    </div>
                                </li>
                                <?php endwhile; ?>
                            </ul>
                        <?php else: ?>
                            <div style="text-align: center; padding: 40px; color: #a0aec0;">
                                <i class="fas fa-inbox fa-3x"></i>
                                <p style="margin-top: 15px;">Belum ada riwayat penarikan</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Informasi dan Tips -->
                <div class="card">
                    <div class="card-header">
                        <h2><i class="fas fa-lightbulb"></i> Tips Menarik Tunai</h2>
                    </div>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                        <div>
                            <i class="fas fa-check-circle" style="color: #48bb78;"></i>
                            <strong>Rencanakan penarikan</strong>
                            <p style="color: #718096; margin-top: 5px;">Tarik uang sesuai kebutuhan, jangan berlebihan agar tabungan tetap bertumbuh.</p>
                        </div>
                        <div>
                            <i class="fas fa-check-circle" style="color: #48bb78;"></i>
                            <strong>Catat pengeluaran</strong>
                            <p style="color: #718096; margin-top: 5px;">Biasakan mencatat setiap penarikan untuk mengontrol keuangan.</p>
                        </div>
                        <div>
                            <i class="fas fa-check-circle" style="color: #48bb78;"></i>
                            <strong>Prioritaskan kebutuhan</strong>
                            <p style="color: #718096; margin-top: 5px;">Gunakan tabungan untuk hal-hal yang benar-benar penting dan bermanfaat.</p>
                        </div>
                        <div>
                            <i class="fas fa-check-circle" style="color: #48bb78;"></i>
                            <strong>Jaga saldo minimum</strong>
                            <p style="color: #718096; margin-top: 5px;">Sisakan saldo minimal Rp 5.000 untuk biaya administrasi jika ada.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Format Rupiah
    const jumlahInput = document.getElementById('jumlah');
    if(jumlahInput) {
        jumlahInput.addEventListener('keyup', function(e) {
            let value = this.value.replace(/\D/g, '');
            this.value = value.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            updateSisaSaldo();
        });
    }

    // Set jumlah dari quick amount
    function setAmount(amount) {
        if(amount > <?php echo $maksimal_bisa_tarik; ?>) {
            alert('Jumlah melebihi batas maksimal penarikan!');
            amount = <?php echo $maksimal_bisa_tarik; ?>;
        }
        document.getElementById('jumlah').value = amount.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        updateSisaSaldo();
    }

    // Update sisa saldo
    function updateSisaSaldo() {
        let jumlah = document.getElementById('jumlah').value.replace(/\./g, '');
        let saldo = <?php echo $total_saldo; ?>;
        let sisa = saldo - (parseInt(jumlah) || 0);
        
        if(sisa < 0) {
            document.getElementById('sisaSaldo').innerHTML = 
                '<span style="color: #f56565;">Sisa saldo tidak mencukupi!</span>';
        } else {
            document.getElementById('sisaSaldo').innerHTML = 
                'Sisa saldo setelah tarik: <strong>Rp ' + sisa.toLocaleString('id-ID') + '</strong>';
        }
    }

    // Validasi form
    function validateForm() {
        let jumlah = document.getElementById('jumlah').value.replace(/\./g, '');
        let saldo = <?php echo $total_saldo; ?>;
        let minTarik = <?php echo $min_tarik; ?>;
        let maxTarik = <?php echo $max_tarik; ?>;
        
        if(!jumlah || parseInt(jumlah) < minTarik) {
            alert('Jumlah penarikan minimal Rp ' + minTarik.toLocaleString('id-ID') + '!');
            return false;
        }
        
        if(parseInt(jumlah) > maxTarik) {
            alert('Jumlah penarikan maksimal Rp ' + maxTarik.toLocaleString('id-ID') + '!');
            return false;
        }
        
        if(parseInt(jumlah) > saldo) {
            alert('Saldo tidak mencukupi!');
            return false;
        }
        
        return confirm('Pastikan jumlah penarikan sudah benar. Lanjutkan transaksi?');
    }

    // Trigger update sisa saldo on page load
    window.onload = function() {
        updateSisaSaldo();
    }
    </script>
</body>
</html>
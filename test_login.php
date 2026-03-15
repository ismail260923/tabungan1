<?php
// test_login.php - File untuk testing langsung
include 'config/database.php';

// Data test
$test_username = 'ahmad.fauzi'; // Ganti dengan username nasabah Anda
$test_password = 'nasabah123'; // Ganti dengan password nasabah

echo "<h2>Test Login</h2>";

// Cek koneksi database
echo "<h3>1. Cek Koneksi Database:</h3>";
if($conn) {
    echo "✓ Koneksi database berhasil<br>";
} else {
    echo "✗ Koneksi database gagal: " . mysqli_connect_error() . "<br>";
}

// Cek tabel users
echo "<h3>2. Cek Tabel Users:</h3>";
$result = mysqli_query($conn, "SHOW TABLES LIKE 'users'");
if(mysqli_num_rows($result) > 0) {
    echo "✓ Tabel users ada<br>";
} else {
    echo "✗ Tabel users tidak ditemukan!<br>";
}

// Cek data user
echo "<h3>3. Cek Data User:</h3>";
$query = "SELECT * FROM users WHERE username = '$test_username'";
$result = mysqli_query($conn, $query);

if(mysqli_num_rows($result) > 0) {
    $user = mysqli_fetch_assoc($result);
    echo "✓ User ditemukan:<br>";
    echo "ID: " . $user['id'] . "<br>";
    echo "Username: " . $user['username'] . "<br>";
    echo "Nama: " . $user['nama_lengkap'] . "<br>";
    echo "Role: " . $user['role'] . "<br>";
    echo "Password Hash: " . $user['password'] . "<br>";
    
    // Test verifikasi password
    echo "<h3>4. Test Verifikasi Password:</h3>";
    if(password_verify($test_password, $user['password'])) {
        echo "✓ Password valid!<br>";
        
        // Test session
        echo "<h3>5. Test Session:</h3>";
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        
        echo "Session user_id: " . $_SESSION['user_id'] . "<br>";
        echo "Session username: " . $_SESSION['username'] . "<br>";
        echo "Session role: " . $_SESSION['role'] . "<br>";
        
        // Test redirect
        echo "<h3>6. Test Redirect:</h3>";
        switch($user['role']) {
            case 'nasabah':
                echo "Akan redirect ke: nasabah/dashboard.php<br>";
                echo '<a href="nasabah/dashboard.php">Klik ke Dashboard Nasabah</a>';
                break;
            case 'petugas':
                echo "Akan redirect ke: petugas/dashboard.php<br>";
                echo '<a href="petugas/dashboard.php">Klik ke Dashboard Petugas</a>';
                break;
            case 'admin':
                echo "Akan redirect ke: admin/dashboard.php<br>";
                echo '<a href="admin/dashboard.php">Klik ke Dashboard Admin</a>';
                break;
        }
    } else {
        echo "✗ Password tidak valid!<br>";
        echo "Password yang dimasukkan: " . $test_password . "<br>";
        echo "Hash yang tersimpan: " . $user['password'] . "<br>";
        
        // Generate hash baru untuk test
        $new_hash = password_hash($test_password, PASSWORD_DEFAULT);
        echo "Hash yang seharusnya: " . $new_hash . "<br>";
        
        echo "<h4>Untuk memperbaiki, jalankan query:</h4>";
        echo "UPDATE users SET password = '$new_hash' WHERE username = '$test_username';";
    }
} else {
    echo "✗ User dengan username '$test_username' tidak ditemukan!<br>";
    
    // Tampilkan semua user
    echo "<h4>Daftar User yang ada:</h4>";
    $all_users = mysqli_query($conn, "SELECT id, username, role FROM users");
    while($row = mysqli_fetch_assoc($all_users)) {
        echo "- " . $row['username'] . " (" . $row['role'] . ")<br>";
    }
}
?>
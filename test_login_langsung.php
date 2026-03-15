<?php
// test_login_langsung.php
include 'config/database.php';

$test_username = 'ahmad.fauzi'; // Ganti dengan username yang ingin dites
$test_password = 'nasabah123';

echo "<h2>TEST LOGIN LANGSUNG</h2>";

// Cari user
$query = "SELECT * FROM users WHERE username = '$test_username'";
$result = mysqli_query($conn, $query);

if(mysqli_num_rows($result) > 0) {
    $user = mysqli_fetch_assoc($result);
    
    echo "<h3>Data User:</h3>";
    echo "ID: " . $user['id'] . "<br>";
    echo "Username: " . $user['username'] . "<br>";
    echo "Nama: " . $user['nama_lengkap'] . "<br>";
    echo "Role: " . $user['role'] . "<br>";
    echo "Password Hash: " . $user['password'] . "<br><br>";
    
    // Test verifikasi password
    echo "<h3>Test Verifikasi:</h3>";
    if(password_verify($test_password, $user['password'])) {
        echo "<span style='color:green; font-weight:bold'>✓ PASSWORD VALID!</span><br>";
        
        // Set session manual
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
        $_SESSION['role'] = $user['role'];
        
        echo "Session telah diset.<br>";
        echo "<a href='nasabah/dashboard.php'>Klik untuk ke Dashboard Nasabah</a><br>";
        echo "<a href='" . $user['role'] . "/dashboard.php'>Klik untuk ke Dashboard sesuai role</a>";
        
    } else {
        echo "<span style='color:red; font-weight:bold'>✗ PASSWORD TIDAK VALID!</span><br>";
        echo "Password yang dicoba: " . $test_password . "<br>";
        
        // Generate hash baru untuk referensi
        $new_hash = password_hash($test_password, PASSWORD_DEFAULT);
        echo "Hash yang seharusnya: " . $new_hash . "<br><br>";
        
        echo "<a href='reset_password_nasabah.php'>Reset Semua Password Nasabah</a>";
    }
} else {
    echo "User dengan username '$test_username' tidak ditemukan!<br>";
    
    // Tampilkan semua user
    $all_users = mysqli_query($conn, "SELECT username, role FROM users");
    echo "<h3>Daftar User yang tersedia:</h3>";
    echo "<ul>";
    while($row = mysqli_fetch_assoc($all_users)) {
        echo "<li>" . $row['username'] . " (" . $row['role'] . ")</li>";
    }
    echo "</ul>";
}
?>
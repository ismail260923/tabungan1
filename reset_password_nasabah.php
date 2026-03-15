<?php
// reset_password_nasabah.php
include 'config/database.php';

echo "<h2>RESET PASSWORD NASABAH</h2>";

// Password baru yang akan digunakan
$new_password = 'nasabah123';
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

echo "Password baru: <strong>" . $new_password . "</strong><br>";
echo "Hash baru: <code>" . $hashed_password . "</code><br><br>";

// Reset untuk semua nasabah
$query = "UPDATE users SET password = '$hashed_password' WHERE role = 'nasabah'";
if(mysqli_query($conn, $query)) {
    $affected = mysqli_affected_rows($conn);
    echo "<span style='color:green; font-weight:bold'>✓ Berhasil mereset password untuk $affected nasabah</span><br>";
    
    // Tampilkan daftar nasabah yang sudah direset
    $result = mysqli_query($conn, "SELECT id, username, nama_lengkap FROM users WHERE role = 'nasabah'");
    echo "<h3>Daftar Nasabah:</h3>";
    echo "<ul>";
    while($row = mysqli_fetch_assoc($result)) {
        echo "<li>" . $row['username'] . " - " . $row['nama_lengkap'] . "</li>";
    }
    echo "</ul>";
    
    echo "<p>Silakan coba login dengan:<br>";
    echo "Username: [username nasabah]<br>";
    echo "Password: nasabah123</p>";
} else {
    echo "<span style='color:red'>Gagal reset password: " . mysqli_error($conn) . "</span>";
}
?>

<p>
    <a href="login.php">Kembali ke Login</a> | 
    <a href="cek_password.php">Cek Password</a>
</p>
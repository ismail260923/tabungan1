<?php
// cek_password.php
include 'config/database.php';

echo "<h2>CEK PASSWORD HASH DATABASE</h2>";

// Ambil semua user nasabah
$query = "SELECT id, username, nama_lengkap, password FROM users WHERE role = 'nasabah'";
$result = mysqli_query($conn, $query);

if(mysqli_num_rows($result) > 0) {
    echo "<table border='1' cellpadding='10'>";
    echo "<tr>
            <th>ID</th>
            <th>Username</th>
            <th>Nama</th>
            <th>Password Hash</th>
            <th>Panjang Hash</th>
            <th>Test dengan 'nasabah123'</th>
            <th>Aksi</th>
          </tr>";
    
    while($row = mysqli_fetch_assoc($result)) {
        $hash = $row['password'];
        $panjang = strlen($hash);
        $test_password = 'nasabah123';
        
        // Test verifikasi
        if(password_verify($test_password, $hash)) {
            $verifikasi = "<span style='color:green; font-weight:bold'>✓ VALID</span>";
        } else {
            $verifikasi = "<span style='color:red; font-weight:bold'>✗ TIDAK VALID</span>";
        }
        
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['username'] . "</td>";
        echo "<td>" . $row['nama_lengkap'] . "</td>";
        echo "<td style='font-family:monospace; font-size:12px;'>" . substr($hash, 0, 50) . "...</td>";
        echo "<td>" . $panjang . " karakter</td>";
        echo "<td>" . $verifikasi . "</td>";
        echo "<td>
                <a href='reset_password.php?id=" . $row['id'] . "' onclick='return confirm(\"Reset password jadi nasabah123?\")'>Reset Password</a>
              </td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "Tidak ada data nasabah!";
}

// Tampilkan informasi tentang password_hash
echo "<h3>Informasi Password Hash:</h3>";
echo "Password 'nasabah123' jika di-hash akan menghasilkan string seperti ini:<br>";
$sample_hash = password_hash('nasabah123', PASSWORD_DEFAULT);
echo "<code style='background:#f0f0f0; padding:10px; display:block; margin:10px 0;'>" . $sample_hash . "</code>";
echo "Panjang hash: " . strlen($sample_hash) . " karakter<br>";
echo "Hash selalu dimulai dengan: <strong>" . substr($sample_hash, 0, 4) . "</strong><br>";
?>
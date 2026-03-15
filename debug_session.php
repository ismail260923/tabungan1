<?php
// debug_session.php
include 'config/database.php';

echo "<h2>Debug Session dan Login</h2>";

// Cek session
echo "<h3>1. Informasi Session:</h3>";
echo "Session ID: " . session_id() . "<br>";
echo "Session Status: " . (isset($_SESSION) ? "Aktif" : "Tidak Aktif") . "<br>";

if(isset($_SESSION)) {
    echo "<pre>";
    print_r($_SESSION);
    echo "</pre>";
} else {
    echo "Session tidak tersedia<br>";
}

// Cek cookie
echo "<h3>2. Informasi Cookie:</h3>";
if(isset($_COOKIE)) {
    echo "<pre>";
    print_r($_COOKIE);
    echo "</pre>";
}

// Form login sederhana
echo "<h3>3. Form Login Test:</h3>";
echo '<form method="POST" action="test_login_proses.php">
    <input type="text" name="username" placeholder="Username" required><br>
    <input type="password" name="password" placeholder="Password" required><br>
    <button type="submit">Login Test</button>
</form>';

// Link ke dashboard
echo "<h3>4. Link Langsung ke Dashboard:</h3>";
echo '<a href="nasabah/dashboard.php">Dashboard Nasabah</a><br>';
echo '<a href="petugas/dashboard.php">Dashboard Petugas</a><br>';
echo '<a href="admin/dashboard.php">Dashboard Admin</a><br>';
?>
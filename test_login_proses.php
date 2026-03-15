<?php
// test_login_proses.php
include 'config/database.php';

echo "<h2>Proses Login Test</h2>";

if($_POST) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    echo "Username: " . $username . "<br>";
    echo "Password: " . $password . "<br>";
    
    // Query dengan prepared statement
    $query = "SELECT * FROM users WHERE username = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    echo "Jumlah hasil query: " . mysqli_num_rows($result) . "<br>";
    
    if(mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        echo "<pre>Data user: ";
        print_r($user);
        echo "</pre>";
        
        if(password_verify($password, $user['password'])) {
            echo "Password VALID!<br>";
            
            // Set session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
            $_SESSION['role'] = $user['role'];
            
            echo "Session telah diset:<br>";
            echo "user_id: " . $_SESSION['user_id'] . "<br>";
            echo "role: " . $_SESSION['role'] . "<br>";
            
            // Redirect link
            echo "<h3>Redirect ke:</h3>";
            $redirect = $user['role'] . "/dashboard.php";
            echo '<a href="' . $redirect . '">Klik ke ' . $redirect . '</a><br>';
            
            // Redirect otomatis setelah 3 detik
            echo '<meta http-equiv="refresh" content="3;url=' . $redirect . '">';
            echo "Anda akan dialihkan dalam 3 detik...";
            
        } else {
            echo "Password TIDAK valid!<br>";
        }
    } else {
        echo "User tidak ditemukan!<br>";
    }
} else {
    echo "Tidak ada data POST";
}
?>
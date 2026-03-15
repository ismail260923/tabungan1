<?php
// login_simple.php
include 'config/database.php';

if($_POST) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    $query = "SELECT * FROM users WHERE username = '$username'";
    $result = mysqli_query($conn, $query);
    $user = mysqli_fetch_assoc($result);
    
    if($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        
        // Redirect manual
        if($user['role'] == 'nasabah') {
            header("Location: nasabah/dashboard.php");
            exit();
        }
    } else {
        echo "Login gagal";
    }
}
?>
<form method="POST">
    <input type="text" name="username" placeholder="Username">
    <input type="password" name="password" placeholder="Password">
    <button type="submit">Login</button>
</form>
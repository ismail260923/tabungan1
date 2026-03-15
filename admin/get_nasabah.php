<?php
// admin/get_nasabah.php
include '../config/database.php';

if(isset($_GET['id'])) {
    $id = $_GET['id'];
    $query = mysqli_query($conn, "SELECT * FROM users WHERE id = $id AND role = 'nasabah'");
    
    if(mysqli_num_rows($query) > 0) {
        $data = mysqli_fetch_assoc($query);
        echo json_encode($data);
    } else {
        echo json_encode(['error' => 'Data tidak ditemukan']);
    }
}
?>
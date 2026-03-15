<?php
// config/database.php
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'tabungan_siswa';

// Buat koneksi
$conn = mysqli_connect($host, $username, $password, $database);

// Cek koneksi
if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

// Set charset
mysqli_set_charset($conn, "utf8");

// Tentukan BASE URL
$base_url = 'http://localhost/tabungan1/'; // Ganti dengan URL Anda

// Buat session jika belum dimulai
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Untuk debugging (hapus jika sudah production)
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
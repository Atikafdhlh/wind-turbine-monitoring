<?php
session_start();

// Membuat koneksi ke database Hostinger
$conn = mysqli_connect("localhost", "u825743231_kinerjaturbina", "Tabas1atika", "u825743231_kinerjaturbina");

// Cek koneksi
if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}
?>
<head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<?php
// Pastikan koneksi ke database sudah dilakukan
require 'function.php';

// Periksa apakah parameter 'card' ada dan tentukan data yang akan diambil
if (isset($_GET['card'])) {
    $card = $_GET['card'];

    // Query untuk mengambil data berdasarkan card yang dipilih
    if ($card == 'daya') {
        $query = "SELECT Waktu, Daya FROM kinerja ORDER BY Waktu DESC";
        $title = "Detail Data Daya";
        $col1 = "Waktu";
        $col2 = "Daya";
    } elseif ($card == 'v1') {
        $query = "SELECT Waktu, V1 FROM kinerja ORDER BY Waktu DESC";
        $title = "Detail Data V1";
        $col1 = "Waktu";
        $col2 = "V1";
    } elseif ($card == 'v2') {
        $query = "SELECT Waktu, V2 FROM kinerja ORDER BY Waktu DESC";
        $title = "Detail Data V2";
        $col1 = "Waktu";
        $col2 = "V2";
    } elseif ($card == 'arus_dc') {
        $query = "SELECT Waktu, `Arus DC` FROM kinerja ORDER BY Waktu DESC";
        $title = "Detail Data Arus DC";
        $col1 = "Waktu";
        $col2 = "Arus DC";
    //} elseif ($card == 'arus_ac') {
    //    $query = "SELECT Waktu, `Arus AC` FROM kinerja ORDER BY Waktu DESC";
    //    $title = "Detail Data Arus AC";
    //    $col1 = "Waktu";
    //    $col2 = "Arus AC";
    //} elseif ($card == 'kecepatan_angin') {
    //    $query = "SELECT Waktu, `Kecepatan Angin` FROM kinerja ORDER BY Waktu DESC";
    //    $title = "Detail Data Kecepatan Angin";
    //    $col1 = "Waktu";
    //    $col2 = "Kecepatan Angin";
    } else {
        // Jika parameter card tidak valid
        echo "<p>Halaman detail tidak ditemukan.</p>";
        exit;
    }

    // Eksekusi query untuk mengambil data
    $result = mysqli_query($conn, $query);

    // Periksa apakah query menghasilkan data
    if (mysqli_num_rows($result) > 0) {
        echo "<h1 class='text-center mb-4'>$title</h1>";
        echo "<table class='table table-striped table-bordered table-hover'>";
        echo "<thead class='thead-dark'><tr><th>$col1</th><th>$col2</th></tr></thead>";
        echo "<tbody>";
        
        // Loop untuk menampilkan setiap baris data
        while ($row = mysqli_fetch_assoc($result)) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['Waktu']) . "</td>"; // Menampilkan waktu
            echo "<td>" . htmlspecialchars($row[$col2]) . "</td>"; // Menampilkan nilai berdasarkan card
            echo "</tr>";
        }
        
        echo "</tbody></table>";
    } else {
        echo "<p>Data tidak ditemukan untuk $title.</p>";
    }
} else {
    echo "<p>Halaman detail tidak ditemukan.</p>";
}
?>

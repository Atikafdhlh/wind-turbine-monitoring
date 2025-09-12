<head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<?php
require 'function.php'; // Pastikan koneksi ke database sudah benar

if (isset($_GET['search'])) {
    $search_query = $_GET['search'];

    // Query pencarian dengan LIKE untuk mencari data berdasarkan query
    $sql = "SELECT * FROM kinerja WHERE waktu LIKE '%$search_query%' 
            OR daya LIKE '%$search_query%' 
            OR v1 LIKE '%$search_query%' 
            OR v2 LIKE '%$search_query%' 
            OR `Arus DC` LIKE '%$search_query%'
            OR `Arus AC` LIKE '%$search_query%' 
            OR `Kecepatan Angin` LIKE '%$search_query%'";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) > 0) {
        // Menampilkan hasil pencarian dalam tabel
        echo "<h3 class='text-center'>Hasil pencarian untuk: " . htmlspecialchars($search_query) . "</h3>";
        echo "<table class='table table-striped table-bordered'>";
        echo "<thead>
                <tr>
                    <th>Waktu</th>
                    <th>Daya</th>
                    <th>V1</th>
                    <th>V2</th>
                    <th>Arus DC</th>
                    <th>Arus AC</th>
                    <th>Kecepatan Angin</th>
                </tr>
              </thead>";
        echo "<tbody>";
        
        // Menampilkan hasil pencarian dalam tabel
        while ($row = mysqli_fetch_assoc($result)) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['Waktu']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Daya']) . "</td>";
            echo "<td>" . htmlspecialchars($row['V1']) . "</td>";
            echo "<td>" . htmlspecialchars($row['V2']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Arus DC']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Arus AC']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Kecepatan Angin']) . "</td>";
            echo "</tr>";
        }

        echo "</tbody>";
        echo "</table>";
    } else {
        echo "<p>No results found for: " . htmlspecialchars($search_query) . "</p>";
    }
}
?>
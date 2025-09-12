<?php
date_default_timezone_set('Asia/Jakarta');
require 'function.php';

// Query untuk mendapatkan semua data dari tabel kinerja dengan waktu WIB
$queryData = "SELECT CONVERT_TZ(Waktu, '+00:00', '+07:00') AS Waktu, Daya, V1, V2,`Arus DC`, `Arus AC`, `Kecepatan Angin` FROM kinerja ORDER BY Waktu ASC";
$resultData = mysqli_query($conn, $queryData) or die("Error: " . mysqli_error($conn));

// Inisialisasi array untuk menyimpan data mentah
$data = [];
while ($row = mysqli_fetch_assoc($resultData)) {
    $data[] = $row;
}

// Fungsi untuk menghitung rata-rata per jam (untuk grafik harian) dengan jendela 24 jam dinamis
function getHourlyAverages($data) {
    $hourlyData = [];
    $currentHour = null;
    $dayaSum = 0;
    $kecepatanSum = 0;
    $count = 0;

    foreach ($data as $row) {
        $hour = date('Y-m-d H:00:00', strtotime($row['Waktu']));
        if ($currentHour !== $hour) {
            if ($currentHour !== null) {
                $hourlyData[$currentHour] = [
                    'Daya' => $count > 0 ? round($dayaSum / $count, 1) : 0,
                    'Kecepatan Angin' => $count > 0 ? round($kecepatanSum / $count, 1) : 0
                ];
            }
            $currentHour = $hour;
            $dayaSum = 0;
            $kecepatanSum = 0;
            $count = 0;
        }
        $dayaSum += (float)$row['Daya'];
        $kecepatanSum += (float)$row['Kecepatan Angin'];
        $count++;
    }
    if ($currentHour !== null) {
        $hourlyData[$currentHour] = [
            'Daya' => $count > 0 ? round($dayaSum / $count, 1) : 0,
            'Kecepatan Angin' => $count > 0 ? round($kecepatanSum / $count, 1) : 0
        ];
    }

    // Ambil 24 jam terakhir berdasarkan waktu terakhir
    if (!empty($data)) {
        $latestTime = end($data)['Waktu'];
        $startHour = date('Y-m-d H:00:00', strtotime('-23 hours', strtotime($latestTime)));
        $dailyData = [];
        foreach ($hourlyData as $hour => $values) {
            if (strtotime($hour) >= strtotime($startHour) && strtotime($hour) <= strtotime($latestTime)) {
                $dailyData[] = [
                    'Daya' => $values['Daya'],
                    'Kecepatan Angin' => $values['Kecepatan Angin'],
                    'Label' => date('H:00', strtotime($hour))
                ];
            }
        }
        return $dailyData;
    }
    return [];
}

// Fungsi untuk menghitung rata-rata per hari (untuk grafik mingguan) dengan jendela 7 hari dinamis
function getDailyAverages($data) {
    $dailyData = [];
    $currentDay = null;
    $dayaSum = 0;
    $kecepatanSum = 0;
    $count = 0;

    foreach ($data as $row) {
        $day = date('Y-m-d', strtotime($row['Waktu']));
        if ($currentDay !== $day) {
            if ($currentDay !== null) {
                $dailyData[$currentDay] = [
                    'Daya' => $count > 0 ? round($dayaSum / $count, 1) : 0,
                    'Kecepatan Angin' => $count > 0 ? round($kecepatanSum / $count, 1) : 0
                ];
            }
            $currentDay = $day;
            $dayaSum = 0;
            $kecepatanSum = 0;
            $count = 0;
        }
        $dayaSum += (float)$row['Daya'];
        $kecepatanSum += (float)$row['Kecepatan Angin'];
        $count++;
    }
    if ($currentDay !== null) {
        $dailyData[$currentDay] = [
            'Daya' => $count > 0 ? round($dayaSum / $count, 1) : 0,
            'Kecepatan Angin' => $count > 0 ? round($kecepatanSum / $count, 1) : 0
        ];
    }

    // Ambil 7 hari terakhir berdasarkan waktu terakhir
    if (!empty($data)) {
        $latestTime = end($data)['Waktu'];
        $latestDay = date('Y-m-d', strtotime($latestTime));
        $startDay = date('Y-m-d', strtotime('-6 days', strtotime($latestTime)));
        $weeklyData = [];
        foreach ($dailyData as $day => $values) {
            if (strtotime($day) >= strtotime($startDay) && strtotime($day) <= strtotime($latestDay)) {
                $weeklyData[] = [
                    'Daya' => $values['Daya'],
                    'Kecepatan Angin' => $values['Kecepatan Angin'],
                    'Label' => date('Y-m-d', strtotime($day))
                ];
            }
        }
        return $weeklyData;
    }
    return [];
}

// Hitung data untuk grafik
$dailyAverages = getHourlyAverages($data); // 24 titik data
$weeklyAverages = getDailyAverages($data); // 7 titik data

// Konversi ke JSON untuk JavaScript
$dailyDaya = json_encode(array_column($dailyAverages, 'Daya'));
$dailyLabels = json_encode(array_column($dailyAverages, 'Label'));
$dailyKecepatan = json_encode(array_column($dailyAverages, 'Kecepatan Angin'));
$weeklyDaya = json_encode(array_column($weeklyAverages, 'Daya'));
$weeklyLabels = json_encode(array_column($weeklyAverages, 'Label'));
$weeklyKecepatan = json_encode(array_column($weeklyAverages, 'Kecepatan Angin'));

// Ambil data terakhir untuk kartu dashboard dari array mentah
$last_index = count($data) - 1;
$daya_display = (isset($data[$last_index]['Daya']) && $data[$last_index]['Daya'] !== null) ? $data[$last_index]['Daya'] : "Data tidak tersedia";
$v1_display = (isset($data[$last_index]['V1']) && $data[$last_index]['V1'] !== null) ? $data[$last_index]['V1'] : "Data tidak tersedia";
$v2_display = (isset($data[$last_index]['V2']) && $data[$last_index]['V2'] !== null) ? $data[$last_index]['V2'] : "Data tidak tersedia";
$arus_dc_display = (isset($data[$last_index]['Arus DC']) && $data[$last_index]['Arus DC'] !== null) ? $data[$last_index]['Arus DC'] : "Data tidak tersedia";
$arus_ac_display = (isset($data[$last_index]['Arus AC']) && $data[$last_index]['Arus AC'] !== null) ? $data[$last_index]['Arus AC'] : "Data tidak tersedia";
$kecepatan_angin_display = (isset($data[$last_index]['Kecepatan Angin']) && $data[$last_index]['Kecepatan Angin'] !== null) ? $data[$last_index]['Kecepatan Angin'] : "Data tidak tersedia";

// Tentukan halaman yang akan ditampilkan berdasarkan parameter GET
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';

// Query untuk tabel (untuk JavaScript export)
$tableDataQuery = $page == 'dashboard' ?
    "SELECT CONVERT_TZ(Waktu, '+00:00', '+07:00') AS Waktu, Daya, V1, V2, `Arus DC`, `Arus AC`, `Kecepatan Angin` FROM kinerja ORDER BY Waktu DESC" :
    "SELECT CONVERT_TZ(Waktu, '+00:00', '+07:00') AS Waktu, Daya, V1, V2, `Arus DC` FROM kinerja ORDER BY Waktu DESC";
$tableDataResult = mysqli_query($conn, $tableDataQuery);
$tableData = [];
while ($row = mysqli_fetch_assoc($tableDataResult)) {
    $tableData[] = $row;
}
$tableDataJson = json_encode($tableData);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <title><?php echo ($page == 'ta-bas1-vol1') ? 'TA-BAS1 VOL1' : 'Dashboard - Kinerja Turbin Angin'; ?></title>
    <link href="css/styles.css" rel="stylesheet" />
    <link href="https://cdn.datatables.net/1.10.20/css/dataTables.bootstrap4.min.css" rel="stylesheet" crossorigin="anonymous" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/js/all.min.js" crossorigin="anonymous"></script>
</head>
<body class="sb-nav-fixed">
    <nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark">
        <a class="navbar-brand" href="index.php">TA-BAS1</a>
        <button class="btn btn-link btn-sm order-1 order-lg-0" id="sidebarToggle" href="#"><i class="fas fa-bars"></i></button>
        <ul class="navbar-nav ml-auto">
            <li class="nav-item">
                <form class="form-inline my-2 my-lg-0" method="GET" action="search.php">
                    <div class="input-group">
                        <input class="form-control mr-sm-2" type="search" placeholder="Search for..." aria-label="Search" name="search" id="searchInput">
                        <div class="input-group-append">
                            <button class="btn btn-outline-light my-2 my-sm-0" type="submit">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </li>
        </ul>
    </nav>
    <div id="layoutSidenav">
        <div id="layoutSidenav_nav">
            <nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion">
                <div class="sb-sidenav-menu">
                    <div class="nav">
                        <div class="sb-sidenav-menu-heading">Core</div>
                        <a class="nav-link" href="index.php?page=dashboard">
                            <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>
                            MAIN DASHBOARD
                        </a>
                        <a class="nav-link" href="index.php?page=ta-bas1-vol1">
                            <div class="sb-nav-link-icon"><i class="fas fa-bolt"></i></div>
                            TA-BAS1 VOL1
                        </a>
                    </div>
                </div>
                <div class="sb-sidenav-footer"></div>
            </nav>
        </div>
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid text-center">
                    <?php if ($page == 'dashboard'): ?>
                        <h1 class="mt-4" style="margin-bottom: 2rem;">Monitoring Kinerja Turbin Angin</h1>
                        <div class="row justify-content-center mb-4">
                            <div class="col-auto">
                                <button class="btn btn-primary" onclick="window.location.reload();">
                                    <i class="fas fa-sync-alt mr-2"></i>Refresh Data
                                </button>
                                <button class="btn btn-success" onclick="exportToCSV('<?php echo $page; ?>')">
                                    <i class="fas fa-download mr-2"></i>Download Data
                                </button>
                            </div>
                        </div>
                        <div class="row justify-content-center">
                            <div class="col-xl-3 col-md-6">
                                <div class="card bg-primary text-white mb-4">
                                    <div class="card-body d-flex justify-content-center align-items-center">
                                        <?php echo htmlspecialchars($daya_display) . " Watt"; ?>
                                    </div>
                                    <div class="card-footer d-flex align-items-center justify-content-between">
                                        <a class="small text-white stretched-link" href="detail.php?card=daya">Lihat Detail</a>
                                        <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-3 col-md-6">
                                <div class="card bg-warning text-white mb-4">
                                    <div class="card-body d-flex justify-content-center align-items-center">
                                        <?php echo htmlspecialchars($v1_display) . " V"; ?>
                                    </div>
                                    <div class="card-footer d-flex align-items-center justify-content-between">
                                        <a class="small text-white stretched-link" href="detail.php?card=v1">Lihat Detail</a>
                                        <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-3 col-md-6">
                                <div class="card bg-success text-white mb-4">
                                    <div class="card-body d-flex justify-content-center align-items-center">
                                        <?php echo htmlspecialchars($v2_display) . " V"; ?>
                                    </div>
                                    <div class="card-footer d-flex align-items-center justify-content-between">
                                        <a class="small text-white stretched-link" href="detail.php?card=v2">Lihat Detail</a>
                                        <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row justify-content-center">
                            <div class="col-xl-3 col-md-6">
                                <div class="card bg-success text-white mb-4">
                                    <div class="card-body d-flex justify-content-center align-items-center">
                                        <?php echo htmlspecialchars($arus_dc_display) . " A"; ?>
                                    </div>
                                    <div class="card-footer d-flex align-items-center justify-content-between">
                                        <a class="small text-white stretched-link" href="detail.php?card=arus_dc&value=<?php echo urlencode($arus_dc_display); ?>">Lihat Detail</a>
                                        <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-3 col-md-6">
                                <div class="card bg-danger text-white mb-4">
                                    <div class="card-body d-flex justify-content-center align-items-center">
                                        <?php echo htmlspecialchars($arus_ac_display) . " A"; ?>
                                    </div>
                                    <div class="card-footer d-flex align-items-center justify-content-between">
                                        <a class="small text-white stretched-link" href="detail.php?card=arus_ac&value=<?php echo urlencode($arus_ac_display); ?>">Lihat Detail</a>
                                        <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-3 col-md-6">
                                <div class="card bg-danger text-white mb-4">
                                    <div class="card-body d-flex justify-content-center align-items-center">
                                        <?php echo htmlspecialchars($kecepatan_angin_display) . " m/s"; ?>
                                    </div>
                                    <div class="card-footer d-flex align-items-center justify-content-between">
                                        <a class="small text-white stretched-link" href="detail.php?card=kecepatan_angin&value=<?php echo urlencode($kecepatan_angin_display); ?>">Lihat Detail</a>
                                        <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-xl-6">
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <i class="fas fa-chart-area mr-1"></i>
                                        Grafik Kecepatan Angin vs Daya Turbin Angin/Hari
                                    </div>
                                    <div class="card-body"><canvas id="myAreaChart" width="200%" height="300"></lias></canvas></div>
                                </div>
                            </div>
                            <div class="col-xl-6">
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <i class="fas fa-chart-bar mr-1"></i>
                                        Grafik Kecepatan Angin vs Daya Turbin Angin/Minggu
                                    </div>
                                    <div class="card-body"><canvas id="myBarChart" width="200%" height="300"></canvas></div>
                                </div>
                            </div>
                        </div>
                        <div class="card mb-4">
                            <div class="card-header">
                                <i class="fas fa-table mr-1"></i>
                                Data Kinerja Turbin Angin
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                        <thead>
                                            <tr>
                                                <th style="text-align: center;">Waktu</th>
                                                <th style="text-align: center;">Daya</th>
                                                <th style="text-align: center;">V1</th>
                                                <th style="text-align: center;">V2</th>
                                                <th style="text-align: center;">Arus DC</th>
                                                <th style="text-align: center;">Arus AC</th>
                                                <th style="text-align: center;">Kecepatan Angin</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        <?php
                                        $query = "SELECT CONVERT_TZ(Waktu, '+00:00', '+07:00') AS Waktu, Daya, V1, V2, `Arus DC`, `Arus AC`, `Kecepatan Angin` FROM kinerja ORDER BY Waktu DESC LIMIT 10";
                                        $result = mysqli_query($conn, $query);
                                        while ($row = mysqli_fetch_assoc($result)) {
                                            echo "<tr>";
                                            echo "<td style='text-align: center;'>" . htmlspecialchars($row['Waktu']) . "</td>";
                                            echo "<td style='text-align: center;'>" . htmlspecialchars($row['Daya']) . " Watt</td>";
                                            echo "<td style='text-align: center;'>" . htmlspecialchars($row['V1']) . " V</td>";
                                            echo "<td style='text-align: center;'>" . htmlspecialchars($row['V2']) . " V</td>";
                                            echo "<td style='text-align: center;'>" . htmlspecialchars($row['Arus DC']) . " A</td>";
                                            echo "<td style='text-align: center;'>" . htmlspecialchars($row['Arus AC']) . " A</td>";
                                            echo "<td style='text-align: center;'>" . htmlspecialchars($row['Kecepatan Angin']) . " m/s</td>";
                                            echo "</tr>";
                                        }
                                        ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    <?php elseif ($page == 'ta-bas1-vol1'): ?>
                        <h1 class="mt-4" style="margin-bottom: 2rem;">MONITORING TURBIN ANGIN VOL1</h1>
                        <div class="row justify-content-center mb-4">
                            <div class="col-auto">
                                <button class="btn btn-primary" onclick="window.location.reload();">
                                    <i class="fas fa-sync-alt mr-2"></i>Refresh Data
                                </button>
                                <button class="btn btn-success" onclick="exportToCSV('<?php echo $page; ?>')">
                                    <i class="fas fa-download mr-2"></i>Download Data
                                </button>
                            </div>
                        </div>
                        <div class="row justify-content-center">
                            <div class="col-xl-3 col-md-6">
                                <div class="card bg-primary text-white mb-4">
                                    <div class="card-body d-flex justify-content-center align-items-center">
                                        <?php echo htmlspecialchars($daya_display) . " Watt"; ?>
                                    </div>
                                    <div class="card-footer d-flex align-items-center justify-content-between">
                                        <a class="small text-white stretched-link" href="detail.php?card=daya">Lihat Detail</a>
                                        <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-3 col-md-6">
                                <div class="card bg-warning text-white mb-4">
                                    <div class="card-body d-flex justify-content-center align-items-center">
                                        <?php echo htmlspecialchars($v1_display) . " V"; ?>
                                    </div>
                                    <div class="card-footer d-flex align-items-center justify-content-between">
                                        <a class="small text-white stretched-link" href="detail.php?card=v1">Lihat Detail</a>
                                        <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-3 col-md-6">
                                <div class="card bg-success text-white mb-4">
                                    <div class="card-body d-flex justify-content-center align-items-center">
                                        <?php echo htmlspecialchars($v2_display) . " V"; ?>
                                    </div>
                                    <div class="card-footer d-flex align-items-center justify-content-between">
                                        <a class="small text-white stretched-link" href="detail.php?card=v2">Lihat Detail</a>
                                        <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-3 col-md-6">
                                <div class="card bg-success text-white mb-4">
                                    <div class="card-body d-flex justify-content-center align-items-center">
                                        <?php echo htmlspecialchars($arus_dc_display) . " A"; ?>
                                    </div>
                                    <div class="card-footer d-flex align-items-center justify-content-between">
                                        <a class="small text-white stretched-link" href="detail.php?card=arus_dc&value=<?php echo urlencode($arus_dc_display); ?>">Lihat Detail</a>
                                        <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-xl-6">
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <i class="fas fa-chart-area mr-1"></i>
                                        Grafik Daya Turbin Angin/Hari
                                    </div>
                                    <div class="card-body"><canvas id="dailyPowerChart" width="200%" height="300"></canvas></div>
                                </div>
                            </div>
                            <div class="col-xl-6">
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <i class="fas fa-chart-bar mr-1"></i>
                                        Grafik Daya Turbin Angin/Minggu
                                    </div>
                                    <div class="card-body"><canvas id="weeklyPowerChart" width="200%" height="300"></canvas></div>
                                </div>
                            </div>
                        </div>
                        <div class="card mb-4">
                            <div class="card-header">
                                <i class="fas fa-table mr-1"></i>
                                Data TA-BAS1 VOL1
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered" id="dataTableVol1" width="100%" cellspacing="0">
                                        <thead>
                                            <tr>
                                                <th style="text-align: center;">Waktu</th>
                                                <th style="text-align: center;">Daya</th>
                                                <th style="text-align: center;">V1</th>
                                                <th style="text-align: center;">V2</th>
                                                <th style="text-align: center;">Arus DC</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        <?php
                                        $query = "SELECT CONVERT_TZ(Waktu, '+00:00', '+07:00') AS Waktu, Daya, V1, V2, `Arus DC` FROM kinerja ORDER BY Waktu DESC LIMIT 10";
                                        $result = mysqli_query($conn, $query);
                                        while ($row = mysqli_fetch_assoc($result)) {
                                            echo "<tr>";
                                            echo "<td style='text-align: center;'>" . htmlspecialchars($row['Waktu']) . "</td>";
                                            echo "<td style='text-align: center;'>" . htmlspecialchars($row['Daya']) . " Watt</td>";
                                            echo "<td style='text-align: center;'>" . htmlspecialchars($row['V1']) . " V</td>";
                                            echo "<td style='text-align: center;'>" . htmlspecialchars($row['V2']) . " V</td>";
                                            echo "<td style='text-align: center;'>" . htmlspecialchars($row['Arus DC']) . " A</td>";
                                            echo "</tr>";
                                        }
                                        ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
            <footer class="py-4 bg-light mt-auto">
                <div class="container-fluid"></div>
            </footer>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="js/scripts.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.8.0/Chart.min.js" crossorigin="anonymous"></script>
    <script>
        // Data untuk ekspor CSV
        var tableData = <?php echo $tableDataJson; ?>;

        // Fungsi untuk ekspor riwayat ke CSV
        function exportToCSV(page) {
            var csv;
            if (page === 'dashboard') {
                csv = ['Waktu,Daya (Watt),V1 (V),V2 (V),Arus DC (A),Arus AC (A),Kecepatan Angin (m/s)'];
                tableData.forEach(row => {
                    csv.push(`${row['Waktu']},${row['Daya']},${row['V1']},${row['V2']},${row['Arus DC']},${row['Arus AC']},${row['Kecepatan Angin']}`);
                });
            } else {
                csv = ['Waktu,Daya (Watt),V1 (V),V2 (V),Arus DC (A)'];
                tableData.forEach(row => {
                    csv.push(`${row['Waktu']},${row['Daya']},${row['V1']},${row['V2']},${row['Arus DC']}`);
                });
            }
            const csvContent = csv.join('\n');
            const blob = new Blob([csvContent], { type: 'text/csv' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `kinerja_turbin_${new Date().toISOString().replace(/T/, '_').replace(/:/g, '-')}.csv`;
            a.click();
            URL.revokeObjectURL(url);
        }
    </script>
    <?php if ($page == 'dashboard'): ?>
    <script>
        var dailyDaya = <?php echo $dailyDaya; ?> || [];
        var dailyKecepatan = <?php echo $dailyKecepatan; ?> || [];
        var weeklyDaya = <?php echo $weeklyDaya; ?> || [];
        var weeklyKecepatan = <?php echo $weeklyKecepatan; ?> || [];
        console.log("Daily Daya:", dailyDaya);
        console.log("Daily Kecepatan:", dailyKecepatan);
        console.log("Weekly Daya:", weeklyDaya);
        console.log("Weekly Kecepatan:", weeklyKecepatan);

        // Grafik Area (Harian)
        var ctxArea = document.getElementById("myAreaChart").getContext("2d");
        new Chart(ctxArea, {
            type: 'line',
            data: {
                labels: dailyDaya,
                datasets: [{
                    label: "Kecepatan Angin (m/s)",
                    data: dailyKecepatan,
                    backgroundColor: "rgba(78, 115, 223, 0.2)",
                    borderColor: "rgba(78, 115, 223, 1)",
                    borderWidth: 1,
                    fill: true
                }]
            },
            options: {
                maintainAspectRatio: false,
                scales: {
                    xAxes: [{
                        scaleLabel: { display: true, labelString: 'Daya (Watt)' },
                        ticks: { autoSkip: false, maxRotation: 45, minRotation: 45 }
                    }],
                    yAxes: [{
                        scaleLabel: { display: true, labelString: 'Kecepatan Angin (m/s)' }
                    }]
                }
            }
        });

        // Grafik Bar (Mingguan)
        var ctxBar = document.getElementById("myBarChart").getContext("2d");
        new Chart(ctxBar, {
            type: 'bar',
            data: {
                labels: weeklyDaya,
                datasets: [{
                    label: "Kecepatan Angin (m/s)",
                    data: weeklyKecepatan,
                    backgroundColor: "#4e73df",
                    borderColor: "#4e73df",
                    borderWidth: 1
                }]
            },
            options: {
                maintainAspectRatio: false,
                scales: {
                    xAxes: [{
                        scaleLabel: { display: true, labelString: 'Daya (Watt)' },
                        ticks: { autoSkip: false, maxRotation: 45, minRotation: 45 }
                    }],
                    yAxes: [{
                        scaleLabel: { display: true, labelString: 'Kecepatan Angin (m/s)' }
                    }]
                }
            }
        });
    </script>
    <script src="https://cdn.datatables.net/1.10.20/js/jquery.dataTables.min.js" crossorigin="anonymous"></script>
    <script src="https://cdn.datatables.net/1.10.20/js/dataTables.bootstrap4.min.js" crossorigin="anonymous"></script>
    <script src="assets/demo/datatables-demo.js"></script>
    <?php elseif ($page == 'ta-bas1-vol1'): ?>
    <script>
        var dailyDaya = <?php echo $dailyDaya; ?> || [];
        var dailyLabels = <?php echo $dailyLabels; ?> || [];
        var weeklyDaya = <?php echo $weeklyDaya; ?> || [];
        var weeklyLabels = <?php echo $weeklyLabels; ?> || [];
        console.log("Daily Daya:", dailyDaya);
        console.log("Daily Labels:", dailyLabels);
        console.log("Weekly Daya:", weeklyDaya);
        console.log("Weekly Labels:", weeklyLabels);

        // Grafik Area (Harian)
        var ctxDailyPower = document.getElementById("dailyPowerChart").getContext("2d");
        new Chart(ctxDailyPower, {
            type: 'line',
            data: {
                labels: dailyLabels,
                datasets: [{
                    label: "Daya (Watt)",
                    data: dailyDaya,
                    backgroundColor: "rgba(78, 115, 223, 0.2)",
                    borderColor: "rgba(78, 115, 223, 1)",
                    borderWidth: 1,
                    fill: true
                }]
            },
            options: {
                maintainAspectRatio: false,
                scales: {
                    xAxes: [{
                        scaleLabel: { display: true, labelString: 'Jam' },
                        ticks: { autoSkip: false, maxRotation: 45, minRotation: 45 }
                    }],
                    yAxes: [{
                        scaleLabel: { display: true, labelString: 'Daya (Watt)' }
                    }]
                }
            }
        });

        // Grafik Bar (Mingguan)
        var ctxWeeklyPower = document.getElementById("weeklyPowerChart").getContext("2d");
        new Chart(ctxWeeklyPower, {
            type: 'bar',
            data: {
                labels: weeklyLabels,
                datasets: [{
                    label: "Daya (Watt)",
                    data: weeklyDaya,
                    backgroundColor: "#4e73df",
                    borderColor: "#4e73df",
                    borderWidth: 1
                }]
            },
            options: {
                maintainAspectRatio: false,
                scales: {
                    xAxes: [{
                        scaleLabel: { display: true, labelString: 'Tanggal' },
                        ticks: { autoSkip: false, maxRotation: 45, minRotation: 45 }
                    }],
                    yAxes: [{
                        scaleLabel: { display: true, labelString: 'Daya (Watt)' }
                    }]
                }
            }
        });
    </script>
    <script src="https://cdn.datatables.net/1.10.20/js/jquery.dataTables.min.js" crossorigin="anonymous"></script>
    <script src="https://cdn.datatables.net/1.10.20/js/dataTables.bootstrap4.min.js" crossorigin="anonymous"></script>
    <script src="assets/demo/datatables-demo.js"></script>
    <?php endif; ?>
</body>
</html>
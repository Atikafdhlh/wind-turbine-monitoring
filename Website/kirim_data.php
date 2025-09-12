<?php
header('Content-Type: text/plain');
date_default_timezone_set('Asia/Jakarta'); // Set PHP timezone to WIB

// Database connection details
$host = "localhost";
$user = "";
$password = "";
$dbname = "u825743231_kinerjaturbina";

// Create database connection
$conn = new mysqli($host, $user, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set MySQL timezone to WIB
$conn->query("SET time_zone = '+07:00';");

// Handle POST request from ESP32
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate input data
    $v1 = isset($_POST['tegangan_1']) && is_numeric($_POST['tegangan_1']) ? floatval($_POST['tegangan_1']) : null;
    $v2 = isset($_POST['tegangan_2']) && is_numeric($_POST['tegangan_2']) ? floatval($_POST['tegangan_2']) : null;
    $daya = isset($_POST['daya']) && is_numeric($_POST['daya']) ? floatval($_POST['daya']) : null;
    $arus_dc = isset($_POST['arus']) && is_numeric($_POST['arus']) ? floatval($_POST['arus']) : null;

    // Check for valid, non-negative data
    if ($v1 === null || $v2 === null || $daya === null || $arus_dc === null || $v1 < 0 || $v2 < 0 || $daya < 0 || $arus_dc < 0) {
        http_response_code(400); // Bad Request
        echo "Error: Invalid data (values must be numeric and non-negative)";
        $conn->close();
        exit;
    }

    // Use prepared statement to insert data, with backticks for column name with space
    $sql = "INSERT INTO kinerja (Waktu, V1, V2, Daya, `Arus DC`) VALUES (NOW(), ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        http_response_code(500); // Internal Server Error
        echo "Error: Failed to prepare statement - " . $conn->error;
        $conn->close();
        exit;
    }

    // Bind parameters
    $stmt->bind_param("dddd", $v1, $v2, $daya, $arus_dc);

    // Execute statement
    if ($stmt->execute()) {
        $current_time = date('Y-m-d H:i:s');
        http_response_code(200); // OK
        echo "Data successfully saved at WIB time: " . $current_time;
    } else {
        http_response_code(500); // Internal Server Error
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
} else {
    http_response_code(405); // Method Not Allowed
    echo "Only POST method is accepted";
}

// Close connection
$conn->close();

?>

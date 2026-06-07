
<?php
$host = "localhost";
$username = "root";
$password = "";
$database = "clinic_system_demo_v2"; // ✅ UPDATED NAME

$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Optional: set charset (GOOD PRACTICE 🔥)
$conn->set_charset("utf8mb4");
?>


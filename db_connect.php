<?php
$servername = "localhost";
$username = "root";  // Adjust if needed
$password = "";      // Change if your MySQL has a password
$dbname = "bakery";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die(json_encode(["error" => "Database connection failed: " . $conn->connect_error]));
}
?>


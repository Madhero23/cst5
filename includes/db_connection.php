<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "bakery";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch(PDOException $e) {
    error_log("Connection failed: " . $e->getMessage());
    die("Database connection failed. Please try again later.");
}
?>

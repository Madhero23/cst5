<?php
session_start();
include("db_connect.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_SESSION['user_id'];
    $currentPassword = $_POST['currentPassword'];
    $newPassword = $_POST['newPassword'];
    $confirmPassword = $_POST['confirmPassword'];

    // Fetch current password from the database
    $sql = "SELECT EmployeePassword FROM employee WHERE EmployeeID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if ($currentPassword === $row['EmployeePassword']) {
        if ($newPassword === $confirmPassword) {
            // Update password
            $sql = "UPDATE employee SET EmployeePassword = ? WHERE EmployeeID = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $newPassword, $userId);
            $stmt->execute();

            echo "<script>alert('Password changed successfully!'); window.location.href = 'index.php';</script>";
        } else {
            echo "<script>alert('New passwords do not match.'); window.history.back();</script>";
        }
    } else {
        echo "<script>alert('Current password is incorrect.'); window.history.back();</script>";
    }
}
?>
<?php
session_start();
include("db_connect.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_SESSION['user_id'];
    $name = $_POST['name'];
    $email = $_POST['email'];

    // Update name and email
    $sql = "UPDATE employee SET EmployeeName = ?, EmployeeContactInfo = ? WHERE EmployeeID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $name, $email, $userId);
    $stmt->execute();

    // Handle profile picture upload
    if ($_FILES['profilePicture']['error'] === UPLOAD_ERR_OK) {
        // Validate file type (only allow images)
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $fileType = $_FILES['profilePicture']['type'];

        if (!in_array($fileType, $allowedTypes)) {
            echo "<script>alert('Only JPG, PNG, and GIF files are allowed.'); window.history.back();</script>";
            exit();
        }

        $targetDir = "uploads/";
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true); // Create the directory if it doesn't exist
        }

        $fileName = basename($_FILES['profilePicture']['name']);
        $targetFile = $targetDir . $fileName;

        // Move the uploaded file to the target directory
        if (move_uploaded_file($_FILES['profilePicture']['tmp_name'], $targetFile)) {
            // Save the file path in the database
            $sql = "UPDATE employee SET ProfilePicture = ? WHERE EmployeeID = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $targetFile, $userId);
            $stmt->execute();

            // Update session with the new profile picture path
            $_SESSION['profile_picture'] = $targetFile;
        } else {
            echo "<script>alert('Failed to upload profile picture.'); window.history.back();</script>";
            exit();
        }
    }

    // Update session variables
    $_SESSION['user_name'] = $name;
    $_SESSION['user_email'] = $email;

    echo "<script>alert('Profile updated successfully!'); window.location.href = 'index.php';</script>";
}
?>
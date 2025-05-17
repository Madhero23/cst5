<?php
session_start();
include("db_connect.php");

// Redirect to login if the user is not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Start a transaction
    $conn->begin_transaction();

    try {
        if ($user_type === 'employee') {
            $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
            $contact_info = filter_input(INPUT_POST, 'contact_info', FILTER_SANITIZE_STRING);

            $stmt = $conn->prepare("UPDATE employee SET EmployeeName = ?, EmployeeContactInfo = ? WHERE EmployeeID = ?");
            $stmt->bind_param("ssi", $name, $contact_info, $user_id);
        } else if ($user_type === 'customer') {
            $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
            $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
            $birthday = filter_input(INPUT_POST, 'birthday', FILTER_SANITIZE_STRING);

            $stmt = $conn->prepare("UPDATE customer SET CustomerName = ?, CustomerEmail = ?, CustomerBirthday = ? WHERE CustomerID = ?");
            $stmt->bind_param("sssi", $name, $email, $birthday, $user_id);
        }

        if ($stmt->execute()) {
            // Commit the transaction
            $conn->commit();
            header("Location: account.php");
            exit();
        } else {
            // Rollback the transaction if there's an error
            $conn->rollback();
            echo "Error updating record: " . $conn->error;
        }

        $stmt->close();
    } catch (Exception $e) {
        // Rollback the transaction if an exception occurs
        $conn->rollback();
        echo "Error: " . $e->getMessage();
    }

    $conn->close();
}
?>
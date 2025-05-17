<?php
require_once 'C:\xampp\htdocs\thebakers\CSE7-Project\includes\db_connection.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $productId = $_POST['product_id'];
        $reason = $_POST['reason'];
        $customerId = $_SESSION['user_id'] ?? null;

        // First, create an order record
        $stmt = $conn->prepare("INSERT INTO orders (OrderStatus, TotalPrice, CustomerID) 
                              SELECT 'Cancelled', Price, ? FROM products WHERE ProductID = ?");
        $stmt->execute([$customerId, $productId]);
        $orderId = $conn->lastInsertId();

        // Then create the refund request using the OrderID and CustomerID
        $stmt = $conn->prepare("INSERT INTO refund (RefundReason, RefundAmount, OrderID, Status) 
                              SELECT ?, Price, ?, 'Pending' 
                              FROM products WHERE ProductID = ?");
        
        if ($stmt->execute([$reason, $orderId, $productId])) {
            $_SESSION['message'] = "Your refund request has been submitted successfully. We will process it within 24-48 hours.";
            header("Location: homepage.php");
            exit();
        } else {
            throw new Exception("Failed to submit refund request");
        }

    } catch (Exception $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
        error_log("Refund error: " . $e->getMessage());
        header("Location: refund.php");
        exit();
    }
}
?>
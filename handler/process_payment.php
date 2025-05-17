<?php
require_once '../includes/db_connection.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validate payment method - must match ENUM values in database
        $allowedMethods = ['Cash', 'Card', 'Online'];
        $paymentMethod = in_array($data['paymentMethod'], $allowedMethods) ? $data['paymentMethod'] : 'Cash';

        $conn->beginTransaction();

        // Create order
        $stmt = $conn->prepare("INSERT INTO orders (OrderStatus, TotalPrice, CustomerID) 
                               VALUES ('Pending', ?, ?)");
        $stmt->execute([$data['total'], $_SESSION['user_id'] ?? null]);
        $orderId = $conn->lastInsertId();

        // Create payment record with validated payment method
        $stmt = $conn->prepare("INSERT INTO payment (PaymentMethod, OrderID, CustomerID) 
                               VALUES (?, ?, ?)");
        $stmt->execute([$paymentMethod, $orderId, $_SESSION['user_id'] ?? null]);
        $paymentId = $conn->lastInsertId();

        // Create sales record
        $stmt = $conn->prepare("INSERT INTO sales (TotalAmount, TransactionStatus, OrderID, PaymentID) 
                               VALUES (?, 'Success', ?, ?)");
        $stmt->execute([$data['total'], $orderId, $paymentId]);

        // Update order status
        $stmt = $conn->prepare("UPDATE orders SET OrderStatus = 'Completed' WHERE OrderID = ?");
        $stmt->execute([$orderId]);

        $conn->commit();
        echo json_encode(['success' => true]);

    } catch (Exception $e) {
        $conn->rollBack();
        error_log("Payment error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit();
}
?>

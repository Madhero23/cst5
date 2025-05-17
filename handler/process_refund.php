<?php
require_once 'db_connect.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn->begin_transaction();

        $order_id = $_POST['order_id'];
        $refund_amount = floatval($_POST['refund_amount']);
        $refund_reason = $_POST['refund_reason'];
        $employee_id = $_SESSION['user_id'] ?? 1;

        // Verify order exists and hasn't been refunded
        $check_order = $conn->prepare("
            SELECT o.TotalPrice, o.OrderStatus 
            FROM orders o 
            LEFT JOIN refund r ON o.OrderID = r.OrderID
            WHERE o.OrderID = ? AND r.RefundID IS NULL
        ");
        $check_order->bind_param("i", $order_id);
        $check_order->execute();
        $order = $check_order->get_result()->fetch_assoc();

        if (!$order) {
            throw new Exception("Invalid order or already refunded");
        }

        if ($refund_amount > $order['TotalPrice']) {
            throw new Exception("Refund amount cannot exceed order total");
        }

        // Create refund record
        $refund_stmt = $conn->prepare("
            INSERT INTO refund (
                RefundReason, 
                RefundAmount, 
                OrderID, 
                EmployeeID
            ) VALUES (?, ?, ?, ?)
        ");
        
        $refund_stmt->bind_param("sdii", $refund_reason, $refund_amount, $order_id, $employee_id);
        $refund_stmt->execute();

        // Update sales status
        $update_sales = $conn->prepare("
            UPDATE sales 
            SET TransactionStatus = 'Refunded'
            WHERE OrderID = ?
        ");
        $update_sales->bind_param("i", $order_id);
        $update_sales->execute();

        $conn->commit();
        echo json_encode(['success' => true]);

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>

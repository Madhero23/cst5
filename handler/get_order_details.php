<?php
require_once '../includes/db_connection.php';
session_start();

if (isset($_GET['order_id']) && isset($_SESSION['user_id'])) {
    try {
        $stmt = $conn->prepare("SELECT o.*, p.PaymentMethod 
                               FROM orders o 
                               LEFT JOIN payment p ON o.OrderID = p.OrderID 
                               WHERE o.OrderID = ? AND o.CustomerID = ?");
        $stmt->execute([$_GET['order_id'], $_SESSION['user_id']]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($order) {
            echo json_encode([
                'order_date' => date('M d, Y', strtotime($order['OrderDate'])),
                'total_price' => number_format($order['TotalPrice'], 2),
                'payment_method' => $order['PaymentMethod'] ?? 'N/A'
            ]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Order not found']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Server error']);
    }
    exit();
}
?>

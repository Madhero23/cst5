<?php
require_once 'includes/db_connection.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $refundId = $_POST['refundId'];
    $status = $_POST['status'];

    try {
        // Update the refund status
        $stmt = $conn->prepare("UPDATE refund SET Status = ? WHERE RefundID = ?");
        $stmt->execute([$status, $refundId]);

        echo json_encode(['status' => 'success']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}
?>
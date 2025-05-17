<?php
require_once 'includes/db_connection.php';
session_start();

header('Content-Type: application/json'); // Ensure the response is JSON

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Retrieve data from the AJAX request
        $refundId = $_POST['refundId'];
        $status = $_POST['status'];

        // Validate input
        if (empty($refundId) || empty($status)) {
            throw new Exception("Refund ID and status are required.");
        }

        // Update the refund status in the database
        $stmt = $conn->prepare("UPDATE refund SET Status = ? WHERE RefundID = ?");
        $stmt->execute([$status, $refundId]);

        // Return success response
        echo json_encode(['status' => 'success']);
    } catch (Exception $e) {
        // Return error response
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
} else {
    // Return error if the request method is not POST
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}
?>
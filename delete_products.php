<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$productIds = $data['productIds'] ?? [];

if (empty($productIds)) {
    echo json_encode(['success' => false, 'error' => 'No products selected']);
    exit();
}

try {
    $conn->begin_transaction();

    // Prepare the SQL statement
    $placeholders = implode(',', array_fill(0, count($productIds), '?'));
    $stmt = $conn->prepare("DELETE FROM products WHERE ProductID IN ($placeholders)");
    $stmt->bind_param(str_repeat('i', count($productIds)), ...$productIds);
    $stmt->execute();

    $conn->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
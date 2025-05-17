<?php
session_start();
require_once 'db_connect.php';

// Add error logging
error_reporting(E_ALL);
ini_set('display_errors', 1);
error_log("Processing stock out request");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = ['success' => false, 'message' => ''];
    
    try {
        // Log the received data
        error_log("POST data received: " . print_r($_POST, true));
        
        // Validate inputs
        if (!isset($_POST['product_id']) || !isset($_POST['quantity']) || !isset($_POST['reason'])) {
            throw new Exception("Missing required fields. Received: " . print_r($_POST, true));
        }

        $product_id = (int)$_POST['product_id'];
        $quantity = (int)$_POST['quantity'];
        $reason = $_POST['reason'];
        $employee_id = $_SESSION['user_id'] ?? 1;

        error_log("Processing for Product ID: $product_id, Quantity: $quantity, Reason: $reason");

        $conn->begin_transaction();

        // Check current stock
        $check_stock = $conn->prepare("
            SELECT ProductID, ProductName, StockQuantity 
            FROM products 
            WHERE ProductID = ? 
            FOR UPDATE
        ");

        if (!$check_stock) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        $check_stock->bind_param("i", $product_id);
        if (!$check_stock->execute()) {
            throw new Exception("Execute failed: " . $check_stock->error);
        }

        $result = $check_stock->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception("Product ID $product_id not found");
        }

        $product = $result->fetch_assoc();
        error_log("Current stock for product: " . print_r($product, true));

        if ($quantity > $product['StockQuantity']) {
            throw new Exception("Cannot remove $quantity items. Only {$product['StockQuantity']} available.");
        }

        // Insert stock out record
        $insert = $conn->prepare("
            INSERT INTO stockout_products 
            (ProductID, QuantityRemoved, EmployeeID, Reason, StockOutDate) 
            VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)
        ");

        if (!$insert) {
            throw new Exception("Insert prepare failed: " . $conn->error);
        }

        $insert->bind_param("iiis", $product_id, $quantity, $employee_id, $reason);
        
        if (!$insert->execute()) {
            throw new Exception("Insert failed: " . $insert->error);
        }

        // Update product stock
        $new_quantity = $product['StockQuantity'] - $quantity;
        $status = $new_quantity <= 0 ? 'Out of Stock' : 'Available';
        
        $update = $conn->prepare("
            UPDATE products 
            SET StockQuantity = ?,
                Status = ?,
                DateStockout = CURRENT_DATE
            WHERE ProductID = ?
        ");

        if (!$update) {
            throw new Exception("Update prepare failed: " . $conn->error);
        }

        $update->bind_param("isi", $new_quantity, $status, $product_id);
        
        if (!$update->execute()) {
            throw new Exception("Update failed: " . $update->error);
        }

        $conn->commit();
        $response['success'] = true;
        $response['message'] = "Successfully removed $quantity items";
        $response['new_quantity'] = $new_quantity;
        
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error occurred: " . $e->getMessage());
        $response['success'] = false;
        $response['message'] = $e->getMessage();
    }

    // Send JSON response
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}
?>
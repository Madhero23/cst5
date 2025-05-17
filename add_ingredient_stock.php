<?php
require_once 'db_connect.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn->begin_transaction();

        $ingredient_id = $_POST['ingredient_id'];
        $quantity = $_POST['quantity'];
        $supplier_id = $_POST['supplier_id'];
        $notes = $_POST['notes'] ?? '';
        $employee_id = $_SESSION['user_id'] ?? 1;

        // Add stock record
        $stock_stmt = $conn->prepare("
            INSERT INTO stockin (
                IngredientID,
                QuantityAdded,
                SupplierID,
                EmployeeID,
                Notes
            ) VALUES (?, ?, ?, ?, ?)
        ");
        
        $stock_stmt->bind_param("iiiis", $ingredient_id, $quantity, $supplier_id, $employee_id, $notes);
        
        if (!$stock_stmt->execute()) {
            throw new Exception("Error creating stock record: " . $stock_stmt->error);
        }

        // Update ingredient quantity
        $update_stmt = $conn->prepare("
            UPDATE ingredients 
            SET ItemStockQuantity = ItemStockQuantity + ?,
                IngredientExpirationDate = DATE_ADD(CURRENT_DATE, INTERVAL 30 DAY)
            WHERE IngredientID = ?
        ");
        
        $update_stmt->bind_param("ii", $quantity, $ingredient_id);
        
        if (!$update_stmt->execute()) {
            throw new Exception("Error updating ingredient quantity: " . $update_stmt->error);
        }

        $conn->commit();
        echo json_encode(['success' => true]);
        
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>
